<?php

namespace Islandora\Hypercube\Tests;

use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Hypercube\Controller\HypercubeController;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Islandora\Hypercube\Controller\HypercubeController
 */
class HypercubeControllerTest extends TestCase
{
    use ProphecyTrait;
    /**
     * @covers ::__construct
     * @covers ::options
     */
    public function testOptions()
    {
        $mock_service = $this->prophesize(CmdExecuteService::class)->reveal();
        $mock_logger = $this->prophesize(Logger::class)->reveal();
        $controller = new HypercubeController(
            $mock_service,
            'tesseract',
            'pdftotext',
            $mock_logger
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
        $this->errorReturns500('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testPdfToTextErrorReturns500()
    {
        $this->errorReturns500('application/pdf');
    }

    protected function errorReturns500($mimetype)
    {
        // Mock a TesseractService to create a controller.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $prophecy->execute(Argument::any(), Argument::any())->willThrow(new \RuntimeException("ERROR", 500));
        $mock_service = $prophecy->reveal();
        $mock_logger = $this->prophesize(Logger::class)->reveal();
        $controller = new HypercubeController($mock_service, 'tesseract', 'pdftotext', $mock_logger);

        // Mock a stream body for a Fedora response.
        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getHeader('Content-Type')->willReturn(['image/tiff']);
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
        $this->successReturns200('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testPdfToTextSuccessReturns200()
    {
        $this->successReturns200('application/pdf');
    }

    protected function successReturns200($mimetype)
    {
        // Mock a controller.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $mock_service = $prophecy->reveal();
        $mock_logger = $this->prophesize(Logger::class)->reveal();
        $controller = new HypercubeController($mock_service, 'tesseract', 'pdftotext', $mock_logger);

        // Mock a stream body for a Fedora response.
        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getHeader('Content-Type')->willReturn([$mimetype]);
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

        // Check success.
        $response = $controller->get($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");
    }
}
