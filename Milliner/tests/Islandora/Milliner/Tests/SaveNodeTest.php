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
class SaveNodeTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::saveNode
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testCreateNodeThrowsOnFedoraError()
    {
        $url = "http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8";
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn([]);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any())
            ->willReturn($url);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Content.jsonld')
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

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveNode
     */
    public function testCreateNodeReturnsFedora201()
    {
        $url = "http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8";
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
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Content.jsonld')
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

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Milliner must return 201 when Fedora returns 201.  Received: $status"
        );
    }

    /**
     * @covers ::saveNode
     */
    public function testCreateNodeReturnsFedora204()
    {
        $url = "http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8";
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
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Content.jsonld')
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

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::saveNode
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testUpdateNodeThrowsOnFedoraGetError()
    {
        $mapping = [
            'drupal' => '"http://localhost:8000/node/1?_format=jsonld"',
            'fedora' => 'http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8'
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $fedora_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveNode
     * @expectedException \RuntimeException
     * @expectedExceptionCode 500
     */
    public function testUpdateNodeThrows500OnBadDatePredicate()
    {
        $mapping = [
            'drupal' => '"http://localhost:8000/node/1?_format=jsonld"',
            'fedora' => 'http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8'
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/StaleContent.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            "total garbage"
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveNode
     * @expectedException \RuntimeException
     * @expectedExceptionCode 412
     */
    public function testUpdateNodeThrows412OnStaleContent()
    {
        $mapping = [
            'drupal' => '"http://localhost:8000/node/1?_format=jsonld"',
            'fedora' => 'http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8'
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/StaleContent.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveNode
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testUpdateNodeThrowsOnFedoraSaveError()
    {
        $mapping = [
            'drupal' => '"http://localhost:8000/node/1?_format=jsonld"',
            'fedora' => 'http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8'
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Content.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_save_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::saveNode
     */
    public function testUpdateNodeReturnsFedora201()
    {
        $mapping = [
            'drupal' => '"http://localhost:8000/node/1?_format=jsonld"',
            'fedora' => 'http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8'
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini->saveUrls(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(true);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Content.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_save_response = new Response(201);
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Milliner must return 201 when Fedora returns 201.  Received: $status"
        );
    }

    /**
     * @covers ::saveNode
     */
    public function testUpdateNodeReturnsFedora204()
    {
        $mapping = [
            'drupal' => '"http://localhost:8000/node/1?_format=jsonld"',
            'fedora' => 'http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8'
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini->saveUrls(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(true);
        $gemini = $gemini->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Content.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_save_response = new Response(204);
        $fedora = $this->prophesize(IFedoraClient::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate
        );

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }
}
