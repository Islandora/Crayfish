<?php

namespace Islandora\Milliner\Tests;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7\Response;
use Islandora\Milliner\Converter\DrupalEntityConverter;
use Prophecy\Prophet;

/**
 * Class DrupalEntityConverterTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Converter\DrupalEntityConverter
 */
class DrupalEntityConverterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $client_prophecy;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $logger_prophecy;

    /**
     * @var \Prophecy\Prophet
     */
    private $prophet;

    /**
     * @var \Islandora\Milliner\Converter\DrupalEntityConverter
     */
    protected $entity_converter;

    public function setUp()
    {
        parent::setUp();
        $this->prophet = new Prophet;
        $this->client_prophecy = $this->prophet->prophesize('GuzzleHttp\Client');
        $this->logger_prophecy = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    }

    /**
     * @covers ::__construct
     * @covers ::convert
     */
    public function testConverterWithAuth()
    {
        $token = "Bearer token";
        $options = [
            'http_errors' => false,
            'headers' => [
                'Authorization' => $token,
            ],
        ];
        $drupal_path = "drupal/fedora_resource";
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

        $this->client_prophecy->get($drupal_path . '?_format=jsonld', $options)->willReturn(new Response(
            200,
            [
                "Content-type" => "application/ld+json",
                "Content-length" => strlen($drupal_jsonld),
            ],
            $drupal_jsonld
        ));

        $client = $this->client_prophecy->reveal();
        $logger = $this->logger_prophecy->reveal();
        $this->entity_converter = new DrupalEntityConverter($client, $logger);

        $request = Request::create("/metadata/{$drupal_path}", "GET");
        $request->headers->set("Authorization", $token);

        $response = $this->entity_converter->convertJsonld($drupal_path, $request);

        $this->assertJsonStringEqualsJsonString(
            $drupal_jsonld,
            $response->getBody()->getContents(),
            "Body doesn't match"
        );
        $this->assertEquals(200, $response->getStatusCode(), "Bad response code");
    }
}
