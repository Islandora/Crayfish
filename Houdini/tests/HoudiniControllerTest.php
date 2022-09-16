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

    private $mock_service;

    private $mock_logger;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mock_service = $this->prophesize(CmdExecuteService::class)->reveal();
        $this->mock_logger = $this->prophesize(Logger::class)->reveal();
    }

  /**
     * @covers ::__construct
     * @covers ::identifyOptions
     * @covers ::convertOptions
     */
    public function testOptions()
    {
        $controller = $this->getController();

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
        $this->errorReturns500('application/pdf');
    }

    private function errorReturns500($content_type)
    {
        // Mock a CmdExecuteService to create a controller.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $prophecy->execute(Argument::any(), Argument::any())->willThrow(new \RuntimeException("ERROR", 500));
        $this->mock_service = $prophecy->reveal();
        $controller = $this->getController();

        // Create a Request.
        $request = $this->getRequest($content_type);

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
    public function testSuccessReturns200Image()
    {
        $this->successReturns200('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::identify
     * @covers ::convert
     */
    public function testSuccessReturns200PDF()
    {
        $this->successReturns200('application/pdf');
    }

    private function successReturns200($content_type)
    {
        // Create a controller.
        $controller = $this->getController(
            ['image/jpeg', 'image/png'],
            'image/jpeg'
        );

        $request = $this->getRequest($content_type, 'image/png');

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

    private function successReturns200Fallback($content_type)
    {
        // Create a controller.
        $controller = $this->getController();

        $request = $this->getRequest($content_type);

        $response = $controller->identify($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");

        $response = $controller->convert($request);
        $this->assertTrue($response->getStatusCode() == 200, "Response must return 200");
    }

    /**
     * Get a HoudiniController.
     *
     * @param array $formats
     *   The formats for the controller.
     * @param string $default_format
     *   The default format for the controller.
     * @param string $executable
     *   The executable for the controller.
     *
     * @return \App\Islandora\Houdini\Controller\HoudiniController
     *   The controller.
     */
    private function getController(
        array $formats = [],
        string $default_format = "",
        string $executable = "convert"
    ): HoudiniController {
        return new HoudiniController(
            $this->mock_service,
            $formats,
            $default_format,
            $executable,
            $this->mock_logger
        );
    }

    /**
     * Get a Request with a mocked Fedora resource.
     *
     * @param string $content_type
     *   The content type of the fake Fedora resource.
     * @param string|null $accept_type
     *   An Accept header value or null to not add the header.
     * @return \Symfony\Component\HttpFoundation\Request
     *   The Request.
     */
    private function getRequest(string $content_type, string $accept_type = null): Request
    {
        // Create a Request.
        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        if (!is_null($accept_type)) {
            $request->headers->set('Accept', $accept_type);
        }
        $request->attributes->set('fedora_resource', $this->mockFedoraResponse($content_type));
        return $request;
    }

    /**
     * Mock a Response with a mock Fedora stream in it..
     *
     * @param string $content_type
     *   The content type to make the stream claim to be.
     * @return ResponseInterface
     *   The ResponseInterface.
     */
    private function mockFedoraResponse($content_type): ResponseInterface
    {
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
        return $prophecy->reveal();
    }
}
