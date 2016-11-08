<?php

namespace Islandora\Crayfish\TransactionService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;

class ExtendTransactionTest extends CrayfishWebTestCase
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
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::extend
     */
    public function testExtendTransaction()
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

        $response = Response::create('', 204, $headers);
        $this->api->expects($this->once())->method('extendTransaction')->willReturn($response);
        
        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/transaction/${txID}/extend");
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not extend transaction");
        $expires = new \DateTime($client->getResponse()->headers->get('expires'));
        $this->assertTrue($expires > new \DateTime(), "New transaction expiry is not in the future");
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\TransactionService\Controller\TransactionController::extend
     */
    public function testExtendTransactionException()
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

        $this->api->expects($this->once())->method('extendTransaction')->will($this->throwException(new \Exception));

        $client = $this->createClient();
        $crawler = $client->request('POST', "/islandora/transaction/${txID}/extend");
        $this->assertEquals($client->getResponse()->getStatusCode(), 503, "Should have aborted route.");
    }
}
