<?php

namespace Islandora\Crayfish\Test;

use Silex\WebTestCase;

class CrayfishWebTestCase extends WebTestCase
{
    protected $api;
    
    protected $triplestore;
    
    protected static $today;
    
    protected $today_dt;
    
    protected static $rootRdf;
    
    protected static $serverHeader = 'Server: Jetty(9.2.3.v20140905)';
    
    protected static $rootHeaders = array(
        'Server' => 'update',
        'Link' => '<http://www.w3.org/ns/ldp#Resource>;rel="type"',
        'Link' => '<http://www.w3.org/ns/ldp#Container>;rel="type"',
        'Link' => '<http://www.w3.org/ns/ldp#BasicContainer>;rel="type"',
        'Accept-Patch' => 'application/sparql-update',
        'Allow' => 'MOVE,COPY,DELETE,POST,HEAD,GET,PUT,PATCH,OPTIONS',
        'Preference-Applied' => 'return=representation',
        'Vary' => 'Prefer',
        'Vary' => 'Accept, Range, Accept-Encoding, Accept-Language',
        'Content-Type' => 'text/turtle',
    );

    public function __construct()
    {
        CrayfishWebTestCase::setVar('rootRdf', file_get_contents(__DIR__ . '/rootRdf.txt'));
        CrayfishWebTestCase::setVar('rootHeaders', CrayfishWebTestCase::$serverHeader, 'Server');
        CrayfishWebTestCase::setVar('rootHeaders', implode(',', array(
            'text/turtle',
            'text/rdf+n3',
            'text/n3',
            'application/rdf+xml',
            'application/n-triples',
            'multipart/form-data',
            'application/sparql-update',
        )), 'Accept-Post');
        $date = new \DateTime("now", new \DateTimeZone('UTC'));
        CrayfishWebTestCase::setVar('today', $date->format('r'));
        $this->today_dt = $date;

        CrayfishWebTestCase::setVar('rootHeaders', strlen(CrayfishWebTestCase::$rootRdf), 'Content-Length');
        CrayfishWebTestCase::setVar('rootHeaders', CrayfishWebTestCase::$today, 'Date');
    }
    
    public function createApplication()
    {
        // must return an Application instance
        return (require __DIR__.'/../src/app.php');
    }
    
    /**
     * Static variable initialization
     *
     * @var $varname string
     *   The name of the variable.
     * @var $value mixed
     *   The value to set to the variable.
     * @var $key string
     *   A key incase the variable is an array.
     */
    protected static function setVar($varname, $value, $key = null)
    {
        if (!is_null($key) && is_array(self::$$varname)) {
            self::${$varname}[$key] = $value;
        } else {
            self::${$varname} = $value;
        }
    }
    
    public function setUp()
    {
        parent::setUp();
        
        $this->api = $this->getMockBuilder('\Islandora\Chullo\FedoraApi')
        ->disableOriginalConstructor()
        ->setMethods(
            array(
            "getResource",
            "saveResource",
            "createResource",
            "modifyResource",
            "deleteResource",
            "createTransaction",
            "commitTransaction",
            "extendTransaction",
            "rollbackTransaction",
            )
        )
        ->getMock();
        
        $this->triplestore = $this->getMockBuilder('\Islandora\Chullo\TriplestoreClient')
        ->disableOriginalConstructor()
        ->setMethods(array('query'))
        ->getMock();
        
        $this->app['api'] = $this->api;
        $this->app['triplestore'] = $this->triplestore;
    }
}
