<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\Controller\GeminiController;
use Islandora\Gemini\UrlMapper\UrlMapperInterface;
use Islandora\Gemini\UrlMinter\UrlMinterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class GetByUriTest
 *
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\Controller\GeminiController
 */
class GetByUriTest extends TestCase
{

  /**
   * @covers ::getByUri
   */
  public function testGetByUriOk()
  {
    $mapper = $this->prophesize(UrlMapperInterface::class);
    $mapper->findUrls(Argument::any())
      ->willReturn(['uri' => 'abc']);
    $mapper = $mapper->reveal();

    $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

    $generator = $this->prophesize(UrlGenerator::class)->reveal();

    $request = new Request();
    $request->headers->add(['X-Islandora-URI' => 'blah']);

    $controller = new GeminiController(
      $mapper,
      $minter,
      $generator
    );

    $response = $controller->getByUri($request);

    $this->assertEquals(
      200,
      $response->getStatusCode(),
      "Response must be 200 on success"
    );
    $this->assertTrue(
      $response->headers->has('Location'),
      "Response must have Location header"
    );
    $this->assertEquals(
      'abc',
      $response->headers->get('Location'),
      "Location header should be 'abc'"
    );
  }

  /**
   * @covers ::getByUri
   */
  public function testGetByUriFailed()
  {
    $mapper = $this->prophesize(UrlMapperInterface::class);
    $mapper->findUrls(Argument::any())
      ->willReturn([]);
    $mapper = $mapper->reveal();

    $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

    $generator = $this->prophesize(UrlGenerator::class)->reveal();

    $request = new Request();
    $request->headers->add(['X-Islandora-URI' => 'blah']);

    $controller = new GeminiController(
      $mapper,
      $minter,
      $generator
    );

    $response = $controller->getByUri($request);

    $this->assertEquals(
      404,
      $response->getStatusCode(),
      "Response must be 200 on success"
    );
  }

  /**
   * @covers ::getByUri
   */
  public function testGetByUriMultiple()
  {
    $mapper = $this->prophesize(UrlMapperInterface::class);
    $mapper->findUrls('foo')
      ->willReturn(['uri' => 'abc']);
    $mapper->findUrls('bar')
      ->willReturn(['uri' => 'oops']);
    $mapper = $mapper->reveal();

    $minter = $this->prophesize(UrlMinterInterface::class)->reveal();

    $generator = $this->prophesize(UrlGenerator::class)->reveal();

    $request = new Request();
    $request->headers->add(['X-Islandora-URI' => ['foo', 'bar']]);

    $controller = new GeminiController(
      $mapper,
      $minter,
      $generator
    );

    $response = $controller->getByUri($request);

    $this->assertEquals(
      200,
      $response->getStatusCode(),
      "Response must be 200 on success"
    );
    $this->assertTrue(
      $response->headers->has('Location'),
      "Response must have Location header"
    );
    $this->assertEquals(
      'abc',
      $response->headers->get('Location'),
      "Location header should be 'abc'"
    );
  }

  /**
   * @covers ::getByUri
   */
  public function testGetByUriNoToken()
  {
    $mapper = $this->prophesize(UrlMapperInterface::class)->reveal();
    $minter = $this->prophesize(UrlMinterInterface::class)->reveal();
    $generator = $this->prophesize(UrlGenerator::class)->reveal();

    $request = new Request();

    $controller = new GeminiController(
      $mapper,
      $minter,
      $generator
    );

    $response = $controller->getByUri($request);

    $this->assertEquals(
      400,
      $response->getStatusCode(),
      "Response must be 400 with no X-Islandora-URI header"
    );
  }
}
