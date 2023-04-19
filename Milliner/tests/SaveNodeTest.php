<?php

namespace App\Islandora\Milliner\Tests;

use donatj\MockWebServer\ResponseByMethod;
use GuzzleHttp\Psr7\Response;
use App\Islandora\Milliner\Service\MillinerService;
use Prophecy\Argument;

/**
 * Class MillinerServiceTest
 * @package \App\Islandora\Milliner\Tests
 * @coversDefaultClass \App\Islandora\Milliner\Service\MillinerService
 */
class SaveNodeTest extends AbstractMillinerTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/static/Content.jsonld')
        );
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::processJsonld
     */
    public function testCreateNodeThrowsOnFedoraError()
    {
        $milliner = $this->setupMilliner($this->not_found_response, null, $this->unauthorized_response);

        $this->expectException(\RuntimeException::class, null, 401);

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     */
    public function testCreateNodeReturnsFedora201()
    {
        $milliner = $this->setupMilliner($this->not_found_response, null, $this->created_response);

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Milliner must return 201 when Fedora returns 201.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::createNode
     * @covers ::processJsonld
     */
    public function testCreateNodeReturnsFedora204()
    {
        $milliner = $this->setupMilliner($this->not_found_response, null, $this->no_content_response);

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
     */
    public function testUpdateNodeThrowsOnFedoraError()
    {
        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents(__DIR__ . '/static/ContentLDP-RS.jsonld'),
            ['Content-Type' => 'application/ld+json'],
            200
        );

        $milliner = $this->setupMilliner($this->ok_response, $fedora_get_response, $this->unauthorized_response);

        $this->expectException(\RuntimeException::class, null, 403);

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
     */
    public function testUpdateNodeThrows500OnBadDatePredicate()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/static/StaleContent.jsonld')
        );
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents(__DIR__ . '/static/ContentLDP-RS.jsonld'),
            ['Content-Type' => 'application/ld+json'],
            200
        );

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner = $this->setupMilliner($this->ok_response, $fedora_get_response, null);

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
     */
    public function testUpdateNodeThrows412OnStaleContent()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/static/StaleContent.jsonld')
        );
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents(__DIR__ . '/static/ContentLDP-RS.jsonld'),
            ['Content-Type' => 'application/ld+json'],
            200
        );

        $milliner = $this->setupMilliner($this->ok_response, $fedora_get_response, null);

        $this->expectException(\RuntimeException::class, null, 412);

        $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
     */
    public function testUpdateNodeReturnsFedora201()
    {
        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents(__DIR__ . '/static/ContentLDP-RS.jsonld'),
            ['Content-Type' => 'application/ld+json'],
            200,
        );
        $milliner = $this->setupMilliner($this->ok_response, $fedora_get_response, $this->created_response);

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 201,
            "Milliner must return 201 when Fedora returns 201.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
     */
    public function testUpdateNodeReturnsFedora204()
    {

        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents(__DIR__ . '/static/ContentLDP-RS.jsonld'),
            ['Content-Type' => 'application/ld+json'],
            200
        );
        $milliner = $this->setupMilliner($this->ok_response, $fedora_get_response, $this->no_content_response);

        $response = $milliner->saveNode(
            $this->uuid,
            "http://localhost:8000/node/1?_format=jsonld",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }

    /**
     * Utility function to setup a MillinerService
     *
     * @param \donatj\MockWebServer\Response|null $fedora_head_response
     *   The response Fedora will return to the HEAD request, if null don't set the prophecy.
     * @param \donatj\MockWebServer\Response|null $fedora_get_response
     *   The response Fedora will return to the GET request, if null don't set the prophecy.
     * @param \donatj\MockWebServer\Response|null $fedora_save_response
     *   The response Fedora will return to the PUT request, if null don't set the prophecy.
     *
     * @return \App\Islandora\Milliner\Service\MillinerService
     */
    private function setupMilliner(
        ?\donatj\MockWebServer\Response $fedora_head_response,
        ?\donatj\MockWebServer\Response $fedora_get_response,
        ?\donatj\MockWebServer\Response $fedora_save_response
    ): MillinerService {

        $by_method = [];
        if ($fedora_head_response !== null) {
            $by_method[ResponseByMethod::METHOD_HEAD] = $fedora_head_response;
        }
        if ($fedora_get_response != null) {
            $by_method[ResponseByMethod::METHOD_GET] = $fedora_get_response;
        }
        if ($fedora_save_response !== null) {
            $by_method[ResponseByMethod::METHOD_PUT] = $fedora_save_response;
        }
        if (count($by_method) > 0) {
            self::$webserver->setResponseOfPath(
                $this->fedora_path,
                new ResponseByMethod(
                    $by_method
                )
            );
        }

        return $this->getMilliner();
    }
}
