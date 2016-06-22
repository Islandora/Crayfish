<?php

namespace Islandora\Crayfish\ResourceService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;

class DeleteTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::delete
     */
    public function testDeleteResource()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );

        $deleteResponse = Response::create('', 204, $headers);
        
        $this->api->expects($this->once())->method('deleteResource')->willReturn($deleteResponse);
        
        $client = $this->createClient();
        $crawler = $client->request('DELETE', '/islandora/resource/');
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not delete resource");
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::delete
     */
    public function testDeleteResourceResourceWithProxies()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );
        $uuid = $this->uuid_gen->generateV4();

        $query_result1 = '{
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
        $result1 = new \EasyRdf_Sparql_Result($query_result1, 'application/sparql-results+json');
        // This is the results of looking for proxies
        $query_result2 = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "obj" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/object2"
      }
    } ]
  }
}';
        $result2 = new \EasyRdf_Sparql_Result($query_result2, 'application/sparql-results+json');

        $this->triplestore->expects($this->at(0))->method('query')->willReturn($result1);
        $this->triplestore->expects($this->at(1))->method('query')->willReturn($result2);
        
        $deleteResponse = Response::create('', 204, $headers);
        $this->api->expects($this->any())->method('deleteResource')->willReturn($deleteResponse);

        $client = $this->createClient();
        $crawler = $client->request('DELETE', "/islandora/resource/" . $uuid);
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not delete object with proxies.");
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::delete
     */
    public function testDeleteForce()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );
        $uuid = $this->uuid_gen->generateV4();

        $query_result1 = '{
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
        $result1 = new \EasyRdf_Sparql_Result($query_result1, 'application/sparql-results+json');

        // This is the results of looking for children
        $query_result2 = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [  ]
  }
}';
        $result2 = new \EasyRdf_Sparql_Result($query_result2, 'application/sparql-results+json');

        $this->triplestore->expects($this->at(0))->method('query')->willReturn($result1);
        $this->triplestore->expects($this->at(1))->method('query')->willReturn($result2);
        
        $deleteResponse = Response::create('', 204, $headers);
        $this->api->expects($this->any())->method('deleteResource')->willReturn($deleteResponse);

        $client = $this->createClient();
        $crawler = $client->request('DELETE', "/islandora/resource/" . $uuid . "?force=true");
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not delete object with proxies.");
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::delete
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testDeleteResourceException()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );
        $uuid = $this->uuid_gen->generateV4();

        $query_result1 = '{
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
        $result1 = new \EasyRdf_Sparql_Result($query_result1, 'application/sparql-results+json');
        // This is the results of looking for children
        $query_result2 = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [  ]
  }
}';
        $result2 = new \EasyRdf_Sparql_Result($query_result2, 'application/sparql-results+json');

        $this->triplestore->expects($this->at(0))->method('query')->willReturn($result1);
        $this->triplestore->expects($this->at(1))->method('query')->willReturn($result2);
        
        $this->api->expects($this->any())->method('deleteResource')->will($this->throwException(new \Exception));

        $client = $this->createClient();
        $crawler = $client->request('DELETE', "/islandora/resource/" . $uuid);
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::delete
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testDeleteSparqlException()
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
        
        $this->triplestore->expects($this->at(0))->method('query')->willReturn($result);
        $this->triplestore->expects($this->at(1))->method('query')->will($this->throwException(new \Exception));
        
        $client = $this->createClient();
        $crawler = $client->request('DELETE', "/islandora/resource/" . $uuid);
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::delete
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testDeleteFailureCode()
    {
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );
        $uuid = $this->uuid_gen->generateV4();

        $query_result1 = '{
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
        $result1 = new \EasyRdf_Sparql_Result($query_result1, 'application/sparql-results+json');
        $this->triplestore->expects($this->at(0))->method('query')->willReturn($result1);
        
        $deleteResponse = Response::create('', 403, $headers);
        $this->api->expects($this->any())->method('deleteResource')->willReturn($deleteResponse);

        $client = $this->createClient();
        $crawler = $client->request('DELETE', "/islandora/resource/" . $uuid);
        $this->assertEquals($client->getResponse()->getStatusCode(), 503, "Did not delete object with proxies.");
    }
}
