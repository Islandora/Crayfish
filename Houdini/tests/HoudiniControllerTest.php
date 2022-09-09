<?php

namespace App\Islandora\Houdini\Tests;

use Islandora\Crayfish\Commons\CmdExecuteService;
use App\Islandora\Houdini\Controller\HoudiniController;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;

/**
 * @coversDefaultClass \App\Islandora\Houdini\Controller\HoudiniController
 */
class HoudiniControllerTest extends TestCase
{

    use ProphecyTrait;

    /**
     * @covers ::__construct
     * @covers ::identifyOptions
     * @covers ::convertOptions
     */
    public function testOptions()
    {
        $mock_service = $this->prophesize(CmdExecuteService::class)->reveal();
        $controller = new HoudiniController(
            $mock_service,
            [],
            '',
            'convert',
            $this->prophesize(Logger::class)->reveal()
        );

        $response = $controller->identifyOptions();
        $this->assertTrue($response->getStatusCode() == 200, 'Identify OPTIONS should return 200');
        $this->assertTrue(
            $response->headers->get('Content-Type') == 'text/turtle',
            'Identify OPTIONS should return turtle'
        );

        $response = $controller->convertOptions();
        $this->assertTrue($response->getStatusCode() == 200, 'Convert OPTIONS should return 200');
        $this->assertTrue(
            $response->headers->get('Content-Type') == 'text/turtle',
            'Convert OPTIONS should return turtle'
        );
    }

    /**
     * @covers ::__construct
     * @covers ::identify
     * @covers ::convert
     */
    public function testErrorReturns500Image()
    {
        $this->errorReturns500('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::identify
     * @covers ::convert
     */
    public function testErrorReturns500PDF()
    {
        $this->errorReturns500('image/tiff');
    }

    protected function errorReturns500($content_type)
    {
        // Mock a CmdExecuteService to create a controller.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $prophecy->execute(Argument::any(), Argument::any())->willThrow(new \RuntimeException("ERROR", 500));
        $mock_service = $prophecy->reveal();
        $controller = new HoudiniController(
            $mock_service,
            [],
            '',
            'convert',
            $this->prophesize(Logger::class)->reveal()
        );

        // Mock a stream body for a Fedora response.
        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getStatusCode()->willReturn(200);
        $prophecy->getHeaders()->willReturn(['Content-Type' => $content_type]);
        $prophecy->getBody()->willReturn($mock_stream);
        $mock_fedora_response = $prophecy->reveal();

        // Create a Request.
        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        // Test convert
        $response = $controller->convert($request);
        $this->assertTrue($response->getStatusCode() == 500, "Response must return 500");
        $this->assertTrue($response->getContent() == "ERROR", "Response must return exception's message");

        // Test identify
        $response = $controller->identify($request);
        $this->assertTrue($response->getStatusCode() == 500, "Response must return 500");
        $this->assertTrue($response->getContent() == "ERROR", "Response must return exception's message");
    }

    /**
     * @covers ::__construct
     * @covers ::identify
     * @covers ::convert
     */
    public function testSuccessReturns200Image($content_type)
    {
        $this->successReturns200('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::identify
     * @covers ::convert
     */
    public function testSuccessReturns200PDF($content_type)
    {
        $this->successReturns200('application/pdf');
    }

    protected function successReturns200($content_type)
    {
        // Mock a stream body for a Fedora response.
        $mock_fedora_response = $this->mockFedoraResponse($content_type);

        // Mock a CmdExecuteService.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $mock_service = $prophecy->reveal();

        // Create a controller.
        $controller = new HoudiniController(
            $mock_service,
            ['image/jpeg', 'image/png'],
            'image/jpeg',
            'convert',
            $this->prophesize(Logger::class)->reveal()
        );

        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->headers->set('Accept', 'image/png');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->identify($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");

        $response = $controller->convert($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");
    }

    /**
     * @covers ::__construct
     * @covers ::identify
     * @covers ::convert
     */
    public function testSuccessReturns200FallbackImage()
    {
        $this->successReturns200Fallback('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::identify
     * @covers ::convert
     */
    public function testSuccessReturns200FallbackPDF()
    {
        $this->successReturns200Fallback('application/pdf');
    }

    protected function successReturns200Fallback($content_type)
    {
        // Mock a stream body for a Fedora response.
        $mock_fedora_response = $this->mockFedoraResponse($content_type);

        // Mock a CmdExecuteService.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $mock_service = $prophecy->reveal();

        // Create a controller.
        $controller = new HoudiniController(
            $mock_service,
            [],
            '',
            'convert',
            $this->prophesize(Logger::class)->reveal()
        );

        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->identify($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");

        $response = $controller->convert($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");
    }

    protected function mockFedoraResponse($content_type) {
        // Mock a stream body for a Fedora response.
        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getStatusCode()->willReturn(200);
        $prophecy->getHeaders()->willReturn(['Content-Type' => $content_type]);
        $prophecy->getBody()->willReturn($mock_stream);
        $mock_fedora_response = $prophecy->reveal();
	return $mock_fedora_response;
    }
}
