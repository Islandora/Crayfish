<?php

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\Chullo;
use Islandora\Chullo\FedoraApi;

class SaveGraphTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers  Islandora\Chullo\Chullo::saveGraph
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

        $result = $client->saveGraph("", new \EasyRdf_Graph());
        $this->assertTrue($result);
    }

    /**
     * @covers            Islandora\Chullo\Chullo::saveGraph
     * @uses              GuzzleHttp\Client
     */
    public function testReturnsFalseOtherwise()
    {
        $mock = new MockHandler([
            new Response(412),
            new Response(409),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        foreach ($mock as $response) {
            $result = $client->saveGraph("", new \EasyRdf_Graph());
            $this->assertFalse($result);
        }
    }
}
