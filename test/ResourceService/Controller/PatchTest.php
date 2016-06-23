<?php
    
namespace Islandora\Crayfish\ResourceService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;

class PatchTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::patch
     */
    public function testPatchResource()
    {
        $patch_content = "prefix dc: <http://purl.org/dc/elements/1.1/> INSERT { <> dc:title 'The title' . }  WHERE {}";
        
        $patch_headers = array(
            'Content-Type' => 'application/sparql-update',
        );
        
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'ETag' => "81798b3c24bce2eacbbe58e76d7bf590a97736f0",
            'Last-Modified' => 'Fri, 20 May 2016 15:42:01 GMT',
            'Date' => CrayfishWebTestCase::$today,
        );
        
        $patchResponse = Response::create('', 204, $headers);
        
        $this->api->expects($this->once())->method('modifyResource')->willReturn($patchResponse);
        
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::patch
     */
    public function testPatchResourceException()
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
        
        $this->api->expects($this->any())->method('modifyResource')->will($this->throwException(new \Exception));

        $client = $this->createClient();
        $crawler = $client->request('PATCH', "/islandora/resource/" . $uuid);
        $this->assertEquals($client->getResponse()->getStatusCode(), 503, "Should have aborted route.");
    }
}
