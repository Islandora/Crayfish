<?php

namespace Islandora\Crayfish\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;

class CommitTransactionTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::commit
     */
    public function testCommitTransactionOk()
    {
        $txID = "tx:f218d271-98ee-4a90-a06a-03420a96d5af";
        $location = "http://localhost:8080/fcrepo/rest/$txID";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Location' => $location,
            'Date' => CrayfishWebTestCase::$today,
        );

        // Need to mock that the transform is installed
        $response_transform = Response::create('', 200);
        $this->api->expects($this->any())->method('getResourceHeaders')->willReturn($response_transform);

        $responseOK = Response::create('', 204, $headers);
        $this->api->method('commitTransaction')->willReturn($responseOK);
        
        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/transaction/${txID}/commit");
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not commit transaction.");
        
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::commit
     */
    public function testCommitTransactionGone()
    {
        $txID = "tx:f218d271-98ee-4a90-a06a-badTxID";
        $location = "http://localhost:8080/fcrepo/rest/$txID";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Location' => $location,
            'Date' => CrayfishWebTestCase::$today,
        );

        // Need to mock that the transform is installed
        $response_transform = Response::create('', 200);
        $this->api->expects($this->any())->method('getResourceHeaders')->willReturn($response_transform);

        $responseGone = Response::create('', 410, $headers);
        $this->api->method('commitTransaction')->willReturn($responseGone);
        
        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/transaction/${txID}/commit");
        
        $this->assertEquals($client->getResponse()->getStatusCode(), 410, "Transaction should be gone.");
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::commit
     */
    public function testCommitTransactionException()
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

        $this->api->expects($this->once())->method('commitTransaction')->will($this->throwException(new \Exception));

        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/transaction/{$txID}/commit");
        $this->assertEquals($client->getResponse()->getStatusCode(), 503, "Should have aborted route.");
    }
}
