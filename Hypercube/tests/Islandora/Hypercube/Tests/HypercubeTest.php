<?php

namespace Islandora\Hypercube\Tests;

use Islandora\Hypercube\Controller\HypercubeController;
use Islandora\Hypercube\Service\TesseractService;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

class HypercubeTest extends \PHPUnit_Framework_TestCase
{
    public function testNonTiffReturns400()
    {
        $mock_service = $this->prophesize(TesseractService::class)->reveal();
        $controller = new HypercubeController($mock_service);

        $request = Request::create(
            "/",
            "POST",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/turtle']
        );

        $response = $controller->post($request);
        $this->assertTrue($response->getStatusCode() == 400, "Response must return 400");
        $this->assertTrue(
            $response->getContent() == "Hypercube only works on tiffs",
            "Response must return appropriate message"
        );
    }

    public function testNoContentReturns400()
    {
        $mock_service = $this->prophesize(TesseractService::class)->reveal();
        $controller = new HypercubeController($mock_service);

        $request = Request::create(
            "/",
            "POST",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'image/tiff']
        );

        $response = $controller->post($request);
        $this->assertTrue($response->getStatusCode() == 400, "Response must return 400");
        $this->assertTrue(
            $response->getContent() == "No TIFF image provided in request.",
            "Response must return appropriate message"
        );
    }

    public function testTesseractErrorReturns500()
    {
        $prophecy = $this->prophesize(TesseractService::class);
        $prophecy->execute(Argument::any(), Argument::any())->willThrow(new \RuntimeException("ERROR", 500));
        $mock_service = $prophecy->reveal();
        $controller = new HypercubeController($mock_service);

        $request = Request::create(
            "/",
            "POST",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'image/tiff', 'CONTENT_LENGTH' => "100"],
            "BLAH BLAH"
        );

        $response = $controller->post($request);
        $this->assertTrue($response->getStatusCode() == 500, "Response must return 500");
        $this->assertTrue($response->getContent() == "ERROR", "Response must return exception's message");
    }

    public function testTesseractSuccessReturns200()
    {
        $prophecy = $this->prophesize(TesseractService::class);
        $mock_service = $prophecy->reveal();
        $controller = new HypercubeController($mock_service);

        $request = Request::create(
            "/",
            "POST",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'image/tiff', 'CONTENT_LENGTH' => "100"],
            "BLAH BLAH"
        );

        $response = $controller->post($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");
    }
}
