<?php

namespace Islandora\Hypercube\Tests;

use Islandora\Hypercube\Controller\HypercubeController;
use Islandora\Hypercube\Service\TesseractService;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;

class HypercubeControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsFedoraError()
    {
      // Mock a TesseractService to create a controller.
      $prophecy = $this->prophesize(TesseractService::class);
      $mock_service = $prophecy->reveal();
      $controller = new HypercubeController($mock_service);

      // Mock a Fedora response.
      $prophecy = $this->prophesize(ResponseInterface::class);
      $prophecy->getStatusCode()->willReturn(401);
      $prophecy->getReasonPhrase()->willReturn("Unauthorized");
      $mock_fedora_response = $prophecy->reveal();

      // Create a Request.
      $request = Request::create(
        "/foo",
        "GET"
      );

      $response = $controller->get($mock_fedora_response, $request);
      $this->assertTrue(
          $response->getStatusCode() == 401,
          "Response code must be Fedora response code"
      );
      $this->assertTrue(
          $response->getContent() == "Unauthorized",
          "Response must return Fedora's reason phrase"
      );
    }

    public function testTesseractErrorReturns500()
    {
        // Mock a TesseractService to create a controller.
        $prophecy = $this->prophesize(TesseractService::class);
        $prophecy->execute(Argument::any(), Argument::any())->willThrow(new \RuntimeException("ERROR", 500));
        $mock_service = $prophecy->reveal();
        $controller = new HypercubeController($mock_service);

        // Mock a stream body for a Fedora response.
        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getStatusCode()->willReturn(200);
        $prophecy->getBody()->willReturn($mock_stream);
        $mock_fedora_response = $prophecy->reveal();

        // Create a Request.
        $request = Request::create(
          "/foo",
          "GET"
        );

        $response = $controller->get($mock_fedora_response, $request);
        $this->assertTrue($response->getStatusCode() == 500, "Response must return 500");
        $this->assertTrue($response->getContent() == "ERROR", "Response must return exception's message");
    }

    public function testTesseractSuccessReturns200()
    {
        // Mock a TesseractService to create a controller.
        $prophecy = $this->prophesize(TesseractService::class);
        $mock_service = $prophecy->reveal();
        $controller = new HypercubeController($mock_service);

        $request = Request::create(
          "/foo",
          "GET"
        );

        // Mock a stream body for a Fedora response.
        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getStatusCode()->willReturn(200);
        $prophecy->getBody()->willReturn($mock_stream);
        $mock_fedora_response = $prophecy->reveal();

        $response = $controller->get($mock_fedora_response, $request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");
    }
}
