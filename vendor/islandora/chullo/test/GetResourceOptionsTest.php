<?php

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\Chullo;
use Islandora\Chullo\FedoraApi;

class GetResourceOptionsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers  Islandora\Chullo\Chullo::getResourceOptions
     * @covers  Islandora\Chullo\FedoraApi::getResourceOptions
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsHeadersOn200()
    {
        $mock = new MockHandler([
          new Response(200, ['Status: 200 OK', 'Accept-Patch: application/sparql-update',
          'Allow: MOVE,COPY,DELETE,POST,HEAD,GET,PUT,PATCH,OPTIONS',
          'Accept-Post: text/turtle,text/rdf+n3,application/n3,text/n3,application/rdf+xml,application/n-triples,
          multipart/form-data,application/sparql-update']),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        $result = $client->getResourceOptions("");
        $this->assertSame((array)$result, [['Status: 200 OK'], ['Accept-Patch: application/sparql-update'],
          ['Allow: MOVE,COPY,DELETE,POST,HEAD,GET,PUT,PATCH,OPTIONS'],
          ['Accept-Post: text/turtle,text/rdf+n3,application/n3,text/n3,application/rdf+xml,application/n-triples,
          multipart/form-data,application/sparql-update']]);
    }
}
