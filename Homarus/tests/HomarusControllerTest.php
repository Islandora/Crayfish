<?php

namespace App\Islandora\Homarus\Tests;

use App\Islandora\Homarus\Controller\HomarusController;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \App\Islandora\Homarus\Controller\HomarusController
 */
class HomarusControllerTest extends TestCase
{

    use ProphecyTrait;

    private $defaults;

    private $formats;

    /**
     * Setup to reset to defaults.
     */
    public function setUp(): void
    {
        $this->defaults = [
          'format' => 'mp4',
          'mimetype' => 'video/mp4',
        ];

        $this->formats = [
            [ 'mimetype' => 'video/mp4', 'format' => 'mp4'],
            [ 'mimetype' => 'video/x-msvideo', 'format' => 'avi'],
            [ 'mimetype' => 'video/ogg', 'format' => 'ogg'],
        ];
    }

    /**
     * @covers ::convertOptions
     */
    public function testOptions()
    {
        $controller = $this->getDefaultController();

        $response = $controller->convertOptions();
        $this->assertTrue($response->getStatusCode() == 200, 'Convert OPTIONS should return 200');
        $this->assertTrue(
            $response->headers->get('Content-Type') == 'text/turtle',
            'Convert OPTIONS should return turtle'
        );
    }

    /**
     * @covers ::__construct
     * @covers ::convert
     */
    public function testErrorReturns500()
    {

        $prophecy = $this->prophesize(CmdExecuteService::class);
        $prophecy->execute(Argument::any(), Argument::any())->willThrow(new \RuntimeException("ERROR", 500));
        $mock_service = $prophecy->reveal();
        // Create a controller.
        $controller = new HomarusController(
            $mock_service,
            $this->formats,
            $this->defaults,
            'convert',
            $this->prophesize(Logger::class)->reveal()
        );

        $mock_fedora_response = $this->getMockFedoraResource();

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
        $this->assertEquals(500, $response->getStatusCode(), "Response must return 500");
        $this->assertEquals("ERROR", $response->getContent(), "Response must return exception's message");
    }

    /**
     * @covers ::__construct
     * @covers ::convert
     * @covers ::getFfmpegFormat
     */
    public function testSuccessReturns200ValidContentType()
    {
        $controller = $this->getDefaultController();
        $mock_fedora_response = $this->getMockFedoraResource();

        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->headers->set('Accept', 'video/mp4');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->convert($request);
        $this->assertEquals(200, $response->getStatusCode(), "Response must return 200");
        $this->assertEquals('video/mp4', $response->headers->get('Content-type'), "Content-type must be video/mp4");
    }

    /**
     * @covers ::__construct
     * @covers ::convert
     * @covers ::getFfmpegFormat
     */
    public function testUnmappedContentType()
    {
        $new_content_type = 'video/x-flv';
        $this->formats[] = $new_content_type;
        $controller = $this->getDefaultController();
        $mock_fedora_response = $this->getMockFedoraResource();

        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->headers->set('Accept', $new_content_type);
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->convert($request);
        $this->assertEquals(200, $response->getStatusCode(), "Response must return 200");
        $this->assertEquals('video/mp4', $response->headers->get('Content-type'), "Content-type must be video/mp4");
    }

    /**
     * @covers ::__construct
     * @covers ::convert
     * @covers ::getFfmpegFormat
     */
    public function testInvalidContentType()
    {
        $new_content_type = 'video/x-flv';
        $controller = $this->getDefaultController();
        $mock_fedora_response = $this->getMockFedoraResource();

        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->headers->set('Accept', $new_content_type);
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->convert($request);
        $this->assertEquals(200, $response->getStatusCode(), "Response must return 200");
        $this->assertEquals('video/mp4', $response->headers->get('Content-type'), "Content-type must be video/mp4");
    }

    public function testFailOnNoApixHeader()
    {
        $controller = $this->getDefaultController();
        $mock_fedora_response = $this->getMockFedoraResource();

        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Accept', 'video/mp4');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->convert($request);
        $this->assertEquals(400, $response->getStatusCode(), "Response must return 400");
    }

    public function testFailOnSettingLogLevel()
    {
        $controller = $this->getDefaultController();
        $mock_fedora_response = $this->getMockFedoraResource();

        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');
        $request->headers->set('Accept', 'video/mp4');
        $request->headers->set('X-Islandora-Args', '-vn -ar 44100 -loglevel debug -ac 2 -ab 192');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->convert($request);
        $this->assertEquals(400, $response->getStatusCode(), "Response must return 400");
    }

    private function getDefaultController()
    {
        // Mock a CmdExecuteService.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $mock_service = $prophecy->reveal();

        // Create a controller.
        $controller = $this->getMockBuilder(HomarusController::class)
          ->onlyMethods(['generateDerivativeResponse'])
          ->setConstructorArgs([
            $mock_service,
            $this->formats,
            $this->defaults,
            'convert',
            $this->prophesize(Logger::class)->reveal(),
          ])
          ->getMock();

        $controller->method('generateDerivativeResponse')
          ->will($this->returnCallback(function ($cmd_string, $source, $path, $content_type) {
            return new BinaryFileResponse(
              __DIR__ . "/../../../fixtures/foo.mp4",
              200,
              ['Content-Type' => $content_type]
            );
          }));
        return $controller;
    }

    private function getMockFedoraResource()
    {
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
        return $mock_fedora_response;
    }
}
