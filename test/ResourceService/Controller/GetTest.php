<?php

namespace Islandora\Crayfish\ResourceService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;

class GetTest extends CrayfishWebTestCase
{
    public function setUp()
    {
        parent::setUp();
    }
    
    public function createApplication()
    {
        return parent::createApplication();
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::get
     */
    public function testGetRootResource()
    {
        $getResponse = Response::create(CrayfishWebTestCase::$rootRdf, 200, CrayfishWebTestCase::$rootHeaders);

        $this->api->expects($this->once())->method('getResource')->willReturn($getResponse);
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
            'Server' => CrayfishWebTestCase::$serverHeader,
            'ETag' => "4e98ff87ecceab2aa535f606fef7b7cde38ab8b9",
            'Content-Type' => 'text/plain',
            'Content-Length' => 46,
            'Date' => CrayfishWebTestCase::$today,
            'Last-Modified' => 'Wed, 18 May 2016 03:07:33 GMT',
            'Location' => 'http://localhost:8080/fcrepo/rest/bobs/burgers',
        );
        
        $getResponse = Response::create($headers["Location"], 200, $headers);

        $this->api->expects($this->once())->method('getResource')->willReturn($getResponse);

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
        
        $this->triplestore->expects($this->once())->method('query')->willReturn($result);
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::get
     */
    public function testGetResourceException()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );
        $uuid = $this->uuid_gen->generateV4();

        $query_result = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "s" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/object1"
      }
    } ]
  }
}';
        
        $result = new \EasyRdf_Sparql_Result($query_result, 'application/sparql-results+json');
        $this->triplestore->expects($this->once())->method('query')->willReturn($result);
        
        $this->api->expects($this->any())->method('getResource')->will($this->throwException(new \Exception));

        $client = $this->createClient();
        $crawler = $client->request('GET', "/islandora/resource/" . $uuid);
        $this->assertEquals($client->getResponse()->getStatusCode(), 503, "Should have aborted route.");
    }
}
