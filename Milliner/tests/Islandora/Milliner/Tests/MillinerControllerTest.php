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
     * @covers ::saveNode
     * @covers ::saveMedia
     * @covers ::deleteNode
     */
    public function testMethodsReturnMillinerErrors()
    {
        // Wire up a controller.
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveNode(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->saveMedia(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->deleteNode(Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner = $milliner->reveal();

        $controller = new MillinerController($milliner, $this->logger);

        // Route parameters.
        $uuid = 'abc123';
        $source_field = 'field_image';

        // Nodes.
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'Authorization' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/node/1?_format=jsonld',
            ]
        );
        $response = $controller->saveNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // Media.
        $request = Request::create(
            "http://localhost:8000/milliner/media/$source_field",
            "POST",
            ['source_field' => $source_field],
            [],
            [],
            [
                'Authorization' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/media/6?_format=json',
            ]
        );
        $response = $controller->saveMedia($source_field, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // Delete.
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            ['Authorization' => 'Bearer islandora']
        );
        $response = $controller->deleteNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );
    }

    /**
     * @covers ::saveNode
     */
    public function testSaveNodeReturns400WithoutContentLocation()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class)->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $uuid = "abc123";
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            ['Authorization' => 'Bearer islandora']
        );
        $response = $controller->saveNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no Content-Location header is present.  Received: $status"
        );
    }

    /**
     * @covers ::saveMedia
     */
    public function testSaveMediaReturn400WithoutContentLocation()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class)->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $source_field = "field_image";
        // Media.
        $request = Request::create(
            "http://localhost:8000/milliner/media/$source_field",
            "POST",
            ['source_field' => $source_field],
            [],
            [],
            ['Authorization' => 'Bearer islandora']
        );
        $response = $controller->saveMedia($source_field, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no Content-Location header is present.  Received: $status"
        );
    }

    /**
     * @covers ::saveNode
     */
    public function testSaveNodeReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveNode(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // Nodes.
        $uuid = "abc123";
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'Authorization' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/node/1?_format=jsonld',
            ]
        );
        $response = $controller->saveNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Response code must be 201 when milliner returns 201.  Received: $status"
        );

        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveNode(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'Authorization' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/node/1?_format=jsonld',
            ]
        );
        $response = $controller->saveNode($uuid, $request);
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

        $source_field = "field_image";
        $request = Request::create(
            "http://localhost:8000/milliner/media/$source_field",
            "POST",
            ["source_field" => $source_field],
            [],
            [],
            [
                'Authorization' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/media/6?_format=json',
            ]
        );
        $response = $controller->saveMedia($source_field, $request);
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
            "http://localhost:8000/milliner/media/$source_field",
            "POST",
            ["source_field" => $source_field],
            [],
            [],
            [
                'Authorization' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/media/6?_format=json',
            ]
        );
        $response = $controller->saveMedia($source_field, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::deleteNode
     */
    public function testDeleteReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->deleteNode(Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $uuid = "abc123";
        $request = Request::create(
            "http://localhost:8000/milliner/resource/$uuid",
            "DELETE",
            ['uuid' => $uuid],
            [],
            [],
            ['Authorization' => 'Bearer islandora']
        );

        $response = $controller->deleteNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }
}
