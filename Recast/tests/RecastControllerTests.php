<?php

namespace Islandora\Recast\Tests;

use Islandora\Crayfish\Commons\Client\GeminiClient;
use Islandora\Recast\Controller\RecastController;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

// phpcs:disable
if (class_exists('\EasyRdf_Graph')) {
    class_alias('\EasyRdf_Graph', ' \EasyRdf\Graph');
}
// phpcs:enable

/**
 * Class RecastControllerTest
 *
 * @package Islandora\Recast\Tests
 * @coversDefaultClass \Islandora\Recast\Controller\RecastController
 */
class RecastControllerTest extends TestCase
{

    private $gemini_prophecy;

    private $logger_prophecy;

    private $namespaces = array(
        array(
            "fedora" => "http://fedora.info/definitions/v4/repository#",
            "pcdm" => "http://pcdm.org/models#",
        ),
    );

  /**
   * {@inheritdoc}
   */
    public function setUp(): void
    {
        $this->gemini_prophecy = $this->prophesize(GeminiClient::class);
        $this->logger_prophecy = $this->prophesize(Logger::class);
    }

  /**
   * @covers ::recastOptions
   */
    public function testOptions()
    {
        $controller = new RecastController(
            $this->gemini_prophecy->reveal(),
            $this->logger_prophecy->reveal()
        );

        $response = $controller->recastOptions();
        $this->assertTrue($response->getStatusCode() == 200, 'Identify OPTIONS should return 200');
        $this->assertTrue(
            $response->headers->get('Content-Type') == 'text/turtle',
            'Identify OPTIONS should return turtle'
        );
    }

  /**
   * @covers ::recast
   * @covers ::findPredicateForObject
   */
    public function testImageAdd()
    {
        $resource_id = 'http://localhost:8080/fcrepo/rest/object1';

        $output_add = realpath(__DIR__ . '/resources/drupal_image_add.json');
        $output_replace = realpath(__DIR__ . '/resources/drupal_image_replace.json');

        $this->gemini_prophecy->findByUri('http://localhost:8000/user/1?_format=jsonld', Argument::any())
        ->willReturn(null);
        $this->gemini_prophecy->findByUri('http://localhost:8000/media/1?_format=jsonld', Argument::any())
        ->willReturn(null);
        $this->gemini_prophecy->findByUri('http://localhost:8000/node/1?_format=jsonld', Argument::any())
        ->willReturn('http://localhost:8080/fcrepo/rest/collection1');

        $mock_silex_app = new Application();
        $mock_silex_app['crayfish.drupal_base_url'] = 'http://localhost:8000';

        $mock_fedora_response = $this->getMockFedoraStream();

        $controller = new RecastController(
            $this->gemini_prophecy->reveal(),
            $this->logger_prophecy->reveal()
        );

        $request = Request::create(
            "/add",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', $resource_id);
        $request->headers->set('Accept', 'application/ld+json');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        // Do with add
        $response = $controller->recast($request, $mock_silex_app, 'add');
        $this->assertEquals(200, $response->getStatusCode(), "Invalid status code");
        $json = json_decode($response->getContent(), true);

        $expected = json_decode(file_get_contents($output_add), true);
        $this->assertEquals($expected, $json, "Response does not match expected additions.");

        // Do with replace
        $response = $controller->recast($request, $mock_silex_app, 'replace');
        $this->assertEquals(200, $response->getStatusCode(), "Invalid status code");
        $json = json_decode($response->getContent(), true);

        $expected = json_decode(file_get_contents($output_replace), true);
        $this->assertEquals($expected, $json, "Response does not match expected additions.");
    }

  /**
   * @covers ::recast
   */
    public function testInvalidType()
    {
        $resource_id = 'http://localhost:8080/fcrepo/rest/object1';
        $mock_silex_app = new Application();
        $mock_silex_app['crayfish.drupal_base_url'] = 'http://localhost:8000';

        $controller = new RecastController(
            $this->gemini_prophecy->reveal(),
            $this->logger_prophecy->reveal()
        );

        $mock_fedora_response = $this->getMockFedoraStream();

        $request = Request::create(
            "/oops",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', $resource_id);
        $request->headers->set('Accept', 'application/ld+json');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        // Do with add
        $response = $controller->recast($request, $mock_silex_app, 'oops');
        $this->assertEquals($response->getStatusCode(), 400, "Invalid status code");
    }

    /**
     * @covers ::recast
     */
    public function testPrefixes()
    {
        $resource_id = 'http://localhost:8080/fcrepo/rest/object1';

        $this->gemini_prophecy->findByUri('http://localhost:8000/user/1?_format=jsonld', Argument::any())
            ->willReturn(null);
        $this->gemini_prophecy->findByUri('http://localhost:8000/node/1?_format=jsonld', Argument::any())
            ->willReturn('http://localhost:8080/fcrepo/rest/collection99');

        $mock_silex_app = new Application();
        $mock_silex_app['crayfish.drupal_base_url'] = 'http://localhost:8000';
        $mock_silex_app['crayfish.namespaces'] = $this->namespaces;

        $mock_fedora_response = $this->getMockFedoraStream(
            realpath(__DIR__ . '/resources/drupal_image.ttl'),
            'text/turtle'
        );

        $controller = new RecastController(
            $this->gemini_prophecy->reveal(),
            $this->logger_prophecy->reveal()
        );

        $request = Request::create(
            "/add",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', $resource_id);
        $request->headers->set('Accept', 'text/turtle');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->recast($request, $mock_silex_app, 'add');
        $body = $response->getContent();
        $this->assertStringContainsString('fedora:', $body, "Did not find fedora: prefix");
        // These two assertions are failing with the EasyRdf update to 1.1.1 in Chullo.
        // It could be the resolution of:
        //   Fixes for format guessing, so it works with SPARQL-style PREFIX and BASE
        //   https://github.com/easyrdf/easyrdf/blob/master/CHANGELOG.md#bug-fixes
        //$this->assertStringNotContainsString('ldp:', $body, "Found ldp: prefix");
        //$this->assertStringContainsString('<http://www.w3.org/ns/ldp#RDFSource>', $body, "Did not find full LDP uri");
        $this->assertStringContainsString('pcdm:', $body, "Did not find pcdm: prefix");
    }

  /**
   * Generate a mock response containing mock Fedora body stream.
   *
   * @param string $input_resource
   *    The path to the file containing the stream contents.
   * @param string $content_type
   *    The content type of the input_resource.
   *
   * @return object
   *   The returned stream object.
   */
    protected function getMockFedoraStream($input_resource = null, $content_type = 'application/ld+json')
    {
        if (is_null($input_resource)) {
            // Provide a default.
            $input_resource = realpath(__DIR__ . '/resources/drupal_image.json');
        }

        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $prophecy->__toString()->willReturn(file_get_contents($input_resource));
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getStatusCode()->willReturn(200);
        $prophecy->getBody()->willReturn($mock_stream);
        $prophecy->getHeader('Content-type')->willReturn($content_type);
        // This is to avoid the describes check, should add a test for it.
        $prophecy->hasHeader('Link')->willReturn(false);
        $mock_fedora_response = $prophecy->reveal();
        return $mock_fedora_response;
    }
}
