<?php

namespace Islandora\Milliner\Tests;

use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Service\MillinerServiceInterface;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Class MillinerControllerTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Controller\MillinerController
 */
class MillinerControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());
    }

    /**
     * @covers ::saveContent
     * @covers ::saveMedia
     * @covers ::saveFile
     * @covers ::delete
     */
    public function testMethodsReturnMillinerErrors()
    {
        // Wire up a controller.
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveContent(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->saveFile(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->saveMedia(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->delete(Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner = $milliner->reveal();

        $controller = new MillinerController($milliner, $this->logger);

        // Content.
        $request = Request::create(
            "http://localhost:8000/milliner/content",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentEvent.jsonld')
        );
        $response = $controller->saveContent($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // Media.
        $request = Request::create(
            "http://localhost:8000/milliner/media",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaEvent.jsonld')
        );
        $response = $controller->saveMedia($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // File.
        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/FileEvent.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // Delete.
        $uuid = 'abc123';
        $request = Request::create(
            "http://localhost:8000/milliner/resource/$uuid",
            "POST",
            ['uuid' => 'abc123'],
            [],
            [],
            ['Authorization' => 'Bearer islandora']
        );
        $response = $controller->delete($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );
    }

    /**
     * @covers ::saveContent
     * @covers ::saveMedia
     * @covers ::saveFile
     */
    public function testMethodsReturn400OnBadContentType()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class)->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // Content.
        $request = Request::create(
            "http://localhost:8000/milliner/content",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/xml'],
            file_get_contents(__DIR__ . '/../../../../static/ContentEvent.jsonld')
        );
        $response = $controller->saveContent($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 for non application/ld+json Content-Type.  Received $status"
        );

        // Media.
        $request = Request::create(
            "http://localhost:8000/milliner/media",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/xml'],
            file_get_contents(__DIR__ . '/../../../../static/MediaEvent.jsonld')
        );
        $response = $controller->saveMedia($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 for non application/ld+json Content-Type.  Received $status"
        );

        // File.
        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/xml'],
            file_get_contents(__DIR__ . '/../../../../static/FileEvent.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 for non application/ld+json Content-Type.  Received $status"
        );
    }

    /**
     * @covers ::saveContent
     */
    public function testSaveContentReturn400OnMalformedEvents()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class)->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // No uuid in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/content",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentEventNoUuid.jsonld')
        );
        $response = $controller->saveContent($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no uuid is present.  Received: $status"
        );

        // Malformed uuid in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/content",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentEventBadUuid.jsonld')
        );
        $response = $controller->saveContent($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when a malformed uuid is present.  Received: $status"
        );

        // No JSONLD url in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentEventNoJsonldUrl.jsonld')
        );
        $response = $controller->saveContent($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no JSONLD url is present.  Received: $status"
        );
    }

    /**
     * @covers ::saveFile
     */
    public function testSaveFileReturn400OnMalformedEvents()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class)->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // No uuid in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/FileEventNoUuid.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no uuid is present.  Received: $status"
        );

        // Malformed uuid in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/FileEventBadUuid.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when a malformed uuid is present.  Received: $status"
        );

        // No file url in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/FileEventNoFileUrl.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no File url is present.  Received: $status"
        );

        // No checksum url in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/media",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/FileEventNoChecksumUrl.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no checksum url is present.  Received: $status"
        );
    }

    /**
     * @covers ::saveMedia
     */
    public function testSaveMediaReturn400OnMalformedEvents()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class)->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // No JSONLD url in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/media",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaEventNoJsonldUrl.jsonld')
        );
        $response = $controller->saveMedia($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no JSONLD url is present.  Received: $status"
        );

        // No JSON url in the event.
        $request = Request::create(
            "http://localhost:8000/milliner/media",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaEventNoJsonUrl.jsonld')
        );
        $response = $controller->saveMedia($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no JSON url is present.  Received: $status"
        );
    }

    /**
     * @covers ::saveContent
     */
    public function testSaveContentReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveContent(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/content",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentEvent.jsonld')
        );
        $response = $controller->saveContent($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Response code must be 201 when milliner returns 201.  Received: $status"
        );

        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveContent(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/content",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/ContentEvent.jsonld')
        );
        $response = $controller->saveContent($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::saveMedia
     */
    public function testSaveMediaReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveMedia(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/media",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaEvent.jsonld')
        );
        $response = $controller->saveMedia($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Response code must be 201 when milliner returns 201.  Received: $status"
        );

        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveMedia(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/media",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaEvent.jsonld')
        );
        $response = $controller->saveMedia($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }
    /**
     * @covers ::saveFile
     */
    public function testSaveFileReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveFile(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/FileEvent.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Response code must be 201 when milliner returns 201.  Received: $status"
        );

        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveFile(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/file",
            "POST",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora', 'CONTENT_TYPE' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/FileEvent.jsonld')
        );
        $response = $controller->saveFile($request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::delete
     */
    public function testDeleteReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->delete(Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/resource/abc-123",
            "DELETE",
            [],
            [],
            [],
            ['Authorization' => 'Bearer islandora']
        );

        $response = $controller->delete("abc-123", $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }
}
