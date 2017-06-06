<?php

namespace Islandora\Houdini\Tests;

use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Crayfish\Commons\ApixFedoraResourceRetriever;
use Islandora\Houdini\Controller\HoudiniController;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;

class HoudiniControllerTest extends \PHPUnit_Framework_TestCase
{
/*
    public function testReturnsFedoraError()
    {
        // Mock a CmdExecuteService to create a controller.
        $prophecy = $this->prophesize(CmdExecuteService::class);
        $mock_service = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getBody()->willReturn();
        $prophecy->getHeaders()->willReturn();
        $prophecy->getStatusCode()->willReturn(401);
        $prophecy->getReasonPhrase()->willReturn("Unauthorized");
        $mock_fedora_response = $prophecy->reveal();

        $prophecy = $this->prophesize(ApixFedoraResourceRetriever::class);
        $prophecy->getFedoraResource(Argument::any())->willReturn($mock_fedora_response);
        $mock_apix_retriever = $prophecy->reveal();

        $controller = new HoudiniController(
            $mock_service,
            $mock_apix_retriever,
            [],
            '',
            'convert',
            $this->prophesize(Logger::class)->reveal()
        );

        // Create a Request.
        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');

        // Test convert
        $response = $controller->convert($request);

        print_r($response->getContent());

        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Fedora response code"
        );
        $this->assertTrue(
            $response->getContent() == "Unauthorized",
            "Response must return Fedora's reason phrase"
        );

        // Test identify
        $response = $controller->identify($request);

        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Fedora response code"
        );
        $this->assertTrue(
            $response->getContent() == "Unauthorized",
            "Response must return Fedora's reason phrase"
        );
    }

*/
    public function testErrorReturns500()
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

    public function testSuccessReturns200()
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
}
