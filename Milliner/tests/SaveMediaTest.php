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
class SaveMediaTest extends AbstractMillinerTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->rebuildFedoraUris('ffb15b4f-54db-44ce-ad0b-3588889a3c9b');
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows500WithNoFileField()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents($this->getStaticFile('MediaNoFileField.json'))
        );

        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows500WithEmptyFileField()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents($this->getStaticFile('MediaEmptyFileField.json'))
        );

        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows404WhenFileIsNotInGemini()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents($this->getStaticFile('Media.json'))
        );

        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class, null, 404);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrowsFedoraHeadError()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents($this->getStaticFile('Media.json'))
        );

        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $head_response = new Response(404);
        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willReturn($head_response);

        $this->expectException(\RuntimeException::class, null, 404);

        $milliner = $this->getMilliner();

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows500WhenNoDescribedbyHeader()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents($this->getStaticFile('Media.json'))
        );

        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $head_response = new Response(200);
        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willReturn($head_response);

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrowsFedoraGetError()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents($this->getStaticFile('Media.json'))
        );
        $this->drupal_client_prophecy->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $link = '<http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b/fcr:metadata>';
        $link .= ';rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' =>  $link]
        );
        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willReturn($head_response);

        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            new ResponseByMethod([
                ResponseByMethod::METHOD_GET => $this->not_found_response,
            ])
        );
        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class, null, 404);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows412OnStaleData()
    {
        $drupal_json_response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                "Link" => '<http://localhost:8000/media/6?_format=jsonld>; rel="alternate"; type="application/ld+json"',
            ],
            file_get_contents($this->getStaticFile('Media.json'))
        );
        $drupal_jsonld_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents($this->getStaticFile('StaleMedia.jsonld'))
        );

        $this->drupal_client_prophecy->get('http://localhost:8000/media/6?_format=json', Argument::any())
            ->willReturn($drupal_json_response);
        $this->drupal_client_prophecy->get('http://localhost:8000/media/6?_format=jsonld', Argument::any())
            ->willReturn($drupal_jsonld_response);

        $link = '<http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b/fcr:metadata>';
        $link .= '; rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' => $link]
        );
        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willReturn($head_response);

        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents($this->getStaticFile('MediaLDP-RS.jsonld')),
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            200
        );
        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            new ResponseByMethod([
                ResponseByMethod::METHOD_GET => $fedora_get_response,
            ])
        );

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class, null, 412);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrowsFedoraPutError()
    {

        $milliner = $this->setupMillinerSave('MediaLDP-RS.jsonld', $this->forbidden_response);

        $this->expectException(\RuntimeException::class, null, 403);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaReturnsFedoraSuccess()
    {
        $milliner = $this->setupMillinerSave('MediaLDP-RS.jsonld', $this->no_content_response);

        $response = $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
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
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaReturnsNoModifiedDate()
    {

        $milliner = $this->setupMillinerSave('MediaLDP-RS-no_date.jsonld', $this->no_content_response);

        $response = $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
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
     * Utility to setup mock clients for a milliner service.
     *
     * @param string $mediaResponseFilename
     *   The file to use as the response to the Fedora request.
     * @param \donatj\MockWebServer\Response $fedora_put_response
     *   The response to return when attempting to PUT to Fedora.
     *
     * @return \App\Islandora\Milliner\Service\MillinerService
     */
    private function setupMillinerSave(
        string $mediaResponseFilename,
        \donatj\MockWebServer\Response $fedora_put_response
    ): MillinerService {
        $link = '<http://localhost:8000/media/6?_format=jsonld>; rel="alternate"; type="application/ld+json"';
        $link .= ',<http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg>; rel="describes"';
        $drupal_json_response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                "Link" => $link,
            ],
            file_get_contents($this->getStaticFile('Media.json'))
        );
        $drupal_jsonld_response = new Response(
            200,
            [
                'Content-Type' => 'application/ld+json',
            ],
            file_get_contents($this->getStaticFile('Media.jsonld'))
        );
        $this->drupal_client_prophecy->get('http://localhost:8000/media/6?_format=json', Argument::any())
            ->willReturn($drupal_json_response);
        $this->drupal_client_prophecy->get('http://localhost:8000/media/6?_format=jsonld', Argument::any())
            ->willReturn($drupal_jsonld_response);

        $link = "<{$this->fedora_full_uri}/fcr:metadata>";
        $link .= '; rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' => $link]
        );
        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willReturn($head_response);

        $fedora_get_response = new \donatj\MockWebServer\Response(
            file_get_contents($this->getStaticFile($mediaResponseFilename)),
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            200
        );
        // This is media tests so we need the responses to be at the
        // fcr:metadata endpoint for metadata.
        self::$webserver->setResponseOfPath(
            $this->fedora_path . '/fcr:metadata',
            new ResponseByMethod([
                ResponseByMethod::METHOD_GET => $fedora_get_response,
                ResponseByMethod::METHOD_PUT => $fedora_put_response,
            ])
        );


        return new MillinerService(
            $this->drupal_client_prophecy->reveal(),
            $this->logger,
            $this->fedoraBaseUrl,
            $this->modifiedDatePredicate,
            false,
            false
        );
    }
}
