<?php

namespace Islandora\Hypercube\Tests;

use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Hypercube\Controller\HypercubeController;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Islandora\Hypercube\Controller\HypercubeController
 */
class HypercubeControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::options
     */
    public function testOptions()
    {
        $mock_service = $this->prophesize(CmdExecuteService::class)->reveal();
        $controller = new HypercubeController(
            $mock_service,
            ''
        );

        $response = $controller->options();
        $this->assertTrue($response->getStatusCode() == 200, 'Identify OPTIONS should return 200');
        $this->assertTrue(
            $response->headers->get('Content-Type') == 'text/turtle',
            'Identify OPTIONS should return turtle'
        );
    }

    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testTesseractErrorReturns500()
    {
        // Mock a TesseractService to create a controller.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $prophecy->execute(Argument::any(), Argument::any())->willThrow(new \RuntimeException("ERROR", 500));
        $mock_service = $prophecy->reveal();
        $controller = new HypercubeController($mock_service, '');

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
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('ApixLdpResource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->get($request);
        $this->assertTrue($response->getStatusCode() == 500, "Response must return 500");
        $this->assertTrue($response->getContent() == "ERROR", "Response must return exception's message");
    }

    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testTesseractSuccessReturns200()
    {
        // Mock a TesseractService to create a controller.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $mock_service = $prophecy->reveal();
        $controller = new HypercubeController($mock_service, '');

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
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('ApixLdpResource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->get($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");
    }
}
