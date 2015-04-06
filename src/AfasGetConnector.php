<?php namespace App\Afas;

class AfasGetConnector extends AfasConnector {

    protected $url = 'https://profitweb.afasonline.nl/profitservices/getconnector.asmx?WSDL';

    private $filters = [ ];
    public $columns;
    public $columnsWithTypes;

    public function execute( $json = true )
    {
        parent::setExtraOptions( [
            'connectorId' => $this->connector,
            'filtersXml' => ($this->filters ? $this->generateFilterXml() : '')
        ] );

        if( !$result = parent::request( 'GetData' ) ) {
            return false;
        }

        // Check if there's any result at all
        if( !$result ) {
            $this->errors[ 'faultstring' ] = $this->client->getError();
            if( strpos( $this->client->getError(), 'Invalid document end' ) !== false ) {
                $this->errors[ 'detail' ] = 'Waarschijnlijk is het aanmelden met de opgegeven gebruikersnaam en wachtwoord niet gelukt, controller de login gegevens.';
            }
            return false;
        }

        $this->findColumns( utf8_encode( $result[ 'GetDataResult' ] ) );

        if( $json ) {
            $result = $this->afasXmlToArray( simplexml_load_string( utf8_encode( $result[ 'GetDataResult' ] ) ) );
            $result = $this->completeTableResult( $result ); // past het resultaat aan
        } else {
            $result = $result[ 'GetDataResult' ];
        }

        if( !array_key_exists( $this->connector, $result ) ) {
            $this->errors[ 'message' ] = 'Transactie voltooid maar met errors.';
            $this->errors[ 'faultcode' ] = $result[ 'faultcode' ];
            $this->errors[ 'faultstring' ] = $result[ 'faultstring' ];
            $this->errors[ 'detail' ] = $result[ 'detail' ];
            return false;
        }

        return $result;
    }

    private function findColumns( $xml )
    {
        $tempColumns = array();
        $tempColumnsWithType = array();

        $indexFirstXsElement = strpos( $xml, '<xs:element' );
        $indexSecondXsElement = strpos( $xml, '<xs:element', $indexFirstXsElement + 1 );

        $currectIndexXsElement = $indexSecondXsElement;

        while( strpos( $xml, '<xs:element', $currectIndexXsElement + 1 ) ) {
            $indexColum = strpos( $xml, '<xs:element', $currectIndexXsElement + 1 );
            // 18 = str_length <xs:element name="
            $posColumName = $indexColum + 18;
            $endPosColumName = strpos( $xml, '"', $posColumName );
            $colum = substr( $xml, $posColumName, $endPosColumName - $posColumName );

            $indexType = strpos( $xml, 'type="', $endPosColumName );
            // 9 = str_length type="xs:
            $posColumType = $indexType + 9;
            $endPosColumType = strpos( $xml, '"', $posColumType );
            $type = substr( $xml, $posColumType, $endPosColumType - $posColumType );

            $currectIndexXsElement = $endPosColumType;

            $tempColumns[ ] = $colum;
            $tempColumnsWithType[ $colum ] = $type;
        }

        $this->columns = $tempColumns;
        $this->columnsWithTypes = $tempColumnsWithType;

    }

    private function completeTableResult( $result )
    {
        $newResult = array();
        $singleResultCount = 0;
        $singleResult = false;

        if( !array_key_exists( $this->connector, $result ) ) {
            $result[ $this->connector ] = [ ];
            return false;
        }

        foreach( $result[ $this->connector ] as $index => $array ) {

            // als er 1 resultaat terug komt van afas
            if( !is_array( $array ) ) {
                $singleResult = true;
                if( $this->columns[ $singleResultCount ] !== $index ) {
                    while( $this->columns[ $singleResultCount ] !== $index ) {
                        $newResult[ $this->columns[ $singleResultCount ] ] = '';
                        $singleResultCount++;
                    }
                    $newResult[ $this->columns[ $singleResultCount ] ] = $this->mutateValue( $array );
                    $singleResultCount++;
                } else {
                    $newResult[ $this->columns[ $singleResultCount ] ] = $this->mutateValue( $array );
                    $singleResultCount++;
                }
            } else {
                // als er meedere resultaten terug komen
                $row = array();
                $count = 0;
                foreach( $array as $key => $value ) {
                    if( $this->columns[ $count ] !== $key ) {
                        while( $this->columns[ $count ] !== $key ) {
                            $row[ $this->columns[ $count ] ] = '';
                            $count++;
                        }
                        $row[ $this->columns[ $count ] ] = $this->mutateValue( $value );
                        $count++;
                    } else {
                        $row[ $this->columns[ $count ] ] = $this->mutateValue( $value );
                        $count++;
                    }
                }

                $newResult[ $index ] = $row;
            }


        }

        $result[ $this->connector ] = ($singleResult) ? [ $newResult ] : $newResult;

        return $result;
    }

    public function mutateValue( $value )
    {
        // controller of de value een array is, in dit geval gewoon leeg terug geven
        if( is_array( $value ) ) {
            return '';
        }

        // controleer op datetime
        if( $this->isDateTime( $value ) ) {
            $dateSplit = explode( 'T', $value );
            return $dateSplit[ 0 ] . ' ' . $dateSplit[ 1 ];
        }
        // eventueel controlle op andere velden
        return $value;
    }

    public function countResult( $result )
    {
        return count( $result[ $this->connector ] );
    }

    private function isDateTime( $value )
    {
        $dateSplit = explode( 'T', $value );
        if( count( $dateSplit ) == 2 ) {
            $datum = explode( '-', $dateSplit[ 0 ] );
            $tijd = explode( ':', $dateSplit[ 1 ] );
            if( strlen( $datum[ 0 ] ) == 4 && is_numeric( $datum[ 0 ] ) &&
                strlen( $datum[ 1 ] ) == 2 && is_numeric( $datum[ 1 ] ) &&
                strlen( $datum[ 2 ] ) == 2 && is_numeric( $datum[ 2 ] ) &&
                strlen( $tijd[ 0 ] ) == 2 && is_numeric( $tijd[ 0 ] ) &&
                strlen( $tijd[ 1 ] ) == 2 && is_numeric( $tijd[ 1 ] ) &&
                strlen( $tijd[ 2 ] ) == 2 && is_numeric( $tijd[ 2 ] )
            ) {
                return true;
            }
        }
        return false;
    }

    private function afasXmlToArray( $xmlObject )
    {
        $out = array();

        foreach( (array)$xmlObject as $index => $node ) {
            $out[ $index ] = (is_object( $node ) || is_array( $node )) ? $this->afasXmlToArray( $node ) : $node;
        }

        return $out;
    }

    public function removeFromColums( $columName )
    {
        $tempColumns = $this->columns;
        if( ($key = array_search( $columName, $tempColumns )) !== false ) {
            unset($tempColumns[ $key ]);
        }
        $this->columns = $tempColumns;

        $tempColumnsTypes = $this->columnsWithTypes;
        unset($tempColumnsTypes[ $columName ]);
        $this->columnsWithTypes = $tempColumnsTypes;
    }

    private function generateFilterXml()
    {
        //        <Filters>
        //          <Filter FilterId="Filter1">
        //              <Field FieldId="Geblokkeerd" OperatorType="1">false</Field>
        //          </Filter>
        //        </Filters>

        $filter = '<Filters>';
        /* foreach($this->filters as $index => $filterText){
             $filter .= '<Filter FilterId="Filter' . ($index+1) . '">';
             $filter .= $filterText;
             $filter .= '</Filter>';
         }*/
        $filter .= '<Filter FilterId="Filter1">';
        foreach( $this->filters as $index => $filterText ) {
            //            $filter .= '<Filter FilterId="Filter' . ($index+1) . '">';
            $filter .= $filterText;
            //            $filter .= '</Filter>';
        }
        $filter .= '</Filter>';
        $filter .= '</Filters>';

        return $filter;
    }

    //    private function isDate( $value )
    //    {
    //        $dateSplit = explode( '-', $value );
    //        if( count( $dateSplit ) == 3 ) {
    //            if( strlen( $dateSplit[ 0 ] ) == 4 && is_numeric( $dateSplit[ 0 ] ) &&
    //                strlen( $dateSplit[ 1 ] ) == 2 && is_numeric( $dateSplit[ 1 ] ) &&
    //                strlen( $dateSplit[ 2 ] ) == 2 && is_numeric( $dateSplit[ 2 ] )
    //            ) {
    //                return true;
    //            }
    //        }
    //        return false;
    //    }

    /** ------------------------
     *  Getters and setters
     *  ------------------------
     */

    public function getFilter()
    {
        return $this->filters;
    }

    public function setFilter( $key, $operator, $value = false )
    {

        if( gettype( $value ) == 'boolean' && $value == false ) {
            $filterOperator = 1;
            $value = $operator;
        }

        $filter = '<Field FieldId="' . $key . '" OperatorType="';
        if( !isset($filterOperator) ) {
            switch( $operator ) {
                case '=':
                    $filterOperator = 1;
                    break;
                case '>=':
                    $filterOperator = 2;
                    break;
                case '<=':
                    $filterOperator = 3;
                    break;
                case '>':
                    $filterOperator = 4;
                    break;
                case '<':
                    $filterOperator = 5;
                    break;
                case 'like':
                    $filterOperator = 6;
                    break;
                case 'notLike':
                    $filterOperator = 7;
                    break;
                case 'empty':
                    $filterOperator = 8;
                    break;
                case 'notEmpty':
                    $filterOperator = 9;
                    break;
                case 'like%':
                    $filterOperator = 10;
                    break;
                case 'notLike%':
                    $filterOperator = 12;
                    break;
                case '%like':
                    $filterOperator = 13;
                    break;
                case 'not%like%':
                    $filterOperator = 14;
                    break;
            }
        }
        $filter .= $filterOperator . '">' . $value . '</Field>';

        $this->filters[ ] = $filter;
    }

    public function resetFilter()
    {
        return $this->filters = [ ];
    }

    public function getFilterCount()
    {
        return count( $this->filters );
    }

}