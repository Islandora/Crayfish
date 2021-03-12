<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Class MillinerServiceTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class DeleteTest extends TestCase
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
     * @covers ::deleteNode
     */
    public function testDeleteThrowsFedoraError()
    {
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->deleteResource(Argument::any(), Argument::any())
            ->willReturn(new Response(403));
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

        $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
    }

    /**
     * @covers ::__construct
     * @covers ::deleteNode
     */
    public function testDeleteReturnsFedoraResult()
    {
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->deleteResource(Argument::any(), Argument::any())
            ->willReturn(new Response(204));
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

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->deleteResource(Argument::any(), Argument::any())
            ->willReturn(new Response(404));
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

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 404,
            "Milliner must return 404 when Fedora returns 404.  Received: $status"
        );

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->deleteResource(Argument::any(), Argument::any())
            ->willReturn(new Response(410));
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

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 410,
            "Milliner must return 410 when Fedora returns 410.  Received: $status"
        );
    }
}
