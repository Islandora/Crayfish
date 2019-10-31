<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class MillinerServiceTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class DeleteTest extends TestCase
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
     * @covers ::deleteNode
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testDeleteReturnsFedoraError()
    {
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn(['drupal' => 'foo', 'fedora' => 'bar']);
        $gemini = $gemini->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->deleteResource(Argument::any(), Argument::any())
            ->willReturn(new Response(403));
        $fedora = $fedora->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->deleteNode("abc123", "Bearer islandora");
    }

    /**
     * @covers ::__construct
     * @covers ::deleteNode
     */
    public function testDeleteReturns204OnGeminiSuccess()
    {
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls("first", Argument::any())
            ->willReturn(['drupal' => 'foo', 'fedora' => 'bar']);
        $gemini->getUrls("second", Argument::any())
            ->willReturn([]);
        $gemini->deleteUrls(Argument::any(), Argument::any())
            ->willReturn(true);
        $gemini = $gemini->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->deleteResource(Argument::any(), Argument::any())
            ->willReturn(new Response(404));
        $fedora = $fedora->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->deleteNode("first", "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Gemini returns success.  Received: $status"
        );

        $response = $milliner->deleteNode("second", "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Gemini returns success.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::deleteNode
     */
    public function testDeleteReturns404IfNotMappedAndGeminiFails()
    {
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn([]);
        $gemini->deleteUrls(Argument::any(), Argument::any())
            ->willReturn(false);
        $gemini = $gemini->reveal();

        $fedora = $this->prophesize(IFedoraApi::class)->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->deleteNode("abc123", "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 404,
            "Milliner must return 404 when Gemini returns fail and resource was not mapped.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::deleteNode
     */
    public function testDeleteReturnsFedoraErrorIfMappedButGeminiFails()
    {
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn(['drupal' => 'foo', 'fedora' => 'bar']);
        $gemini->deleteUrls(Argument::any(), Argument::any())
            ->willReturn(false);
        $gemini = $gemini->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->deleteResource(Argument::any(), Argument::any())
            ->willReturn(new Response(410));
        $fedora = $fedora->reveal();

        $drupal = $this->prophesize(Client::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->deleteNode("abc123", "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 410,
            "Milliner must return Fedora response when mapped but Gemini fails.  Expected 410, Received: $status"
        );
    }
}
