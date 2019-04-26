<?php

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\Chullo;
use Islandora\Chullo\FedoraApi;

class CreateResourceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers  Islandora\Chullo\Chullo::createResource
     * @covers  Islandora\Chullo\FedoraApi::createResource
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsUriOn201()
    {
        $mock = new MockHandler([
            new Response(201, ['Location' => "SOME URI"]),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        $result = $client->createResource("");
        $this->assertSame($result, "SOME URI");
    }

    /**
     * @covers  Islandora\Chullo\Chullo::createResource
     * @covers  Islandora\Chullo\FedoraApi::createResource
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsNullOtherwise()
    {
        $mock = new MockHandler([
            new Response(404),
            new Response(409),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        // 404
        $result = $client->createResource("");
        $this->assertNull($result);

        // 409
        $result = $client->createResource("");
        $this->assertNull($result);
    }
}
