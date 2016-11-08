<?php

namespace Islandora\Crayfish\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use GuzzleHttp\Psr7\Response;
use Islandora\Crayfish\CrayfishWebTestCase;

class UuidCacheTransactionTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::storeUuid
     */
    public function testPostInsideTransactionOk()
    {
        $txID1 = "tx:" . $this->uuid_gen->generateV4();
        $uuid1 = $this->uuid_gen->generateV4();
        $location1 = "http://localhost:8080/fcrepo/rest/ab/cd/01/4c/" . $this->uuid_gen->generateV4();
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
            'Location' => $location1,
        );

        $uuid_json = '[ {
      "@id" : "' . $location1 .'",
  "@type" : [ "http://fedora.info/definitions/v4/repository#Container",
    "http://fedora.info/definitions/v4/repository#Resource",
    "http://www.w3.org/ns/ldp#RDFSource",
    "http://www.w3.org/ns/ldp#Container" ],
  "http://fedora.info/definitions/v4/repository#created" : [ {
        "@type" : "http://www.w3.org/2001/XMLSchema#dateTime",
    "@value" : "2016-10-04T17:57:53.508Z"
  } ],
  "http://fedora.info/definitions/v4/repository#createdBy" : [ {
        "@value" : "fedoraAdmin"
  } ],
  "http://fedora.info/definitions/v4/repository#hasParent" : [ {
        "@id" : "http://localhost:8080/fcrepo/rest/"
  } ],
  "http://fedora.info/definitions/v4/repository#lastModified" : [ {
        "@type" : "http://www.w3.org/2001/XMLSchema#dateTime",
    "@value" : "2016-10-04T17:57:53.508Z"
  } ],
  "http://fedora.info/definitions/v4/repository#lastModifiedBy" : [ {
        "@value" : "fedoraAdmin"
  } ],
  "http://fedora.info/definitions/v4/repository#writable" : [ {
        "@type" : "http://www.w3.org/2001/XMLSchema#boolean",
    "@value" : "true"
  } ],
  "' . TransactionController::$uuidPredicate . '" : [ {
    "@value" : "' . $uuid1 . '"
} ]';

        $responseOK = new Response(201, $headers, $location1);
        
        unset($headers['Location']);
        $headers['Content-Type'] = 'application/json';
        $headers['Content-Length'] = strlen($uuid_json);
        $responseTransform = new Response(200, $headers, $uuid_json);

        $this->api->expects($this->once())->method('createResource')->willReturn($responseOK);
        $this->api->expects($this->once())->method('getResource')->willReturn($responseTransform);
        
        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/resource?tx=${txID1}");
        $this->assertEquals(
            $client->getResponse()->getStatusCode(),
            201,
            "Did not get transaction status. " . $client->getResponse()->getContent()
        );
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::put
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::storeUuid
     */
    public function testPutInsideTransactionOk()
    {
        $txID1 = "tx:" . $this->uuid_gen->generateV4();
        $uuid = $this->uuid_gen->generateV4();
        $location = "http://localhost:8080/fcrepo/rest/object1";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
            'Location' => $location,
        );

        $uuid_json = '[ {
      "@id" : "' . $location .'",
  "@type" : [ "http://fedora.info/definitions/v4/repository#Container",
    "http://fedora.info/definitions/v4/repository#Resource",
    "http://www.w3.org/ns/ldp#RDFSource",
    "http://www.w3.org/ns/ldp#Container" ],
  "http://fedora.info/definitions/v4/repository#created" : [ {
        "@type" : "http://www.w3.org/2001/XMLSchema#dateTime",
    "@value" : "2016-10-04T17:57:53.508Z"
  } ],
  "http://fedora.info/definitions/v4/repository#createdBy" : [ {
        "@value" : "fedoraAdmin"
  } ],
  "http://fedora.info/definitions/v4/repository#hasParent" : [ {
        "@id" : "http://localhost:8080/fcrepo/rest/"
  } ],
  "http://fedora.info/definitions/v4/repository#lastModified" : [ {
        "@type" : "http://www.w3.org/2001/XMLSchema#dateTime",
    "@value" : "2016-10-04T17:57:53.508Z"
  } ],
  "http://fedora.info/definitions/v4/repository#lastModifiedBy" : [ {
        "@value" : "fedoraAdmin"
  } ],
  "http://fedora.info/definitions/v4/repository#writable" : [ {
        "@type" : "http://www.w3.org/2001/XMLSchema#boolean",
    "@value" : "true"
  } ],
  "' . TransactionController::$uuidPredicate . '" : [ {
    "@value" : "' . $uuid . '"
} ]';

        $responseOK = new Response(201, $headers, $location);

        unset($headers['Location']);
        $headers['Content-Type'] = 'application/json';
        $headers['Content-Length'] = strlen($uuid_json);
        $responseTransform = new Response(200, $headers, $uuid_json);

        $this->api->expects($this->once())->method('saveResource')->willReturn($responseOK);
        $this->api->expects($this->once())->method('getResource')->willReturn($responseTransform);

        $client = $this->createClient();
        $crawler = $client->request('PUT', "/islandora/resource?tx=${txID1}");
        $this->assertEquals(
            $client->getResponse()->getStatusCode(),
            201,
            "Did not get transaction status. " . $client->getResponse()->getContent()
        );
    }
}
