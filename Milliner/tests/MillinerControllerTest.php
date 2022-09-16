<?php

namespace App\Islandora\Milliner\Tests;

use App\Islandora\Milliner\Controller\MillinerController;
use App\Islandora\Milliner\Service\MillinerServiceInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Class MillinerControllerTest
 * @package \App\Islandora\Milliner\Tests
 * @coversDefaultClass \App\Islandora\Milliner\Controller\MillinerController
 */
class MillinerControllerTest extends AbstractMillinerTestCase
{
    /**
     * A Milliner Service prophecy.
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $milliner_service_prophecy;

    /**
     * A controller instance.
     * @var \App\Islandora\Milliner\Controller\MillinerController
     */
    private $controller;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->milliner_service_prophecy = $this->prophesize(MillinerServiceInterface::class);
        $this->controller = new MillinerController($this->milliner_service_prophecy->reveal(), $this->logger);
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::saveMedia
     * @covers ::deleteNode
     * @covers ::createNodeVersion
     * @covers ::createMediaVersion
     */
    public function testMethodsReturnMillinerErrors()
    {
        // Wire up a controller.
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveNode(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->saveMedia(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->deleteNode(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->saveExternal(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->createVersion(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner->createMediaVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \Exception("Forbidden", 403));
        $milliner = $milliner->reveal();

        $controller = new MillinerController($milliner, $this->logger);

        // Route parameters.
        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $source_field = 'field_image';

        // Nodes.
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
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
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/media/6?_format=json',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->saveMedia($source_field, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // Version Media.
        $request = Request::create(
            "http://localhost:8000/milliner/media/$source_field/version",
            "POST",
            ['source_field' => $source_field],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/media/6?_format=json',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->createMediaVersion($source_field, $request);
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
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->deleteNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // External.
        $request = Request::create(
            "http://localhost:8000/milliner/external/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/sites/default/files/1.jpg',
            ]
        );
        $response = $controller->saveExternal($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );

        // Version.
        // Delete.
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid/version",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->createNodeVersion($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Response code must be that of thrown exception.  Expected 403, received $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     */
    public function testSaveNodeReturns400WithoutContentLocation()
    {

        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $this->controller->saveNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no Content-Location header is present.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     */
    public function testSaveMediaReturn400WithoutContentLocation()
    {
        $source_field = "field_image";
        // Media.
        $request = Request::create(
            "http://localhost:8000/milliner/media/$source_field",
            "POST",
            ['source_field' => $source_field],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $this->controller->saveMedia($source_field, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no Content-Location header is present.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalReturns400WithoutContentLocation()
    {
        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $request = Request::create(
            "http://localhost:8000/milliner/external/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $this->controller->saveExternal($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 400,
            "Response code must be 400 when no Content-Location header is present.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     */
    public function testSaveNodeReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveNode(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // Nodes.
        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
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
        $milliner->saveNode(Argument::any(), Argument::any(), Argument::any(), Argument::any())
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
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
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
     * @covers ::__construct
     * @covers ::saveMedia
     */
    public function testSaveMediaReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveMedia(Argument::any(), Argument::any(), Argument::any(), Argument::any())
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
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
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
        $milliner->saveMedia(Argument::any(), Argument::any(), Argument::any(), Argument::any())
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
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
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
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveExternal(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // Nodes.
        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $request = Request::create(
            "http://localhost:8000/milliner/external/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/sites/default/files/1.jpeg',
            ]
        );
        $response = $controller->saveExternal($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Response code must be 201 when milliner returns 201.  Received: $status"
        );

        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->saveExternal(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/external/$uuid",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/sites/default/files/1.jpeg',
            ]
        );
        $response = $controller->saveExternal($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::deleteNode
     */
    public function testDeleteReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->deleteNode(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $request = Request::create(
            "http://localhost:8000/milliner/resource/$uuid",
            "DELETE",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );

        $response = $controller->deleteNode($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::createNodeVersion
     */
    public function testCreateNodeVersionReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // Nodes.
        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid/version",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/node/1?_format=jsonld',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->createNodeVersion($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Response code must be 201 when milliner returns 201.  Received: $status"
        );

        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/node/$uuid/version",
            "POST",
            ['uuid' => $uuid],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/node/1?_format=jsonld',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->createNodeVersion($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }

        /**
     * @covers ::__construct
     * @covers ::createMediaVersion
     */
    public function testCreateMediaVersionReturnsSuccessOnSuccess()
    {
        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->createMediaVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(201));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        // Nodes.
        $uuid = '630e0c1d-1a38-4e3b-84f9-68d519bdc026';
        $source_field = 'field_image';
        $request = Request::create(
            "http://localhost:8000/milliner/media/$source_field/version",
            "POST",
            ['source_field' => $source_field],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/media/6?_format=json',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->createMediaVersion($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Response code must be 201 when milliner returns 201.  Received: $status"
        );

        $milliner = $this->prophesize(MillinerServiceInterface::class);
        $milliner->createMediaVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(204));
        $milliner = $milliner->reveal();
        $controller = new MillinerController($milliner, $this->logger);

        $request = Request::create(
            "http://localhost:8000/milliner/media/$source_field/version",
            "POST",
            ['source_field' => $source_field],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer islandora',
                'HTTP_CONTENT_LOCATION' => 'http://localhost:8000/media/6?_format=json',
                'HTTP_X_ISLANDORA_FEDORA_ENDPOINT' => 'http://localhost:8080/fcrepo/rest',
            ]
        );
        $response = $controller->createMediaVersion($uuid, $request);
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Response code must be 204 when milliner returns 204.  Received: $status"
        );
    }
}
