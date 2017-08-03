<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraClient;
use Islandora\Milliner\Gemini\GeminiClient;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Prophecy\Argument;

/**
 * Class MillinerServiceTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class SaveFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $modifiedDatePredicate;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());

        $this->modifiedDatePredicate = "http://schema.org/dateModified";
    }

    /**
     * @covers ::saveFile
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testCreateFileThrowsFedoraPutError()
    {
        $url = "http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198";
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn([]);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any())
            ->willReturn($url);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            "CONTENT"
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveFile
     */
    public function testCreateFileReturnsFedora201()
    {
        $url = "http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198";
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn([]);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any())
            ->willReturn($url);
        $gemini->saveUrls(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(true);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            "CONTENT"
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_response = new Response(201);
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $response = $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Milliner must return 201 when Fedora returns 201.  Received: $status"
        );
    }

    /**
     * @covers ::saveFile
     */
    public function testCreateFileReturnsFedora204()
    {
        $url = "http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198";
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn([]);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any())
            ->willReturn($url);
        $gemini->saveUrls(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(true);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            "CONTENT"
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_response = new Response(204);
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $response = $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::saveFile
     * @expectedException \RuntimeException
     * @expectedExceptionCode 404
     */
    public function testUpdateFileThrowsFedoraHeadError()
    {
        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/fedora_logo.png',
            'fedora' => 'http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $fedora_response = new Response(404);
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveFile
     * @expectedException \RuntimeException
     * @expectedExceptionCode 500
     */
    public function testUpdateFileThrows500WhenNoDescribedby()
    {
        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/fedora_logo.png',
            'fedora' => 'http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $fedora_response = new Response(
            200,
            ['ETag' => 'abc123']
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveFile
     * @expectedException \RuntimeException
     * @expectedExceptionCode 404
     */
    public function testUpdateFileThrowsFedoraGetError()
    {
        $fedora_url = 'http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198';
        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/fedora_logo.png',
            'fedora' => $fedora_url,
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $fedora_head_response = new Response(
            200,
            ['Link' => "<$fedora_url/fcr:metadata>; rel=\"describedby\"", 'ETag' => 'abc123']
        );
        $fedora_get_response = new Response(
            404
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveFile
     * @expectedException \RuntimeException
     * @expectedExceptionCode 500
     */
    public function testUpdateFileThrows500WhenNoDigest()
    {
        $fedora_url = 'http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198';
        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/fedora_logo.png',
            'fedora' => $fedora_url,
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $fedora_head_response = new Response(
            200,
            ['Link' => "<$fedora_url/fcr:metadata>; rel=\"describedby\"", 'ETag' => 'abc123']
        );
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS-NoDigest.jsonld')
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveFile
     * @expectedException \RuntimeException
     * @expectedExceptionCode 412
     */
    public function testUpdateFileThrows412WithChecksumMatch()
    {
        $fedora_url = 'http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198';
        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/fedora_logo.png',
            'fedora' => $fedora_url,
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $fedora_head_response = new Response(
            200,
            ['Link' => "<$fedora_url/fcr:metadata>; rel=\"describedby\"", 'ETag' => 'abc123']
        );
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS.jsonld')
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $checksum_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/StaleChecksumResponse.json')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get("http://localhost:8000/checksum/29?_format=json", Argument::any(), Argument::any())
            ->willReturn($checksum_response);
        $drupal = $drupal->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveFile
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testUpdateFileThrowsFedoraPutError()
    {
        $fedora_url = 'http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198';
        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/fedora_logo.png',
            'fedora' => $fedora_url,
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $fedora_head_response = new Response(
            200,
            ['Link' => "<$fedora_url/fcr:metadata>; rel=\"describedby\"", 'ETag' => 'abc123']
        );
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS.jsonld')
        );
        $fedora_put_response = new Response(
            403
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_put_response);
        $fedora = $fedora->reveal();

        $checksum_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/ChecksumResponse.json')
        );
        $get_response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            "CONTENT"
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get("http://localhost:8000/checksum/29?_format=json", Argument::any(), Argument::any())
            ->willReturn($checksum_response);
        $drupal->get("http://localhost:8000/sites/default/files/2017-07/fedora_logo.png", Argument::any(), Argument::any())
            ->willReturn($get_response);
        $drupal = $drupal->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveFile
     */
    public function testUpdateFileReturnsFedora204()
    {
        $fedora_url = 'http://localhost:8080/fcrepo/rest/fc/c3/12/25/fcc31225-51d3-46e8-add3-f4e33b456198';
        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/fedora_logo.png',
            'fedora' => $fedora_url,
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini->saveUrls(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(true);
        $gemini = $gemini->reveal();

        $fedora_head_response = new Response(
            200,
            ['Link' => "<$fedora_url/fcr:metadata>; rel=\"describedby\"", 'ETag' => 'abc123']
        );
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS.jsonld')
        );
        $fedora_put_response = new Response(
            204
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_put_response);
        $fedora = $fedora->reveal();

        $checksum_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/ChecksumResponse.json')
        );
        $get_response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            "CONTENT"
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get("http://localhost:8000/checksum/29?_format=json", Argument::any(), Argument::any())
            ->willReturn($checksum_response);
        $drupal->get("http://localhost:8000/sites/default/files/2017-07/fedora_logo.png", Argument::any(), Argument::any())
            ->willReturn($get_response);
        $drupal = $drupal->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $response = $milliner->saveFile(
            "fcc31225-51d3-46e8-add3-f4e33b456198",
            "http://localhost:8000/sites/default/files/2017-07/fedora_logo.png",
            "http://localhost:8000/checksum/29?_format=json",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }
}
