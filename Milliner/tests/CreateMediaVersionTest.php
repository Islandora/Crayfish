<?php

namespace App\Islandora\Milliner\Tests;

use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseByMethod;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Prophecy\Argument;

/**
 * @coversDefaultClass \App\Islandora\Milliner\Service\MillinerService
 */
class CreateMediaVersionTest extends AbstractMillinerTestCase
{
    private const JSON_URL = 'http://localhost:8000/media/6?_format=json';

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildFedoraUris('ffb15b4f-54db-44ce-ad0b-3588889a3c9b');
    }

    /**
     * @covers ::__construct
     * @covers ::createMediaVersion
     * @covers ::getMediaUrls
     * @covers ::getLinkHeader
     */
    public function testCreateMediaVersionReturnsFedora201()
    {
        $this->setUpMediaLookup($this->created_response);

        $response = $this->getMilliner()->createMediaVersion(
            'field_image',
            self::JSON_URL,
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );

        $this->assertSame(201, $response->getStatusCode());
        $request = self::$webserver->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame('POST', $request->getRequestMethod());
        $headers = array_change_key_case($request->getHeaders(), CASE_LOWER);
        $this->assertSame('Bearer islandora', $headers['authorization']);
    }

    /**
     * @covers ::__construct
     * @covers ::createMediaVersion
     * @covers ::getMediaUrls
     * @covers ::getLinkHeader
     */
    public function testCreateMediaVersionThrowsOnFedoraError()
    {
        $this->setUpMediaLookup($this->forbidden_response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

        $this->getMilliner()->createMediaVersion(
            'field_image',
            self::JSON_URL,
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );
    }

    private function setUpMediaLookup(Response $versionResponse): void
    {
        $link = '<http://localhost:8000/media/6?_format=jsonld>; rel="alternate"; ' .
            'type="application/ld+json", ' .
            '<http://localhost:8000/sites/default/files/sample.jpeg>; rel="describes"';
        $this->drupal_client_prophecy->get(self::JSON_URL, Argument::any())
            ->willReturn(
                new Psr7Response(
                    200,
                    ['Content-Type' => 'application/json', 'Link' => $link],
                    file_get_contents($this->getStaticFile('Media.json'))
                )
            );

        $metadataUrl = $this->fedora_full_uri . '/fcr:metadata';
        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willReturn(
                new Psr7Response(
                    200,
                    ['Link' => "<$metadataUrl>; rel=\"describedby\""]
                )
            );

        self::$webserver->setResponseOfPath(
            $this->fedora_path . '/fcr:metadata',
            new ResponseByMethod([
                ResponseByMethod::METHOD_HEAD => new Response(
                    '',
                    ['Link' => "<$metadataUrl/fcr:versions>; rel=\"timemap\""],
                    200
                ),
            ])
        );
        self::$webserver->setResponseOfPath(
            $this->fedora_path . '/fcr:metadata/fcr:versions',
            new ResponseByMethod([
                ResponseByMethod::METHOD_POST => $versionResponse,
            ])
        );
    }
}
