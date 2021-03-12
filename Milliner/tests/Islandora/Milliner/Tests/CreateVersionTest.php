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
class CreateVersionTest extends TestCase
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

        $this->drupal = $this->prophesize(Client::class);
        $this->drupal = $this->drupal->reveal();
    }


    /**
     * @covers ::__construct
     * @covers ::createVersion
     */
    public function testCreateVersionReturnsFedora201()
    {
        $fedora_response = new Response(201);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
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

        $response = $milliner->createVersion(
            $this->uuid,
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
     * @covers ::createVersion
     */

    public function testCreateVersionReturnsFedora404()
    {
        $fedora_response = new Response(404);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $this->expectException(\RuntimeException::class, null, 404);

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $response = $milliner->createVersion(
            $this->uuid,
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 404,
            "Milliner must return 404 when Fedora returns 404.  Received: $status"
        );
    }


    /**
     * @covers ::__construct
     * @covers ::createVersion
     */
    public function testcreateVersionThrowsOnFedoraSaveError()
    {
        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentLDP-RS.jsonld')
        );
        $fedora_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_response);
        $fedora = $fedora->reveal();

        $this->expectException(\RuntimeException::class, null, 403);

        $milliner = new MillinerService(
            $fedora,
            $this->drupal,
            $this->mapper,
            $this->logger,
            $this->modifiedDatePredicate,
            false,
            false
        );

        $response = $milliner->createVersion(
            $this->uuid,
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Milliner must return 403 when Fedora returns 403.  Received: $status"
        );
    }
}
