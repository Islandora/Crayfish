<?php

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\Chullo;
use Islandora\Chullo\FedoraApi;

class ModifyResourceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers  Islandora\Chullo\Chullo::modifyResource
     * @covers  Islandora\Chullo\FedoraApi::modifyResource
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsTrueOn204()
    {
        $mock = new MockHandler([
            new Response(204),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        $result = $client->modifyResource("");
        $this->assertTrue($result);
    }

    /**
     * @covers  Islandora\Chullo\Chullo::modifyResource
     * @covers  Islandora\Chullo\FedoraApi::modifyResource
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsFalseOtherwise()
    {
        $mock = new MockHandler([
            new Response(412),
            new Response(404),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        foreach ($mock as $response) {
            $result = $client->modifyResource("");
            $this->assertFalse($result);
        }
    }
}
