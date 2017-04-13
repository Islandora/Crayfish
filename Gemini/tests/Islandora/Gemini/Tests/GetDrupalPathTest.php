<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\Controller\GeminiController;
use Islandora\Crayfish\Commons\PathMapper\PathMapper;

class GetDrupalPathTest extends \PHPUnit_Framework_TestCase
{
    public function testReturns500OnException()
    {
        $prophecy = $this->prophesize(PathMapper::class);
        $prophecy->getDrupalPath("foo")
            ->willThrow(new \Exception("Exception", 500));
        $mock_service = $prophecy->reveal();
        $controller = new GeminiController($mock_service);

        $response = $controller->getDrupalPath("foo");

        $this->assertTrue(
            $response->getStatusCode() == 500,
            "Response must be 500 when Exception occurs"
        );
    }

    public function testReturns404WhenNotFound()
    {
        $mock_service = $this->prophesize(PathMapper::class)
            ->reveal();
        $controller = new GeminiController($mock_service);

        $response = $controller->getDrupalPath("foo");

        $this->assertTrue(
            $response->getStatusCode() == 404,
            "Response must be 404 when not found"
        );
    }

    public function testReturns200WhenFound()
    {
        $prophecy = $this->prophesize(PathMapper::class);
        $prophecy->getDrupalPath("foo")
            ->willReturn("bar");
        $mock_service = $prophecy->reveal();
        $controller = new GeminiController($mock_service);

        $response = $controller->getDrupalPath("foo");

        $this->assertTrue(
            $response->getStatusCode() == 200,
            "Response must be 200 when found"
        );
        $this->assertTrue(
            $response->getContent() == "bar",
            "Response must return path when found"
        );
    }
}
