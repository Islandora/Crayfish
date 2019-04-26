<?php

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\Chullo;
use Islandora\Chullo\FedoraApi;

class GetGraphTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers  Islandora\Chullo\Chullo::getGraph
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsContentOn200()
    {
        $fixture = <<<EOD
            [ {
              "@id" : "http://127.0.0.1:8080/fcrepo/rest/4d/8b/2d/8e/4d8b2d8e-d063-4c9f-aac9-6b285b193ed6",
              "@type" : [ "http://www.w3.org/ns/ldp#RDFSource", "http://www.w3.org/ns/ldp#Container",
"http://www.jcp.org/jcr/nt/1.0folder", "http://www.jcp.org/jcr/nt/1.0hierarchyNode", 
"http://www.jcp.org/jcr/nt/1.0base", "http://www.jcp.org/jcr/mix/1.0created", 
"http://fedora.info/definitions/v4/repository#Container", "http://fedora.info/definitions/v4/repository#Resource", 
"http://www.jcp.org/jcr/mix/1.0lastModified", "http://www.jcp.org/jcr/mix/1.0referenceable" ],
              "http://fedora.info/definitions/v4/repository#created" : [ {
                "@type" : "http://www.w3.org/2001/XMLSchema#dateTime",
                "@value" : "2015-10-03T02:14:34.391Z"
              } ],
              "http://fedora.info/definitions/v4/repository#createdBy" : [ {
                "@value" : "bypassAdmin"
              } ],
              "http://fedora.info/definitions/v4/repository#exportsAs" : [ {
                "@id" : 
"http://127.0.0.1:8080/fcrepo/rest/4d/8b/2d/8e/4d8b2d8e-d063-4c9f-aac9-6b285b193ed6/fcr:export?format=jcr/xml"
              } ],
              "http://fedora.info/definitions/v4/repository#hasParent" : [ {
                "@id" : "http://127.0.0.1:8080/fcrepo/rest/"
              } ],
              "http://fedora.info/definitions/v4/repository#lastModified" : [ {
                "@type" : "http://www.w3.org/2001/XMLSchema#dateTime",
                "@value" : "2015-10-03T02:14:34.631Z"
              } ],
              "http://fedora.info/definitions/v4/repository#lastModifiedBy" : [ {
                "@value" : "bypassAdmin"
              } ],
              "http://fedora.info/definitions/v4/repository#mixinTypes" : [ {
                "@value" : "fedora:Container"
              }, {
                "@value" : "fedora:Resource"
              } ],
              "http://fedora.info/definitions/v4/repository#primaryType" : [ {
                "@value" : "nt:folder"
              } ],
              "http://fedora.info/definitions/v4/repository#writable" : [ {
                "@type" : "http://www.w3.org/2001/XMLSchema#boolean",
                "@value" : "true"
              } ],
              "http://purl.org/dc/terms/title" : [ {
                "@value" : "My Sweet Title"
              } ]
            }, {
              "@id" : 
"http://127.0.0.1:8080/fcrepo/rest/4d/8b/2d/8e/4d8b2d8e-d063-4c9f-aac9-6b285b193ed6/fcr:export?format=jcr/xml",
              "http://purl.org/dc/elements/1.1/format" : [ {
                "@id" : "http://fedora.info/definitions/v4/repository#jcr/xml"
              } ]
            } ]
EOD;
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ld+json'], $fixture),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        $graph = $client->getGraph();
        $title = (string)$graph->get(
            "http://127.0.0.1:8080/fcrepo/rest/4d/8b/2d/8e/4d8b2d8e-d063-4c9f-aac9-6b285b193ed6",
            "dc:title"
        );
        $this->assertSame($title, "My Sweet Title");
    }

    /**
     * @covers  Islandora\Chullo\Chullo::getGraph
     * @uses    GuzzleHttp\Client
     */
    public function testReturnsNullOtherwise()
    {
        $mock = new MockHandler([
            new Response(304),
            new Response(404),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);
        $api = new FedoraApi($guzzle);
        $client = new Chullo($api);

        // 304
        $result = $client->getGraph("");
        $this->assertNull($result);

        //404
        $result = $client->getGraph("");
        $this->assertNull($result);
    }
}
