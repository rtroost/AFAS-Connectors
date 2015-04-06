<?php namespace App\Afas;

class AfasConnector {

    protected $client;

    /**
     * Holds the URL to the AFAS-connector
     *
     * @var String
     */
    protected $url;

    /**
     * Holds the environment indentifier
     *
     * @var String
     */
    protected $environment;

    /**
     * Holds the name of the connector corresponding
     * to the connector created in AFAS
     *
     * @var String
     */
    protected $connector;

    /**
     * Holds the username
     *
     * @var String
     */
    protected $username;

    /**
     * Holds the password
     *
     * @var String
     */
    protected $password;

    protected $extraOptions = [ ];

    private $lastResult = [ ];

    protected $xmlVersion = '1.0';

    public $errors;

    public function __construct()
    {
        $this->client = new \nusoap_client( $this->url, true, false, false, false, false, 300, 300 );
    }

    public function request( $method )
    {
        if( !$this->username || !$this->password )
            throw \Exception( 'No credentials set.' );

        $this->errors = [ ];
        $this->client->setCredentials( $this->username, $this->password, 'ntlm' );

        $options = [
            'environmentId' => $this->environment,
            'userId' => $this->username,
            'password' => $this->password,
            'logonAs' => '',
        ];

        $options = array_merge( $options, $this->extraOptions );

        $result = null;

        try {
            $result = $this->client->call( $method, $options );
        } catch( \Exception $e ) {
            $this->errors[ 'clientError' ] = $this->client->getError();

            //            dd($this->client->debug_str);
            $this->errors[ 'exception' ] = $e;
            return false;
        }

        // Check for any errors from the remote service
        if( $this->client->fault ) {
            $this->errors[ 'message' ] = 'Transactie voltooid maar met errors.';
            return false;
        }

        //        $this->lastResult = $result ? $result : [ ];
        $this->lastResult = $result;

        return $result;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setEnvironment( $env )
    {
        $this->environment = $env;
    }

    public function getCredentials()
    {
        return [
            'username' => $this->username,
            'password' => $this->password
        ];
    }

    public function setCredentials( $username, $password )
    {
        self::setUsername( $username );
        self::setPassword( $password );
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername( $username )
    {
        $this->username = str_replace( 'AOL\\', "", $username );
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword( $password )
    {
        $this->password = $password;
    }

    public function getConnector()
    {
        return $this->connector;
    }

    public function setConnector( $connector )
    {
        $this->connector = $connector;
    }

    public function getExtraOptions()
    {
        return $this->extraOptions;
    }

    public function setExtraOptions( array $extraOptions )
    {
        $this->extraOptions = $extraOptions;
    }

    public function setOption( array $options )
    {
        if( empty($options) )
            return;

        if( !is_array( $options[ 0 ] ) )
            $options = [ $options ];

        $this->extraOptions = array_merge($this->extraOptions, $options);
    }

    public function getLastResult()
    {
        return $this->lastResult;
    }

    protected function makeXmlVersionNotation()
    {
        return '<?xml version="' . $this->xmlVersion . '"?>';
    }

    public function hasErrors()
    {
        return (bool)$this->errors;
    }
}