<?php

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\TriplestoreClient;

class TriplestoreClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers  Islandora\Chullo\TriplestoreClient::query
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsResultsOn200()
    {
        $mock_response = <<<EOD
            {
              "head" : {
                "vars" : [ "s" ]
              },
              "results" : {
                "bindings" : [ {
                  "s" : {
                    "type" : "uri",
                    "value" : "http://localhost:8080/fcrepo/rest/f6/d8/3f/01/f6d83f01-13b3-4638-b806-3aba9402937a"
                  }
                } ]
              }
            }
EOD;
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/sparql-results+json'], $mock_response),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler,
          'base_uri' => 'http://127.0.0.1:8080/bigdata/namespace/kb/sparql/']);
        $client = new TriplestoreClient($guzzle);

        $result = $client->query("");

        $this->assertInstanceOf("EasyRdf_Sparql_Result", $result);
    }

    /**
     * @covers  Islandora\Chullo\TriplestoreClient::query
     * @uses              GuzzleHttp\Client
     * @expectedException GuzzleHttp\Exception\ClientException
     */
    public function testThrowsExceptionOn400()
    {
        $mock = new MockHandler([
            new Response(400),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler,
          'base_uri' => 'http://127.0.0.1:8080/bigdata/namespace/kb/sparql/']);
        $client = new TriplestoreClient($guzzle);

        $result = $client->query("");
    }
}
