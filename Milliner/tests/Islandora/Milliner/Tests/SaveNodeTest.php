<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Class MillinerServiceTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class SaveNodeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $modifiedDatePredicate;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $fedoraBaseUrl;

    /**
     * @var Islandora\Crayfish\Commons\EntityMapper\EntityMapper
     */
    protected $mapper;

    /**
     * @var Islandora\Crayfish\Commons\EntityMapper\EntityMapper
     */
    protected $drupal;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());

        $this->modifiedDatePredicate = "http://schema.org/dateModified";

        $this->uuid = '9541c0c1-5bee-4973-a9d0-e55c1658bc8';
        $this->fedoraBaseUrl = 'http://localhost:8080/fcrepo/rest';

        $this->mapper = $this->prophesize(EntityMapperInterface::class);
        $this->mapper->getFedoraPath($this->uuid)
            ->willReturn("{$this->fedoraBaseUrl}/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc8");
        $this->mapper = $this->mapper->reveal();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Content.jsonld')
        );
        $this->drupal = $this->prophesize(Client::class);
        $this->drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $this->drupal = $this->drupal->reveal();
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::processJsonld
     */
    public function testCreateNodeThrowsOnFedoraError()
    {
        $fedora_head_response = new Response(404);
        $fedora_save_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $this->expectException(\RuntimeException::class, null, 403);

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
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
        $fedora_head_response = new Response(404);
        $fedora_save_response = new Response(201);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
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
        $fedora_head_response = new Response(404);
        $fedora_save_response = new Response(204);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
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
     */
    public function testUpdateNodeThrowsOnFedoraError()
    {
        $fedora_head_response = new Response(200);
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_save_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $this->expectException(\RuntimeException::class, null, 403);

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
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
    public function testUpdateNodeThrows500OnBadDatePredicate()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/StaleContent.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_head_response = new Response(200);
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
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
    public function testUpdateNodeThrows412OnStaleContent()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/StaleContent.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora_head_response = new Response(200);
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $this->expectException(\RuntimeException::class, null, 412);

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
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
        $fedora_head_response = new Response(200);
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_save_response = new Response(201);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
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
        $fedora_head_response = new Response(200);
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_save_response = new Response(204);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResourceHeaders(Argument::any())
            ->willReturn($fedora_head_response);
        $fedora->getResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_save_response);
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }
}
