<?php

namespace Islandora\Crayfish\Commons\tests;

use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\ApixMiddleware;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Psr\Http\Message\ResponseInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

class ApixMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsFedoraError()
    {
        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getBody()->willReturn();
        $prophecy->getHeaders()->willReturn();
        $prophecy->getStatusCode()->willReturn(401);
        $prophecy->getReasonPhrase()->willReturn("Unauthorized");
        $mock_fedora_response = $prophecy->reveal();

        // Mock a FedoraApi to return the mock response.
        $prophecy = $this->prophesize(IFedoraApi::class);
        $prophecy->getResource(Argument::any(), Argument::any())->willReturn($mock_fedora_response);
        $mock_fedora_api = $prophecy->reveal();

        // Make a null logger.
        $log = new Logger('null');
        $handler = new NullHandler();
        $log->pushHandler($handler);

        $middleware = new ApixMiddleware(
            $mock_fedora_api,
            $log
        );

        // Create a Request.
        $request = Request::create(
            "/",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', 'http://localhost:8080/fcrepo/rest/foo');

        // Test before().
        $response = $middleware->before($request);

        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Fedora response code"
        );
        $this->assertTrue(
            $response->getContent() == "Unauthorized",
            "Response must return Fedora's reason phrase"
        );
    }

    public function testReturns400IfNoApixLdpResourceHeader()
    {
        // Mock a FedoraApi.
        $prophecy = $this->prophesize(IFedoraApi::class);
        $mock_fedora_api = $prophecy->reveal();

        // Make a null logger.
        $log = new Logger('null');
        $handler = new NullHandler();
        $log->pushHandler($handler);

        // Make the middleware.
        $middleware = new ApixMiddleware(
            $mock_fedora_api,
            $log
        );

        // Create a Request.
        $request = Request::create(
            "/",
            "GET"
        );

        // Test before().
        $response = $middleware->before($request);

        $this->assertTrue(
            $response->getStatusCode() == 400,
            "Response code must be 400 if no ApixLdpResource header is present."
        );
    }
}
