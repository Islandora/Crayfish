<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\Controller\GeminiController;
use Islandora\Gemini\Service\GeminiService;

class DeleteFromDrupalPathTest extends \PHPUnit_Framework_TestCase
{
    public function testReturns500OnException()
    {
        $prophecy = $this->prophesize(GeminiService::class);
        $prophecy->deleteFromDrupalPath("foo")
            ->willThrow(new \Exception("Exception", 500));
        $mock_service = $prophecy->reveal();
        $controller = new GeminiController($mock_service);

        $response = $controller->deleteFromDrupalPath("foo");

        $this->assertTrue(
            $response->getStatusCode() == 500,
            "Response must be 500 when Exception occurs"
        );
    }

    public function testReturns404WhenNotFound()
    {
        $mock_service = $this->prophesize(GeminiService::class)
            ->reveal();
        $controller = new GeminiController($mock_service);

        $response = $controller->deleteFromDrupalPath("foo");

        $this->assertTrue(
            $response->getStatusCode() == 404,
            "Response must be 404 when not found"
        );
    }

    public function testReturns204WhenDeleted()
    {
        $prophecy = $this->prophesize(GeminiService::class);
        $prophecy->deleteFromDrupalPath("foo")
            ->willReturn(true);
        $mock_service = $prophecy->reveal();
        $controller = new GeminiController($mock_service);

        $response = $controller->deleteFromDrupalPath("foo");

        $this->assertTrue(
            $response->getStatusCode() == 204,
            "Response must be 204 when deleted"
        );
    }
}
