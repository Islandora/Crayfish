<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Psr7\Response;
use Islandora\Milliner\Service\MillinerService;
use Prophecy\Prophet;

/**
 * Class MillinerServiceTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class MillinerServiceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $fedora_api_prophecy;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $path_mapper_prophecy;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $logger_prophecy;

    /**
     * @var \Prophecy\Prophet
     */
    private $prophet;

    /**
     * @var \Islandora\Milliner\Service\MillinerServiceInterface
     */
    protected $milliner;

    public function setUp()
    {
        parent::setUp();
        $this->prophet = new Prophet;

        $this->fedora_api_prophecy = $this->prophet->prophesize('Islandora\Chullo\FedoraApi');
        $this->path_mapper_prophecy = $this->prophet->prophesize(
            'Islandora\Crayfish\Commons\PathMapper\PathMapper'
        );
        $this->logger_prophecy = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    }

    /**
     * @covers ::__construct
     * @covers ::createRdf
     * @covers ::processJsonLd
     */
    public function testCreateOk()
    {
        $drupal_path = "new/fedora/path";
        $escaped_path = addslashes($drupal_path);
        $drupal_jsonld =<<<EOF
{"@graph":[{"@id":"http:\/\/localhost:8000\/{$escaped_path}?_format=jsonld",
"@type":["http:\/\/schema.org\/Thing","http:\/\/www.w3.org\/ns\/ldp#RDFSource","http:\/\/www.w3.org\/ns\/ldp#Container"
],"http:\/\/schema.org\/author":[{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld"}],
"http:\/\/purl.org\/dc\/elements\/1.1\/title":[{"@value":"This is the final test"}],
"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#label":[{"@value":"This is the final test"}],
"http:\/\/schema.org\/dateCreated":[{"@value":"2017-04-25T21:45:32+00:00"}],
"http:\/\/schema.org\/dateModified":[{"@value":"2017-04-25T21:45:32+00:00"}]},
{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld","@type":["http:\/\/schema.org\/Person"]}]}
EOF;
        // Using newlines to fit in PSR2, but removing to make JSON easier to match.
        $drupal_jsonld = str_replace("\n", "", $drupal_jsonld);

        $fedora_jsonld =<<<EOF
[{"@id":"","@type":["http:\/\/schema.org\/Thing","http:\/\/www.w3.org\/ns\/ldp#RDFSource",
"http:\/\/www.w3.org\/ns\/ldp#Container"],"http:\/\/schema.org\/author":[{"@id":
"http:\/\/localhost:8000\/user\/1?_format=jsonld"}],"http:\/\/purl.org\/dc\/elements\/1.1\/title":[
{"@value":"This is the final test"}],"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#label":[
{"@value":"This is the final test"}],"http:\/\/schema.org\/dateCreated":[{"@value":"2017-04-25T21:45:32+00:00"}],
"http:\/\/schema.org\/dateModified":[{"@value":"2017-04-25T21:45:32+00:00"}]}]
EOF;
        $fedora_jsonld = str_replace("\n", "", $fedora_jsonld);
        $token = "Bearer token";
        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/ld+json',
        ];
        $fedora_path = "fedora/00/11/22/new";
        $this->path_mapper_prophecy->getFedoraPath($drupal_path)->willReturn(null);
        $this->fedora_api_prophecy->createResource('', $fedora_jsonld, $headers)->willReturn(
            new Response(
                201,
                [
                        'Content-type' => 'text/plain'
                    ],
                $fedora_path
            )
        );

        $api = $this->fedora_api_prophecy->reveal();
        $path_map = $this->path_mapper_prophecy->reveal();
        $logger = $this->logger_prophecy->reveal();
        $this->milliner = new MillinerService($api, $path_map, $logger);

        $response = $this->milliner->createRdf($drupal_jsonld, $drupal_path, $token);
        $this->assertEquals(201, $response->getStatusCode(), "Expected created code");
        $this->assertEquals($fedora_path, $response->getBody()->getContents(), "Expected body");
    }

    /**
     * @covers ::__construct
     * @covers ::createRdf
     * @expectedException \RuntimeException
     * @expectedExceptionCode 409
     */
    public function testCreateError()
    {
        $drupal_path = "new/fedora/path";
        $existing_fedora_resource = "fedora/existing/path";
        $token = "Bearer token";
        $escaped_path = addslashes($drupal_path);
        $drupal_jsonld =<<<EOF
{"@graph":[{"@id":"http:\/\/localhost:8000\/{$escaped_path}?_format=jsonld",
"@type":["http:\/\/schema.org\/Thing","http:\/\/www.w3.org\/ns\/ldp#RDFSource","http:\/\/www.w3.org\/ns\/ldp#Container"
],"http:\/\/schema.org\/author":[{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld"}],
"http:\/\/purl.org\/dc\/elements\/1.1\/title":[{"@value":"This is the final test"}],
"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#label":[{"@value":"This is the final test"}],
"http:\/\/schema.org\/dateCreated":[{"@value":"2017-04-25T21:45:32+00:00"}],
"http:\/\/schema.org\/dateModified":[{"@value":"2017-04-25T21:45:32+00:00"}]},
{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld","@type":["http:\/\/schema.org\/Person"]}]}
EOF;
        // Using newlines to fit in PSR2, but removing to make JSON easier to match.
        $drupal_jsonld = str_replace("\n", "", $drupal_jsonld);

        $this->path_mapper_prophecy->getFedoraPath($drupal_path)->willReturn($existing_fedora_resource);

        $api = $this->fedora_api_prophecy->reveal();
        $path_map = $this->path_mapper_prophecy->reveal();
        $logger = $this->logger_prophecy->reveal();
        $this->milliner = new MillinerService($api, $path_map, $logger);

        $this->milliner->createRdf($drupal_jsonld, $drupal_path, $token);
    }

    /**
     * @covers ::__construct
     * @covers ::updateRdf
     * @covers ::processJsonLd
     */
    public function testUpdateOk()
    {
        $drupal_path = "drupal/fedora/path";
        $fedora_path = "fedora/00/11/22/new";
        $escaped_path = addslashes($drupal_path);
        $drupal_jsonld =<<<EOF
{"@graph":[{"@id":"http:\/\/localhost:8000\/{$escaped_path}?_format=jsonld",
"@type":["http:\/\/schema.org\/Thing","http:\/\/www.w3.org\/ns\/ldp#RDFSource","http:\/\/www.w3.org\/ns\/ldp#Container"
],"http:\/\/schema.org\/author":[{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld"}],
"http:\/\/purl.org\/dc\/elements\/1.1\/title":[{"@value":"This is the final test"}],
"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#label":[{"@value":"This is the final test"}],
"http:\/\/schema.org\/dateCreated":[{"@value":"2017-04-25T21:45:32+00:00"}],
"http:\/\/schema.org\/dateModified":[{"@value":"2017-04-25T21:45:32+00:00"}]},
{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld","@type":["http:\/\/schema.org\/Person"]}]}
EOF;
        // Using newlines to fit in PSR2, but removing to make JSON easier to match.
        $drupal_jsonld = str_replace("\n", "", $drupal_jsonld);

        $fedora_jsonld =<<<EOF
[{"@id":"","@type":["http:\/\/schema.org\/Thing","http:\/\/www.w3.org\/ns\/ldp#RDFSource",
"http:\/\/www.w3.org\/ns\/ldp#Container"],"http:\/\/schema.org\/author":[{"@id":
"http:\/\/localhost:8000\/user\/1?_format=jsonld"}],"http:\/\/purl.org\/dc\/elements\/1.1\/title":[
{"@value":"This is the final test"}],"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#label":[
{"@value":"This is the final test"}],"http:\/\/schema.org\/dateCreated":[{"@value":"2017-04-25T21:45:32+00:00"}],
"http:\/\/schema.org\/dateModified":[{"@value":"2017-04-25T21:45:32+00:00"}]}]
EOF;
        $fedora_jsonld = str_replace("\n", "", $fedora_jsonld);
        $token = "Bearer token";
        $etag = substr(md5($fedora_jsonld), 0, 20);

        $return_headers = [
            'Content-Type' => 'text/turtle',
            'ETag' => $etag,
            'Content-Length' => strlen($fedora_jsonld),
        ];


        $this->path_mapper_prophecy->getFedoraPath($drupal_path)->willReturn($fedora_path);
        $this->fedora_api_prophecy->getResourceHeaders($fedora_path, ['Authorization' => $token])
            ->willReturn(new Response(200, $return_headers));

        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/ld+json',
            'If-Match' => $etag,
            'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
        ];
        $this->fedora_api_prophecy->saveResource($fedora_path, $fedora_jsonld, $headers)->willReturn(
            new Response(204, ['Content-type' => 'text/plain'])
        );

        $api = $this->fedora_api_prophecy->reveal();
        $path_map = $this->path_mapper_prophecy->reveal();
        $logger = $this->logger_prophecy->reveal();
        $this->milliner = new MillinerService($api, $path_map, $logger);

        $response = $this->milliner->updateRdf($drupal_jsonld, $drupal_path, $token);
        $this->assertEquals(204, $response->getStatusCode(), "Expected created code");
        $this->assertEquals('', $response->getBody()->getContents(), "Did not expect a body");
    }

    /**
     * @covers ::__construct
     * @covers ::updateRdf
     * @expectedExceptionCode 404
     * @expectedException \RuntimeException
     */
    public function testUpdateError()
    {
        $drupal_path = "drupal/fedora/path";
        $escaped_path = addslashes($drupal_path);
        $drupal_jsonld =<<<EOF
{"@graph":[{"@id":"http:\/\/localhost:8000\/{$escaped_path}?_format=jsonld",
"@type":["http:\/\/schema.org\/Thing","http:\/\/www.w3.org\/ns\/ldp#RDFSource","http:\/\/www.w3.org\/ns\/ldp#Container"
],"http:\/\/schema.org\/author":[{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld"}],
"http:\/\/purl.org\/dc\/elements\/1.1\/title":[{"@value":"This is the final test"}],
"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#label":[{"@value":"This is the final test"}],
"http:\/\/schema.org\/dateCreated":[{"@value":"2017-04-25T21:45:32+00:00"}],
"http:\/\/schema.org\/dateModified":[{"@value":"2017-04-25T21:45:32+00:00"}]},
{"@id":"http:\/\/localhost:8000\/user\/1?_format=jsonld","@type":["http:\/\/schema.org\/Person"]}]}
EOF;
        // Using newlines to fit in PSR2, but removing to make JSON easier to match.
        $drupal_jsonld = str_replace("\n", "", $drupal_jsonld);

        $token = "Bearer token";

        $this->path_mapper_prophecy->getFedoraPath($drupal_path)->willReturn(null);

        $api = $this->fedora_api_prophecy->reveal();
        $path_map = $this->path_mapper_prophecy->reveal();
        $logger = $this->logger_prophecy->reveal();
        $this->milliner = new MillinerService($api, $path_map, $logger);

        $this->milliner->updateRdf($drupal_jsonld, $drupal_path, $token);
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     */
    public function testDeleteOk()
    {
        $drupal_path = "drupal/fedora/path";
        $fedora_path = "fedora/00/11/22/new";
        $token = "Bearer token";

        $headers = [
            "Authorization" => $token,
        ];

        $this->path_mapper_prophecy->getFedoraPath($drupal_path)->willReturn($fedora_path);
        $this->fedora_api_prophecy->deleteResource($fedora_path, $headers)->willReturn(
            new Response(204, ['Content-type' => 'text/plain'])
        );

        $api = $this->fedora_api_prophecy->reveal();
        $path_map = $this->path_mapper_prophecy->reveal();
        $logger = $this->logger_prophecy->reveal();
        $this->milliner = new MillinerService($api, $path_map, $logger);

        $response = $this->milliner->delete($drupal_path, $token);
        $this->assertEquals(204, $response->getStatusCode(), "Incorrect response code");
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @expectedExceptionCode 404
     * @expectedException \RuntimeException
     */
    public function testDeleteError()
    {
        $drupal_path = "drupal/fedora/path";
        $token = "Bearer token";

        $this->path_mapper_prophecy->getFedoraPath($drupal_path)->willReturn(null);

        $api = $this->fedora_api_prophecy->reveal();
        $path_map = $this->path_mapper_prophecy->reveal();
        $logger = $this->logger_prophecy->reveal();
        $this->milliner = new MillinerService($api, $path_map, $logger);

        $this->milliner->delete($drupal_path, $token);
    }
}
