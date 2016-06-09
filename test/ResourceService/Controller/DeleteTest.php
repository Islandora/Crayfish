<?php

namespace Islandora\Crayfish\Test\ResourceService\Controller;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\Test\CrayfishWebTestCase;

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
}
