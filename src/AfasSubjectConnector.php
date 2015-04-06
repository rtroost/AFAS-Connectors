<?php namespace App\Afas;

class AfasSubjectConnector extends AfasConnector {

    protected $url = 'https://profitweb.afasonline.nl/profitservices/subjectconnector.asmx?WSDL';

    public function execute()
    {
        parent::setOption( [
            'filtersXml' => ($this->filters ? $this->generateFilterXml() : '')
        ] );

        return parent::request( 'GetAttachment' );
    }
}