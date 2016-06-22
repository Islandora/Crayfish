<?php

namespace Islandora\Crayfish\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;
use Islandora\Crayfish\TransactionService\Controller\TransactionController;
use Islandora\Crayfish\KeyCache\UuidCache;

class CreateTransactionTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::create
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::getId
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::parseTransactionId
     */
    public function testCreateTransaction()
    {
        $txID = "tx:f218d271-98ee-4a90-a06a-03420a96d5af";
        $location = "http://localhost:8080/fcrepo/rest/$txID";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Location' => $location,
            'Content-Type' => 'text/plain',
            'Content-Length' => strlen($location),
            'Expires' => $this->today_dt->add(new \DateInterval("P3M"))->format('r'),
            'Date' => CrayfishWebTestCase::$today,
        );

        $response = Response::create($location, 201, $headers);
        
        $this->api->expects($this->once())->method("createTransaction")->willReturn($response);
        
        $client = $this->createClient();
        $crawler = $client->request('POST', '/islandora/transaction');
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create a transaction");
        
        $application = $this->createApplication();
        $cache = new UuidCache(new \Moust\Silex\Cache\ArrayCache());
        $tempController = new TransactionController($application, $cache);
        $this->assertEquals(
            $tempController->getId($client->getResponse()),
            $txID,
            "Did not get expected transaction ID from response"
        );
    }
}
