<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Islandora\Milliner\Gemini\GeminiClient;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * Class GeminiClientTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Gemini\GeminiClient
 */
class GeminiClientTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());
    }

    /**
     * @covers ::__construct
     * @covers ::getUrls
     */
    public function testGetUrls()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn('{"drupal" : "foo", "fedora": "bar"}');
        $response = $response->reveal();

        $client = $this->prophesize(Client::class);
        $client->get(Argument::any(), Argument::any())->willReturn($response);
        $client = $client->reveal();

        $gemini = new GeminiClient(
            $client,
            $this->logger
        );

        $out = $gemini->getUrls("abc123");
        $this->assertTrue(
            $out['drupal'] == 'foo',
            "Gemini client must return response unaltered.  Expected 'foo' but received {$out['drupal']}"
        );
        $this->assertTrue(
            $out['fedora'] == 'bar',
            "Gemini client must return response unaltered.  Expected 'bar' but received {$out['fedora']}"
        );

        $out = $gemini->getUrls("abc123", "some_token");
        $this->assertTrue(
            $out['drupal'] == 'foo',
            "Gemini client must return response unaltered.  Expected 'foo' but received {$out['drupal']}"
        );
        $this->assertTrue(
            $out['fedora'] == 'bar',
            "Gemini client must return response unaltered.  Expected 'bar' but received {$out['fedora']}"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getUrls
     */
    public function testGetUrlsReturnsEmptyArrayWhenNotFound()
    {
        $request = $this->prophesize(RequestInterface::class)->reveal();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(404);
        $response = $response->reveal();

        $client = $this->prophesize(Client::class);
        $client->get(Argument::any(), Argument::any())->willThrow(
            new RequestException("Not Found", $request, $response)
        );
        $client = $client->reveal();

        $gemini = new GeminiClient(
            $client,
            $this->logger
        );

        $this->assertTrue(
            empty($gemini->getUrls("abc123")),
            "Gemini client must return empty array if not found"
        );
        $this->assertTrue(
            empty($gemini->getUrls("abc123", "some_token")),
            "Gemini client must return empty array if not found"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getUrls
     * @expectedException \GuzzleHttp\Exception\RequestException
     */
    public function testGetUrlsException()
    {
        $request = $this->prophesize(RequestInterface::class)->reveal();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(500);
        $response = $response->reveal();

        $client = $this->prophesize(Client::class);
        $client->get(Argument::any(), Argument::any())->willThrow(
            new RequestException("Server Error", $request, $response)
        );
        $client = $client->reveal();

        $gemini = new GeminiClient(
            $client,
            $this->logger
        );
        $gemini->getUrls("abc123");
    }

    /**
     * @covers ::__construct
     * @covers ::mintFedoraUrl
     */
    public function testMintFedoraUrl()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn("http://foo.com/bar");
        $response = $response->reveal();

        $client = $this->prophesize(Client::class);
        $client->post(Argument::any(), Argument::any())->willReturn($response);
        $client = $client->reveal();

        $gemini = new GeminiClient(
            $client,
            $this->logger
        );

        $url = $gemini->mintFedoraUrl("abc123");
        $this->assertTrue(
            $url == "http://foo.com/bar",
            "Gemini client must return response unaltered.  Expected 'http://foo.com/bar' but received $url"
        );

        $url = $gemini->mintFedoraUrl("abc123", "some_token");
        $this->assertTrue(
            $url == "http://foo.com/bar",
            "Gemini client must return response unaltered.  Expected 'http://foo.com/bar' but received $url"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveUrls
     */
    public function testSaveUrls()
    {
        $client = $this->prophesize(Client::class)->reveal();

        $gemini = new GeminiClient(
            $client,
            $this->logger
        );

        $out = $gemini->saveUrls("abc123", "foo", "bar");
        $this->assertTrue(
            $out,
            "Gemini client must return true on successful saveUrls().  Received $out"
        );

        $out = $gemini->saveUrls("abc123", "foo", "bar", "some_token");
        $this->assertTrue(
            $out,
            "Gemini client must return true on successful saveUrls().  Received $out"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::deleteUrls
     */
    public function testDeleteUrls()
    {
        $client = $this->prophesize(Client::class)->reveal();

        $gemini = new GeminiClient(
            $client,
            $this->logger
        );

        $out = $gemini->deleteUrls("abc123");
        $this->assertTrue(
            $out,
            "Gemini client must return true on successful deleteUrls().  Received $out"
        );

        $out = $gemini->deleteUrls("abc123", "some_token");
        $this->assertTrue(
            $out,
            "Gemini client must return true on successful deleteUrls().  Received $out"
        );
    }

    /**
     * @covers ::create
     */
    public function testCreateClient()
    {
        $client = GeminiClient::create('http://example.org', $this->logger);
        $this->assertTrue($client instanceof GeminiClient, 'Must get back a GeminiClient instance');
    }
}
