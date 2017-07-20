<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\Controller\GeminiController;
use Islandora\Gemini\UrlMapper\UrlMapperInterface;
use Islandora\Gemini\UrlMinter\UrlMinterInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class PutTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\Controller\GeminiController
 */
class PutTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::put
     */
    public function testReturns204OnUpdate()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class);
        $mapper->saveUrls(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(false);
        $mapper = $mapper->reveal();

        $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $json_str = '{"drupal" : "http://localhost:8000/node/1?_format=jsonld", "fedora" : ' .
            '"http://localhost:8080/fcrepo/rest/5d/15/0b/3a/5d150b3a-9d1b-437f-87a9-104b8cf15859"}';

        $request = Request::create(
            "/5d150b3a-9d1b-437f-87a9-104b8cf15859",
            "PUT",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json_str
        );

        $response = $controller->put("5d150b3a-9d1b-437f-87a9-104b8cf15859", $request);

        $this->assertTrue(
            $response->getStatusCode() == 204,
            "Response must be 204 on update"
        );
    }

    /**
     * @covers ::put
     */
    public function testReturns201OnCreation()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class);
        $mapper->saveUrls(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(true);
        $mapper = $mapper->reveal();

        $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

        $generator = $this->prophesize(UrlGenerator::class);
        $generator->generate(Argument::any(), Argument::any(), Argument::any())
            ->willReturn("http://localhost:8000/gemini/5d150b3a-9d1b-437f-87a9-104b8cf15859");
        $generator = $generator->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        $json_str = '{"drupal" : "http://localhost:8000/node/1?_format=jsonld", "fedora" : ' .
            '"http://localhost:8080/fcrepo/rest/5d/15/0b/3a/5d150b3a-9d1b-437f-87a9-104b8cf15859"}';

        $request = Request::create(
            "/5d150b3a-9d1b-437f-87a9-104b8cf15859",
            "PUT",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json_str
        );

        $response = $controller->put("5d150b3a-9d1b-437f-87a9-104b8cf15859", $request);

        $this->assertTrue(
            $response->getStatusCode() == 201,
            "Response must be 201 on create"
        );

        $this->assertTrue(
            $response->headers->has("Location"),
            "201 response must contain Location header."
        );
    }

    /**
     * @covers ::put
     */
    public function testReturns400OnMalformedRequest()
    {
        $mapper = $this->prophesize(UrlMapperInterface::class)->reveal();

        $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

        $generator = $this->prophesize(UrlGenerator::class)->reveal();

        $controller = new GeminiController(
            $mapper,
            $minter,
            $generator
        );

        // Test non-JSON
        $request = Request::create(
            "/5d150b3a-9d1b-437f-87a9-104b8cf15859",
            "PUT",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            'some garbage'
        );

        $response = $controller->put("5d150b3a-9d1b-437f-87a9-104b8cf15859", $request);

        $this->assertTrue(
            $response->getStatusCode() == 400,
            "Response must be 400 on non JSON requests"
        );

        // Test missing 'drupal'
        $request = Request::create(
            "/5d150b3a-9d1b-437f-87a9-104b8cf15859",
            "PUT",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"fedora" : "http://localhost:8080/fcrepo/rest/5d/15/0b/3a/5d150b3a-9d1b-437f-87a9-104b8cf15859"}'
        );

        $response = $controller->put("5d150b3a-9d1b-437f-87a9-104b8cf15859", $request);

        $this->assertTrue(
            $response->getStatusCode() == 400,
            "Response must be 400 when 'drupal' entry is missing in request body"
        );

        // Test missing 'fedora'
        $request = Request::create(
            "/5d150b3a-9d1b-437f-87a9-104b8cf15859",
            "PUT",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"drupal" : "http://localhost:8000/node/1?_format=jsonld"}'
        );

        $response = $controller->put("5d150b3a-9d1b-437f-87a9-104b8cf15859", $request);

        $this->assertTrue(
            $response->getStatusCode() == 400,
            "Response must be 400 when 'fedora' entry is missing in request body"
        );
    }
}
