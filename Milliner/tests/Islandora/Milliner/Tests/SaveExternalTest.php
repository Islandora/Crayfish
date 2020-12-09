<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * Class SaveExternalTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class SaveExternalTest extends TestCase
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());

        $this->modifiedDatePredicate = "http://schema.org/dateModified";
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     * @expectedException \GuzzleHttp\Exception\RequestException
     * @expectedExceptionCode 403
     */
    public function testSaveExternalThrowsOnMintError()
    {
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any(), Argument::any())
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

        $milliner->saveExternal(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            "http://localhost:8080/fcrepo/rest/",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     * @expectedException \GuzzleHttp\Exception\RequestException
     * @expectedExceptionCode 403
     */
    public function testSaveExternalThrowsOnHeadError()
    {
        $url = "http://localhost:8080/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc81";
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($url);
        $gemini = $gemini->reveal();

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
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveExternal(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            "http://localhost:8080/fcrepo/rest/",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     * @expectedException \RuntimeException
     * @expectedExceptionCode 403
     */
    public function testSaveExternalThrowsOnPutError()
    {
        $url = "http://localhost:8080/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc81";
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($url);
        $gemini = $gemini->reveal();

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
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveExternal(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            "http://localhost:8080/fcrepo/rest/",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     * @expectedException \GuzzleHttp\Exception\RequestException
     * @expectedExceptionCode 403
     */
    public function testSaveExternalThrowsOnGeminiError()
    {
        $url = "http://localhost:8080/95/41/c0/c1/9541c0c1-5bee-4973-a9d0-e55c1658bc81";
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->mintFedoraUrl(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($url);
        $gemini->saveUrls(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(
                new RequestException(
                    "Unauthorized",
                    new Request('PUT', 'http://localhost:8000/gemini/9541c0c1-5bee-4973-a9d0-e55c1658bc81'),
                    new Response(403, [], "Unauthorized")
                )
            );
        $gemini = $gemini->reveal();

        $drupal = $this->prophesize(Client::class);
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'image/jpeg']));
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $fedora = $fedora->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveExternal(
            "9541c0c1-5bee-4973-a9d0-e55c1658bc81",
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            "http://localhost:8080/fcrepo/rest/",
            "Bearer islandora"
        );
    }
}
