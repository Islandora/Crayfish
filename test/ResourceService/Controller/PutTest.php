<?php
    
namespace Islandora\Crayfish\ResourceService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;

class PutTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::put
     */
    public function testPutResource()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'ETag' => "04fd070cc35e916f359fa46f51008481e6596e91",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Location' => 'http://localhost:8080/fcrepo/rest/new/test/object',
            'Content-Type' => 'text/plain',
            'Content-Length' => 49,
            'Date' => CrayfishWebTestCase::$today,
        );
        
        $putResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('saveResource')->willReturn($putResponse);
        
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::put
     */
    public function testPutResourceException()
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
        
        $this->api->expects($this->any())->method('saveResource')->will($this->throwException(new \Exception));

        $client = $this->createClient();
        $crawler = $client->request('PUT', "/islandora/resource/" . $uuid . "/object2");
        $this->assertEquals($client->getResponse()->getStatusCode(), 503, "Should have aborted route.");
    }
}
