<?php

namespace Islandora\Homarus\Tests;

use Islandora\Crayfish\Commons\ApixFedoraResourceRetriever;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Homarus\Controller\HomarusController;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Islandora\Homarus\Controller\HomarusController
 */
class HomarusControllerTest extends TestCase
{

    private $mime_to_format;

    private $default_format;

    private $content_types;

    private $default_content_type;

    /**
     * Setup to reset to defaults.
     */
    public function setUp(): void
    {
        $this->mime_to_format = [
            'video/mp4_mp4',
            'video/x-msvideo_avi',
            'video/ogg_ogg',
        ];

        $this->default_format = 'mp4';

        $this->content_types = [
            'video/mp4',
            'video/x-msvideo',
            'video/ogg',
        ];

        $this->default_content_type = 'video/mp4';
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
            $this->content_types,
            $this->default_content_type,
            'convert',
            $this->prophesize(Logger::class)->reveal(),
            $this->mime_to_format,
            $this->default_format
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
        $this->content_types[] = $new_content_type;
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
        $controller = new HomarusController(
            $mock_service,
            $this->content_types,
            $this->default_content_type,
            'convert',
            $this->prophesize(Logger::class)->reveal(),
            $this->mime_to_format,
            $this->default_format
        );
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
