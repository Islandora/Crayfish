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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(401);

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

        $milliner = $this->setupMilliner($this->ok_response, $fedora_get_response, $this->forbidden_response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

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
        $drupal_jsonld = json_decode(
            file_get_contents(__DIR__ . '/static/StaleContent.jsonld'),
            true
        );
        $drupal_jsonld['@graph'][0]['http://schema.org/dateModified'][0]['@value'] = 'not-a-date';
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            json_encode($drupal_jsonld)
        );
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents(__DIR__ . '/static/ContentLDP-RS.jsonld'),
            ['Content-Type' => 'application/ld+json'],
            200
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(500);

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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(412);

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
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::updateNode
     * @covers ::processJsonld
     * @covers ::getModifiedTimestamp
     * @covers ::getFirstPredicate
     */
    public function testUpdateNodeUsesFedora6StateTokenHeaders()
    {
        $this->isFedora6 = true;
        $fedoraGetResponse = new \donatj\MockWebServer\Response(
            file_get_contents(__DIR__ . '/static/ContentLDP-RS.jsonld'),
            [
                'Content-Type' => 'application/ld+json',
                'X-State-Token' => 'abc123',
            ],
            200
        );
        $milliner = $this->setupMilliner(
            $this->ok_response,
            $fedoraGetResponse,
            $this->no_content_response
        );

        $response = $milliner->saveNode(
            $this->uuid,
            'http://localhost:8000/node/1?_format=jsonld',
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );

        $this->assertSame(204, $response->getStatusCode());
        $headRequest = self::$webserver->getRequestByOffset(-3);
        $getRequest = self::$webserver->getRequestByOffset(-2);
        $putRequest = self::$webserver->getRequestByOffset(-1);
        $this->assertNotNull($headRequest);
        $this->assertNotNull($getRequest);
        $this->assertNotNull($putRequest);
        $headHeaders = array_change_key_case($headRequest->getHeaders(), CASE_LOWER);
        $getHeaders = array_change_key_case($getRequest->getHeaders(), CASE_LOWER);
        $putHeaders = array_change_key_case($putRequest->getHeaders(), CASE_LOWER);

        $this->assertSame('HEAD', $headRequest->getRequestMethod());
        $this->assertSame('Bearer islandora', $headHeaders['authorization']);
        $this->assertSame('GET', $getRequest->getRequestMethod());
        $this->assertSame(
            'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
            $getHeaders['prefer']
        );
        $this->assertSame('PUT', $putRequest->getRequestMethod());
        $this->assertSame('"abc123"', $putHeaders['x-if-state-match']);
        $this->assertSame('handling=lenient', $putHeaders['prefer']);
        $this->assertStringNotContainsString('received=minimal', $putHeaders['prefer']);
        $payload = json_decode($putRequest->getInput(), true);
        $this->assertSame($this->fedora_full_uri, $payload[0]['@id']);
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::createNode
     * @covers ::processJsonld
     */
    public function testCreateNodeFindsSubjectAfterOtherGraphEntries()
    {
        $jsonld = json_decode(file_get_contents(__DIR__ . '/static/Content.jsonld'), true);
        array_unshift($jsonld['@graph'], ['@id' => 'http://localhost:8000/node/other']);
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'application/ld+json'], json_encode($jsonld)));
        $milliner = $this->setupMilliner($this->not_found_response, null, $this->created_response);

        $response = $milliner->saveNode(
            $this->uuid,
            'http://localhost:8000/node/1?_format=jsonld',
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );

        $this->assertSame(201, $response->getStatusCode());
        $request = self::$webserver->getLastRequest();
        $this->assertNotNull($request);
        $payload = json_decode($request->getInput(), true);
        $this->assertSame($this->fedora_full_uri, $payload[0]['@id']);
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::createNode
     * @covers ::processJsonld
     */
    public function testCreateNodeThrowsWhenSubjectIsMissingFromGraph()
    {
        $jsonld = ['@graph' => [['@id' => 'http://localhost:8000/node/other']]];
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'application/ld+json'], json_encode($jsonld)));
        $milliner = $this->setupMilliner($this->not_found_response, null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Could not find Drupal resource');

        $milliner->saveNode(
            $this->uuid,
            'http://localhost:8000/node/1?_format=jsonld',
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveNode
     * @covers ::createNode
     * @covers ::processJsonld
     * @covers ::getJsonLdSubjectUrl
     */
    public function testFormatRemovalDoesNotTrimCharactersFromDrupalPath()
    {
        $this->stripJsonLd = true;
        $drupalUrl = 'http://localhost:8000/node/abcd?_format=jsonld';
        $jsonld = [
            '@graph' => [[
                '@id' => 'http://localhost:8000/node/abcd',
                'http://schema.org/dateModified' => [['@value' => '2026-07-29T10:00:00+00:00']],
            ]],
        ];
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'application/ld+json'], json_encode($jsonld)));
        $milliner = $this->setupMilliner($this->not_found_response, null, $this->created_response);

        $response = $milliner->saveNode(
            $this->uuid,
            $drupalUrl,
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );

        $this->assertSame(201, $response->getStatusCode());
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
