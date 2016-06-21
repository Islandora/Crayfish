<?php

namespace Islandora\Crayfish\Test\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\Test\CrayfishWebTestCase;

class CommitTransactionTest extends CrayfishWebTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->api->method('getResourceHeaders')->willReturn(Response::create("", 200));
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
        
        $responseGone = Response::create('', 410, $headers);
        
        $this->api->method('commitTransaction')->willReturn($responseGone);
        
        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/transaction/${txID}/commit");
        
        $this->assertEquals($client->getResponse()->getStatusCode(), 410, "Transaction should be gone.");
    }
}
