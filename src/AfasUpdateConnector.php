<?php namespace App\Afas;

use App\AfasGetConnector;

class AfasUpdateConnector extends AfasConnector {

    protected $url = 'https://profitweb.afasonline.nl/profitservices/updateconnector.asmx?WSDL';

    private $selectorData;

    private $conn_to_xml_translations = [
        'KnEmployee' => 'AfasEmployee'
    ];

    public function execute( $xml = '' )
    {
        if( !$xml )
            throw Exception( 'XML was empty.' );

        parent::setExtraOptions( [
            'connectorType' => $this->connector,
            'connectorVersion' => '1',
            'dataXml' => $xml
        ] );

        return parent::request( 'Execute' );
    }

    public function makeInsert( array $xmlData )
    {
        $connXmlTranslation = $this->translateConnForXml( $this->connector );

        $lines = [ ];
        $lines[ ] = parent::makeXmlVersionNotation();
        $lines[ ] = '<' . $connXmlTranslation . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';

        $lines = array_merge( $lines, $this->transformArrayToXml( 'insert', $xmlData ) );

        $lines[ ] = '</' . $connXmlTranslation . '>';

        return implode( $lines );
    }

    public function makeUpdate( array $xmlSelectData, array $xmlData )
    {
        $connXmlTranslation = $this->translateConnForXml( $this->connector );

        $lines = [ ];
        $lines[ ] = parent::makeXmlVersionNotation();
        $lines[ ] = '<' . $connXmlTranslation . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
            '<Element><Fields Action="update">';

        $lines = array_merge( $lines, $this->transformArrayToXml( 'update', $xmlSelectData ) );

        $lines[ ] = '</Fields><Objects>';

        $lines = array_merge( $lines, $this->transformArrayToXml( 'update', $xmlData ) );

        $lines[ ] = '</Objects></Element></' . $connXmlTranslation . '>';

        return implode( $lines );
    }

    protected function transformArrayToXml( $action, array $updateData )
    {
        $lines = [ ];

        foreach( $updateData as $field => $value ) {
            if( is_array( $value ) ) {
                if( is_integer( $field ) ) {
                    // Field is just a key, so the start of a multi insert
                    $lines[ ] = '<Element><Fields Action="' . $action . '">';
                    $lines = array_merge( $lines, $this->transformArrayToXml( $action, $value ) );
                    $lines[ ] = '</Fields></Element>';
                } else {
                    $lines[ ] = '<' . $field . '><Element><Fields Action="insert">';
                    $lines = array_merge( $lines, $this->transformArrayToXml( $action, $value ) );
                    $lines[ ] = '</Fields></Element></' . $field . '>';
                }
            } else {
                if( $value !== '' )
                    $lines[ ] = '<' . $field . '>' . $value . '</' . $field . '>';
                else
                    $lines[ ] = '<' . $field . ' xsi:nil="true"/>';
            }
        }

        return $lines;
    }

    public function setUpdateSelector( $key, $value )
    {
        if( !$this->selectorData ) {
            $this->selectorData = [ $key => $value ];
            return;
        }

        $this->selectorData = array_merge( $this->selectorData, [ $key => $value ] );
    }

    public function setUpdateData( array $updateData )
    {
        $this->updateData = $updateData;
    }

    public function getUpdateXml( $refresh = false )
    {
        if( $refresh )
            $this->updateXml = $this->generateUpdateXML();

        return $this->updateXml;
    }

    public function setUpdateXml( $xml )
    {
        $this->updateXml = $xml;
    }

    protected function translateConnForXml( $conn )
    {
        return array_key_exists( $conn, $this->conn_to_xml_translations ) ? $this->conn_to_xml_translations[ $conn ] : $conn;
    }
}

