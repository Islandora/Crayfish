<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\Controller\GeminiController;
use Islandora\Gemini\UrlMapper\UrlMapperInterface;
use Islandora\Gemini\UrlMinter\UrlMinterInterface;
use Prophecy\Argument;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class GetTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\Controller\GeminiController
 */
class GetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testReturns404WhenNotFound()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class);
        $mapper->getUrls(Argument::any())
            ->willReturn([]);
        $mapper = $mapper->reveal();

        $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $response = $controller->get("abc");

        $this->assertTrue(
            $response->getStatusCode() == 404,
            "Response must be 404 when not found"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testReturns200WhenFound()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class);
        $mapper->getUrls(Argument::any())
            ->willReturn(['drupal' => 'foo', 'fedora' => 'bar']);
        $mapper = $mapper->reveal();

        $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $response = $controller->get("abc");

        $this->assertTrue(
            $response->getStatusCode() == 200,
            "Response must be 200 when found"
        );
    }
}
