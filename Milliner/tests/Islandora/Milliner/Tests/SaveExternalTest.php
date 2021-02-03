<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Class SaveExternalTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class SaveExternalTest extends TestCase
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
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalThrowsOnHeadError()
    {
        $drupal = $this->prophesize(Client::class);
        $drupal->head(Argument::any(), Argument::any())
            ->willThrow(
                new RequestException(
                    "Unauthorized",
                    new Request('HEAD', 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg'),
                    new Response(403, [], null, "1.1", "UNAUTHORIZED")
                )
            );
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
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

        $this->expectException(\GuzzleHttp\Exception\RequestException::class, null, 403);

        $milliner->saveExternal(
            $this->uuid,
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalThrowsOnPutError()
    {
        $drupal = $this->prophesize(Client::class);
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'image/jpeg']));
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(403, [], null, "1.1", "UNAUTHORIZED"));
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

        $this->expectException(\RuntimeException::class, null, 403);

        $milliner->saveExternal(
            $this->uuid,
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }
}
