<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\Controller\GeminiController;
use Islandora\Gemini\UrlMapper\UrlMapperInterface;
use Islandora\Gemini\UrlMinter\UrlMinter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class PostTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\Controller\GeminiController
 */
class PostTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::post
     */
    public function testReturns400OnMalformedRequest()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class)->reveal();

        $minter = new UrlMinter("http://localhost:8080/fcrepo/rest");

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $request = Request::create(
            "/",
            "POST",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            ''
        );

        $response = $controller->post($request);

        $this->assertTrue(
            $response->getStatusCode() == 400,
            "Response must be 400 on empty request"
        );

        $request = Request::create(
            "/",
            "POST",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            'abc'
        );

        $response = $controller->post($request);

        $this->assertTrue(
            $response->getStatusCode() == 400,
            "Response must be 400 on request with UUID of length < 8"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::post
     */
    public function testReturns200OnSuccess()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class)->reveal();

        $minter = new UrlMinter("http://localhost:8080/fcrepo/rest");

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $request = Request::create(
            "/",
            "POST",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            '5d150b3a-9d1b-437f-87a9-104b8cf15859'
        );

        $response = $controller->post($request);

        $this->assertTrue(
            $response->getStatusCode() == 200,
            "Response must be 200 when given a proper UUID"
        );
    }
}
