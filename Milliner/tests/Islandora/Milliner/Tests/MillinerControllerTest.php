<?php

namespace Islandora\Milliner\Tests;

use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Service\MillinerServiceInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7\Response;

class MillinerControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testMethodsReturnDrupalError()
    {
        // Wire up a controller.
        $mock_milliner = $this->prophesize(MillinerServiceInterface::class)->reveal();
        $mock_log = $this->prophesize(LoggerInterface::class)->reveal();
        $controller = new MillinerController($mock_milliner, $mock_log);

        // Dummy Drupal error response
        $drupal_response = new Response(401, [], '');

        // Mock a request since we'll never hit it.
        $mock_request = $this->prophesize(Request::class)->reveal();

        $response = $controller->create(
            $drupal_response,
            $mock_request
        );
        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Drupal response code"
        );

        $response = $controller->update(
            $drupal_response,
            $mock_request
        );
        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Drupal response code"
        );
    }

    public function testMethodsReturnFedoraResponse()
    {
        // Wire up a controller.
        $mock_milliner = $this->prophesize(MillinerServiceInterface::class);
        $mock_milliner->create(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(401, [], "Unauthorized"));
        $mock_milliner->update(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(401, [], "Unauthorized"));
        $mock_milliner->delete(Argument::any(), Argument::any())
            ->willReturn(new Response(401, [], "Unauthorized"));
        $mock_milliner = $mock_milliner->reveal();
        $mock_log = $this->prophesize(LoggerInterface::class)->reveal();
        $controller = new MillinerController($mock_milliner, $mock_log);

        // Dummy Drupal success response
        $drupal_response = new Response(200, [], '{"@graph": [{"@id": "http://foo.com/bar"}]}');

        // Dummy request
        $request = Request::create(
            "http://baz.org/bar",
            'POST',
            ['path' => 'bar'],
            [],
            [],
            ['Authorization' => 'some_token']
        );

        $response = $controller->create($drupal_response, $request);
        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Fedora response code"
        );

        $response = $controller->update($drupal_response, $request);
        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Fedora response code"
        );

        $response = $controller->delete('bar', $request);
        $this->assertTrue(
            $response->getStatusCode() == 401,
            "Response code must be Fedora response code"
        );
    }

    public function testMethodsReturnExceptionMessage()
    {
        // Wire up a controller.
        $mock_milliner = $this->prophesize(MillinerServiceInterface::class);
        $mock_milliner->create(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \RuntimeException("ERROR", 500));
        $mock_milliner->update(Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new \RuntimeException("ERROR", 500));
        $mock_milliner->delete(Argument::any(), Argument::any())
            ->willThrow(new \RuntimeException("ERROR", 500));
        $mock_milliner = $mock_milliner->reveal();
        $mock_log = $this->prophesize(LoggerInterface::class)->reveal();
        $controller = new MillinerController($mock_milliner, $mock_log);

        // Dummy Drupal success response
        $drupal_response = new Response(200, [], '{"@graph": [{"@id": "http://foo.com/bar"}]}');

        // Dummy request
        $request = Request::create(
            "http://baz.org/bar",
            'POST',
            ['path' => 'bar'],
            [],
            [],
            ['Authorization' => 'some_token']
        );

        $response = $controller->create($drupal_response, $request);
        $this->assertTrue(
            $response->getStatusCode() == 500,
            "Response code must be Exception response code"
        );

        $response = $controller->update($drupal_response, $request);
        $this->assertTrue(
            $response->getStatusCode() == 500,
            "Response code must be Exception response code"
        );

        $response = $controller->delete('bar', $request);
        $this->assertTrue(
            $response->getStatusCode() == 500,
            "Response code must be Exception response code"
        );
    }
}
