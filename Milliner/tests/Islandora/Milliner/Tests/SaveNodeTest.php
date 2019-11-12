<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * Class MillinerServiceTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class SaveNodeTest extends TestCase
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
     * @covers ::__construct
     * @covers ::saveNode
     * @expectedException \GuzzleHttp\Exception\RequestException
     * @expectedExceptionCode 403
     */
    public function testCreateNodeThrowsOnMintError()
    {
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn([]);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any())
            ->willThrow(
                new RequestException(
                    "Unauthorized",
                    new Request('POST', 'http://localhost:8000/gemini'),
                    new Response(403, [], "Unauthorized")
                )
            );
        $gemini = $gemini->reveal();

        $drupal = $this->prophesize(Client::class);
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::processJsonld
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
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::createNode
     * @covers ::processJsonld
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testCreateNodeThrowsOnFedoraSaveError()
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

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_save_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraApi::class);
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
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
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
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Milliner must return 201 when Fedora returns 201.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::createNode
     * @covers ::processJsonld
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
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
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
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
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
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            "total garbage",
            false
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
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
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
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
        $fedora = $this->prophesize(IFedoraApi::class);
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
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
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
        $fedora = $this->prophesize(IFedoraApi::class);
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
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Milliner must return 201 when Fedora returns 201.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
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
        $fedora = $this->prophesize(IFedoraApi::class);
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
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->saveNode(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "http://localhost:8000/node/1?_format=jsonld",
            $token = "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }
}
