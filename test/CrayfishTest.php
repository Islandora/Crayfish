<?php

namespace Islandora\Crayfish\Test;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Silex\WebTestCase;

class CrayfishTest extends WebTestCase
{
 
    protected $api;
    
    protected $triplestore;
    
    private static $today;
    
    private static $rootRdf;
    
    private static $serverHeader = 'Server: Jetty(9.2.3.v20140905)';
    
    private static $rootHeaders = array(
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
        CrayfishTest::setVar('rootRdf', file_get_contents(__DIR__ . '/rootRdf.txt'));
        CrayfishTest::setVar('rootHeaders', CrayfishTest::$serverHeader, 'Server');
        CrayfishTest::setVar('rootHeaders', implode(',', array(
            'text/turtle',
            'text/rdf+n3',
            'text/n3',
            'application/rdf+xml',
            'application/n-triples',
            'multipart/form-data',
            'application/sparql-update',
        )), 'Accept-Post');
        $date = new \DateTime("now", new \DateTimeZone('UTC'));
        CrayfishTest::setVar('today', $date->format('r'));

        CrayfishTest::setVar('rootHeaders', strlen(CrayfishTest::$rootRdf), 'Content-Length');
        CrayfishTest::setVar('rootHeaders', CrayfishTest::$today, 'Date');
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
    private static function setVar($varname, $value, $key = null)
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
        ->setMethods(array("getResource", "saveResource", "createResource", "modifyResource", "deleteResource"))
        ->getMock();
        
        $this->triplestore = $this->getMockBuilder('\Islandora\Chullo\TriplestoreClient')
        ->disableOriginalConstructor()
        ->setMethods(array('query'))
        ->getMock();
        
        $this->app['api'] = $this->api;
        $this->app['triplestore'] = $this->triplestore;
    }
    
    public function createApplication()
    {
        // must return an Application instance
        return require __DIR__.'/../src/app.php';
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::get
     */
    public function testGetRootResource()
    {
        $getResponse = Response::create(CrayfishTest::$rootRdf, 200, CrayfishTest::$rootHeaders);

        $this->api->expects($this->once())->method('getResource')->will($this->returnValue($getResponse));
        // Symfony BrowserKit Client
        // @link http://api.symfony.com/2.3/Symfony/Component/BrowserKit/Client.html
        $client = $this->createClient();
        // Symfony DomCrawler Crawler
        // @link http://api.symfony.com/3.0/Symfony/Component/DomCrawler/Crawler.html
        $crawler = $client->request('GET', '/islandora/resource');
        $this->assertEquals($client->getResponse()->getStatusCode(), 200, "Did not get root resource");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::get
     */
    public function testGetResource()
    {
        $headers = array(
            'Server' => CrayfishTest::$serverHeader,
            'ETag' => "4e98ff87ecceab2aa535f606fef7b7cde38ab8b9",
            'Content-Type' => 'text/plain',
            'Content-Length' => 46,
            'Date' => CrayfishTest::$today,
            'Last-Modified' => 'Wed, 18 May 2016 03:07:33 GMT',
            'Location' => 'http://localhost:8080/fcrepo/rest/bobs/burgers',
        );
        
        $getResponse = Response::create($headers["Location"], 200, $headers);

        $this->api->expects($this->once())->method('getResource')->will($this->returnValue($getResponse));

        $query_result = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "s" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/bobs/burgers"
      }
    } ]
  }
}';
        
        $result = new \EasyRdf_Sparql_Result($query_result, 'application/sparql-results+json');
        
        $this->triplestore->expects($this->once())->method('query')->will($this->returnValue($result));
        $client = $this->createClient();
        $crawler = $client->request('GET', '/islandora/resource/f218d271-98ee-4a90-a06a-03420a96d5af');
        $this->assertEquals($client->getResponse()->getStatusCode(), 200, "Did not get resource");
        $this->assertEquals(
            $client->getResponse()->headers->get('location'),
            $headers['Location'],
            "Did not get correct resource location"
        );
        
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::post
     */
    public function testPostResourceToRoot()
    {
        $headers = array(
            'Server' => CrayfishTest::$serverHeader,
            'ETag' => "ba88cf750cf2e170dc01830931a727427795747f",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Location' => "http://localhost:8080/fcrepo/rest/28/c3/7c/25/28c37c25-8c48-46b8-a00c-5df9af261b8b",
            'Content-Type' => 'text/plain',
            'Content-Length' => '82',
            'Date' => CrayfishTest::$today,
        );
        
        $postResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('createResource')->will($this->returnValue($postResponse));
       
        $client = $this->createClient();
        $crawler = $client->request("POST", "/islandora/resource");
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create new node");
        $this->assertEquals($client->getResponse()->getContent(), $headers['Location'], "Created URL does not match");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::post
     */
    public function testPostResource()
    {
        $headers = array(
            'Server' => CrayfishTest::$serverHeader,
            'ETag' => "ba88cf750cf2e170dc01830931a727427795747f",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Location' => "http://localhost:8080/fcrepo/rest/28/c3/7c/25/28c37c25-8c48-46b8-a00c-5df9af261b8b",
            'Content-Type' => 'text/plain',
            'Content-Length' => '82',
            'Date' => CrayfishTest::$today,
        );
        
        $postResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('createResource')->will($this->returnValue($postResponse));
        
        $query_result = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "s" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/test"
      }
    } ]
  }
}';
        
        $result = new \EasyRdf_Sparql_Result($query_result, 'application/sparql-results+json');
        
        $this->triplestore->expects($this->once())->method('query')->will($this->returnValue($result));
       
        $client = $this->createClient();
        $crawler = $client->request("POST", "/islandora/resource/f218d271-98ee-4a90-a06a-03420a96d5af");
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create new node");
        $this->assertEquals($client->getResponse()->getContent(), $headers['Location'], "Created URL does not match");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::put
     */
    public function testPutResource()
    {
        $headers = array(
            'Server' => CrayfishTest::$serverHeader,
            'ETag' => "04fd070cc35e916f359fa46f51008481e6596e91",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Location' => 'http://localhost:8080/fcrepo/rest/new/test/object',
            'Content-Type' => 'text/plain',
            'Content-Length' => 49,
            'Date' => CrayfishTest::$today,
        );
        
        $putResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('saveResource')->will($this->returnValue($putResponse));
        
        $client = $this->createClient();
        $crawler = $client->request('PUT', '/islandora/resource/');
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create a new resource");
        $this->assertEquals(
            $client->getResponse()->headers->get('Location'),
            $headers['Location'],
            'Did not get the correct Location'
        );
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::patch
     */
    public function testPatchResource()
    {
        $patch_content = "prefix dc: <http://purl.org/dc/elements/1.1/> INSERT { <> dc:title 'The title' . }  WHERE {}";
        
        $patch_headers = array(
            'Content-Type' => 'application/sparql-update',
        );
        
        $headers = array(
            'Server' => CrayfishTest::$serverHeader,
            'ETag' => "81798b3c24bce2eacbbe58e76d7bf590a97736f0",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Date' => CrayfishTest::$today,
        );
        
        $patchResponse = Response::create('', 204, $headers);
        
        $this->api->expects($this->once())->method('modifyResource')->will($this->returnValue($patchResponse));
        
        $query_result = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "s" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/test"
      }
    } ]
  }
}';
        
        $result = new \EasyRdf_Sparql_Result($query_result, 'application/sparql-results+json');
        
        $this->triplestore->expects($this->once())->method('query')->will($this->returnValue($result));
        
        $client = $this->createClient();
        $crawler = $client->request(
            'PATCH',
            '/islandora/resource/f218d271-98ee-4a90-a06a-03420a96d5af',
            array(),
            array(),
            $patch_headers
        );
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not patch resource");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::delete
     */
    public function testDeleteResource()
    {
        $headers = array(
            'Server' => CrayfishTest::$serverHeader,
            'Date' => CrayfishTest::$today,
        );

        $deleteResponse = Response::create('', 204, $headers);
        
        $this->api->expects($this->once())->method('deleteResource')->will($this->returnValue($deleteResponse));
        
        $client = $this->createClient();
        $crawler = $client->request('DELETE', '/islandora/resource/');
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not delete resource");
    }
}
