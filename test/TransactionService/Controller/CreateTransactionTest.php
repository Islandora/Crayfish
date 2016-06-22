<?php

namespace Islandora\Crayfish\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;
use Islandora\Crayfish\TransactionService\Controller\TransactionController;
use Islandora\Crayfish\KeyCache\UuidCache;
use Islandora\Chullo\Uuid\UuidGenerator;

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
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::installUuidTransform
     */
    public function testCreateTransaction()
    {
        $txID = "tx:" . $this->uuid_gen->generateV4();
        $location = "http://localhost:8080/fcrepo/rest/$txID";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Location' => $location,
            'Content-Type' => 'text/plain',
            'Content-Length' => strlen($location),
            'Expires' => $this->today_dt->add(new \DateInterval("P3M"))->format('r'),
            'Date' => CrayfishWebTestCase::$today,
        );

        // Need to mock that the transform is installed
        $response_transform = Response::create('', 200);
        $this->api->expects($this->any())->method('getResourceHeaders')->willReturn($response_transform);

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

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::getId
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::parseTransactionId
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::__construct
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::installUuidTransform
     */
    public function testParseTransactionIdGuzzle()
    {
        $txID = "tx:" . $this->uuid_gen->generateV4();
        $location = "http://localhost:8080/fcrepo/rest/{$txID}";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Location' => $location,
            'Content-Type' => 'text/plain',
            'Content-Length' => strlen($location),
            'Expires' => $this->today_dt->add(new \DateInterval("P3M"))->format('r'),
            'Date' => CrayfishWebTestCase::$today,
        );

        $response = new \GuzzleHttp\Psr7\Response(201, $headers, $location);

        $application = $this->createApplication();
        $cache = new UuidCache(new \Moust\Silex\Cache\ArrayCache());
        $tempController = new TransactionController($application, $cache);

        $this->assertEquals(
            $tempController->getId($response),
            $txID,
            "Did not get the expected transaction ID."
        );
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::getId
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::parseTransactionId
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::installUuidTransform
     */
    public function testParseTransactionIdUnknown()
    {
        $txID = "tx:" . $this->uuid_gen->generateV4();
        $location = "bob's ID is $txID";

        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Location' => $location,
            'Content-Type' => 'text/plain',
            'Content-Length' => strlen($location),
            'Expires' => $this->today_dt->add(new \DateInterval("P3M"))->format('r'),
            'Date' => CrayfishWebTestCase::$today,
        );

        $response = new \stdClass($location);

        $application = $this->createApplication();
        $cache = new UuidCache(new \Moust\Silex\Cache\ArrayCache());
        $tempController = new TransactionController($application, $cache);

        $this->assertEquals(
            $tempController->getId($response),
            null,
            "Got a response."
        );
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::create
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::installUuidTransform
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testCreateTransactionException()
    {
        $txID = "tx:" . $this->uuid_gen->generateV4();
        $location = "http://localhost:8080/fcrepo/rest/$txID";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Location' => $location,
            'Expires' => $this->today_dt->add(new \DateInterval("P3M"))->format('r'),
            'Date' => CrayfishWebTestCase::$today,
        );

        // Need to mock that the transform is installed
        $response_transform = Response::create('', 200);
        $this->api->expects($this->any())->method('getResourceHeaders')->willReturn($response_transform);

        $this->api->expects($this->once())->method('createTransaction')->will($this->throwException(new \Exception));

        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/transaction");
    }
}
