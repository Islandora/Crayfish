<?php

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\Chullo;
use Islandora\Chullo\FedoraApi;

class GetResourceHeadersTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers  Islandora\Chullo\Chullo::getResourceHeaders
     * @covers  Islandora\Chullo\FedoraApi::getResourceHeaders
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsHeadersOn200()
    {
        $mock = new MockHandler([
          new Response(200, ['Status: 200 OK', 'ETag: "bbdd92e395800153a686773f773bcad80a51f47b"',
          'Last-Modified: Wed, 28 May 2014 18:31:36 GMT', 'Last-Modified: Thu, 20 Nov 2014 15:44:32 GMT',
          'Link: <http://www.w3.org/ns/ldp#Resource>;rel="type"',
          'Link: <http://www.w3.org/ns/ldp#Container>;rel="type"',
          'Link: <http://www.w3.org/ns/ldp#BasicContainer>;rel="type"', 'Accept-Patch: application/sparql-update',
          'Accept-Post: text/turtle,text/rdf+n3,text/n3,application/rdf+xml,application/n-triples,multipart/form-data,'
          . 'application/sparql-update', 'Allow: MOVE,COPY,DELETE,POST,HEAD,GET,PUT,PATCH,OPTIONS']),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        $result = $client->getResourceHeaders("");
        $this->assertSame((array)$result, [['Status: 200 OK'], ['ETag: "bbdd92e395800153a686773f773bcad80a51f47b"'],
          ['Last-Modified: Wed, 28 May 2014 18:31:36 GMT'], ['Last-Modified: Thu, 20 Nov 2014 15:44:32 GMT'],
          ['Link: <http://www.w3.org/ns/ldp#Resource>;rel="type"'],
          ['Link: <http://www.w3.org/ns/ldp#Container>;rel="type"'],
          ['Link: <http://www.w3.org/ns/ldp#BasicContainer>;rel="type"'], ['Accept-Patch: application/sparql-update'],
          ['Accept-Post: text/turtle,text/rdf+n3,text/n3,application/rdf+xml,application/n-triples,'
          . 'multipart/form-data,application/sparql-update'],
          ['Allow: MOVE,COPY,DELETE,POST,HEAD,GET,PUT,PATCH,OPTIONS']]);
    }

    /**
     * @covers            Islandora\Chullo\Chullo::getResourceHeaders
     * @covers  Islandora\Chullo\FedoraApi::getResourceHeaders
     * @uses              GuzzleHttp\Client
     */
    public function testReturnsNullOtherwise()
    {
        $mock = new MockHandler([
            new Response(404),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        $result = $client->getResourceHeaders("");
        $this->assertNull($result);
    }
}
