<?php

namespace Islandora\Crayfish\Test\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\Test\CrayfishWebTestCase;

class GetTransactionTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::get
     * @covers \Islandora\Crayfish\Provider\CrayfishProvider::register
     */
    public function testGetTransactionOk()
    {
        $txID1 = "tx:f218d271-98ee-4a90-a06a-03420a96d5af";
        $location1 = "http://localhost:8080/fcrepo/rest/$txID1";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );

        $responseOK = Response::create('', 200, $headers);

        $this->api->expects($this->once())->method('getResource')->willReturn($responseOK);
        
        $client = $this->createClient();
        $crawler = $client->request('GET', "/islandora/transaction/${txID1}");
        $this->assertEquals(
            $client->getResponse()->getStatusCode(),
            200,
            "Did not get transaction status. " . $client->getResponse()->getContent()
        );
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceController::get
     */
    public function testGetTransactionGone()
    {
        $txID2 = "tx:f218d271-98ee-4a90-a06a-badTxID";
        $location2 = "http://localhost:8080/fcrepo/rest/$txID2";
        $headers = array(
            'Server' => CrayfishWebTestCase::$serverHeader,
            'Date' => CrayfishWebTestCase::$today,
        );
        $responseGone = Response::create('', 410, $headers);
        
        $client = $this->createClient();
        $crawler = $client->request('GET', "/islandora/transaction/${txID2}");
        
        $this->api->expects($this->once())->method('getResource')->willReturn($responseGone);

        $crawler = $client->request('GET', "/islandora/transaction/${txID2}");
        $this->assertEquals($client->getResponse()->getStatusCode(), 410, "This transaction should not exist.");
        
    }
}
