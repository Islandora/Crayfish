<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\Controller\GeminiController;
use Islandora\Gemini\UrlMapper\UrlMapperInterface;
use Islandora\Gemini\UrlMinter\UrlMinterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class DeleteTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\Controller\GeminiController
 */
class DeleteTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::delete
     */
    public function testReturns404WhenNotFound()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class);
        $mapper->deleteUrls(Argument::any())
            ->willReturn(0);
        $mapper = $mapper->reveal();

        $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $response = $controller->delete("abc");

        $this->assertTrue(
            $response->getStatusCode() == 404,
            "Response must be 404 when not found"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     */
    public function testReturns204WhenDeleted()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class);
        $mapper->deleteUrls(Argument::any())
            ->willReturn(1);
        $mapper = $mapper->reveal();

        $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $response = $controller->delete("abc");

        $this->assertTrue(
            $response->getStatusCode() == 204,
            "Response must be 204 when deleted"
        );
    }
}
