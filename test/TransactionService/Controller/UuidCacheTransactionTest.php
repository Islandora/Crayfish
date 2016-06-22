<?php

namespace Islandora\Crayfish\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use GuzzleHttp\Psr7\Response;
use Islandora\Crayfish\CrayfishWebTestCase;
use Islandora\Chullo\Uuid\UuidGenerator;

class UuidCacheTransactionTest extends CrayfishWebTestCase
{
    private $uuid_gen;

    public function setUp()
    {
        parent::setUp();
        $this->uuid_gen = new UuidGenerator();
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

        $uuid_json = '[{"id":["' . $location1 .'"],"uuid":["' . $uuid1 .'"]}]';

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
}
