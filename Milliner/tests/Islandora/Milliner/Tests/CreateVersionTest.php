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
class CreateVersionTest extends TestCase
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
     * @covers ::createVersion
     */
    public function testCreateVersionReturnsFedora201()
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

        $fedora_response = new Response(201);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
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

        $response = $milliner->createVersion(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
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
     * @expectedException \RuntimeException
     * @expectedExceptionCode 404
     */

    public function testCreateVersionReturnsFedora404()
    {
        $mapping = [
            'drupal' => '"http://localhost:8000/node/1?_format=jsonld"',
            'fedora' => 'http://localhost:8080/fcrepo/rest/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-9998'
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

        $fedora_response = new Response(404);
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
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

        $response = $milliner->createVersion(
            "9541c0c1-5bee-4973-a9d0-9998",
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
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testcreateVersionThrowsOnFedoraSaveError()
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
        $fedora_response = new Response(403, [], null, '1.1', 'UNAUTHORIZED');
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
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

        $response = $milliner->createVersion(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Milliner must return 403 when Fedora returns 403.  Received: $status"
        );
    }
}
