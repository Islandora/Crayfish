<?php

namespace App\Islandora\Recast\Tests;

use Islandora\Crayfish\Commons\EntityMapper\EntityMapper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use App\Islandora\Recast\Controller\RecastController;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RecastControllerTest
 *
 * @package App\Islandora\Recast\Tests
 * @coversDefaultClass \App\Islandora\Recast\Controller\RecastController
 */
class RecastControllerTest extends TestCase
{
    use ProphecyTrait;

    private $http_prophecy;

    private $logger_prophecy;

    private $drupal_base_url = 'localhost:8000';

    private $fedora_base_url = 'localhost:8080/fcrepo/rest';

    private $namespaces = [
        "fedora" => "http://fedora.info/definitions/v4/repository#",
        "pcdm" => "http://pcdm.org/models#",
    ];

  /**
   * {@inheritdoc}
   */
    public function setUp(): void
    {
        $this->http_prophecy = $this->prophesize(Client::class);
        $this->logger_prophecy = $this->prophesize(Logger::class);
    }

  /**
   * @covers ::recastOptions
   */
    public function testOptions()
    {
        $controller = $this->getController();

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
        $node_1 = realpath(__DIR__ . '/resources/node1.json');

        $this->http_prophecy->get('http://localhost:8000/user/1?_format=json', Argument::any())
            ->willThrow(
                new RequestException(
                    "NOT FOUND",
                    new GuzzleRequest('GET', 'http://localhost:8000/user/1?_format=json')
                )
            );
        $this->http_prophecy->get('http://localhost:8000/media/1?_format=json', Argument::any())
            ->willThrow(new RequestException(
                "NOT FOUND",
                new GuzzleRequest('GET', 'http://localhost:8000/media/1?_format=json')
            ));
        $this->http_prophecy->get('http://localhost:8000/node/1?_format=json', Argument::any())
            ->willReturn(new GuzzleResponse(200, [], file_get_contents($node_1)));

        $mock_fedora_response = $this->getMockFedoraStream();

        $controller = $this->getController();

        $request = Request::create(
            "/add",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', $resource_id);
        $request->headers->set('Accept', 'application/ld+json');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        // Do with add
        $response = $controller->recast($request, 'add');
        $this->assertEquals(200, $response->getStatusCode(), "Invalid status code");
        $json = json_decode($response->getContent(), true);

        $expected = json_decode(file_get_contents($output_add), true);
        $this->assertEquals($expected, $json, "Response does not match expected additions.");

        // Do with replace
        $response = $controller->recast($request, 'replace');
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

        $controller = $this->getController();

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
        $response = $controller->recast($request, 'oops');
        $this->assertEquals($response->getStatusCode(), 400, "Invalid status code");
    }

    /**
     * @covers ::recast
     */
    public function testPrefixes()
    {
        $resource_id = 'http://localhost:8080/fcrepo/rest/object1';

        $node_1 = realpath(__DIR__ . '/resources/node1.json');

        $this->http_prophecy->get('http://localhost:8000/user/1?_format=json', Argument::any())
            ->willThrow(
                new RequestException(
                    "NOT FOUND",
                    new GuzzleRequest('GET', 'http://localhost:8000/user/1?_format=json')
                )
            );
        $this->http_prophecy->get('http://localhost:8000/node/1?_format=json', Argument::any())
            ->willReturn(new GuzzleResponse(200, [], file_get_contents($node_1)));

        $mock_fedora_response = $this->getMockFedoraStream(
            realpath(__DIR__ . '/resources/drupal_image.ttl'),
            'text/turtle'
        );

        $controller = $this->getController();

        $request = Request::create(
            "/add",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set('Apix-Ldp-Resource', $resource_id);
        $request->headers->set('Accept', 'text/turtle');
        $request->attributes->set('fedora_resource', $mock_fedora_response);

        $response = $controller->recast($request, 'add');
        $body = $response->getContent();
        $this->assertStringContainsString('fedora:', $body, "Did not find fedora: prefix");
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

    /**
     * Utility to get the controller.
     * @return \App\Islandora\Recast\Controller\RecastController
     */
    private function getController(): RecastController
    {
        return new RecastController(
            new EntityMapper(),
            $this->http_prophecy->reveal(),
            $this->logger_prophecy->reveal(),
            $this->drupal_base_url,
            $this->fedora_base_url,
            $this->namespaces
        );
    }
}
