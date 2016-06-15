<?php
    
namespace Islandora\Crayfish\Test\ResourceService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\Test\CrayfishWebTestCase;

class PostTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::post
     */
    public function testPostResourceToRoot()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'ETag' => "ba88cf750cf2e170dc01830931a727427795747f",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Location' => "http://localhost:8080/fcrepo/rest/28/c3/7c/25/28c37c25-8c48-46b8-a00c-5df9af261b8b",
            'Content-Type' => 'text/plain',
            'Content-Length' => '82',
            'Date' => CrayfishWebTestCase::$today,
        );
        
        $postResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('createResource')->willReturn($postResponse);
       
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
            'Server' => CrayfishWebTestCase::$serverHeader,
            'ETag' => "ba88cf750cf2e170dc01830931a727427795747f",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Location' => "http://localhost:8080/fcrepo/rest/28/c3/7c/25/28c37c25-8c48-46b8-a00c-5df9af261b8b",
            'Content-Type' => 'text/plain',
            'Content-Length' => '82',
            'Date' => CrayfishWebTestCase::$today,
        );
        
        $postResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('createResource')->willReturn($postResponse);
        
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
        
        $this->triplestore->expects($this->once())->method('query')->willReturn($result);
       
        $client = $this->createClient();
        $crawler = $client->request("POST", "/islandora/resource/f218d271-98ee-4a90-a06a-03420a96d5af");
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create new node");
        $this->assertEquals($client->getResponse()->getContent(), $headers['Location'], "Created URL does not match");
    }
}
