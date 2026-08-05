<?php

namespace App\Islandora\Milliner\Tests;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * Class SaveExternalTest
 * @package \App\Islandora\Milliner\Tests
 * @coversDefaultClass \App\Islandora\Milliner\Service\MillinerService
 */
class SaveExternalTest extends AbstractMillinerTestCase
{

    private const EXTERNAL_URL = 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg';

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalThrowsOnHeadError()
    {

        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willThrow(
                new RequestException(
                    "Unauthorized",
                    new Request('HEAD', 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg'),
                    new Response(403, [], null, "1.1", "UNAUTHORIZED")
                )
            );

        $milliner = $this->getMilliner();

        $this->expectException(RequestException::class);
        $this->expectExceptionCode(403);

        $milliner->saveExternal(
            $this->uuid,
            self::EXTERNAL_URL,
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalThrowsOnPutError()
    {
        $this->drupal_client_prophecy->head(Argument::any(), Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'image/jpeg']));
        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            $this->forbidden_response
        );

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

        $milliner->saveExternal(
            $this->uuid,
            self::EXTERNAL_URL,
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalCreatesRedirectWithNormalizedMimeType()
    {
        $this->drupal_client_prophecy->head(
            self::EXTERNAL_URL,
            ['headers' => ['Authorization' => 'Bearer islandora']]
        )->willReturn(new Response(200, ['Content-Type' => 'image/jpeg; charset=binary']))
            ->shouldBeCalledOnce();
        self::$webserver->setResponseOfPath($this->fedora_path, $this->created_response);

        $response = $this->getMilliner()->saveExternal(
            $this->uuid,
            self::EXTERNAL_URL,
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );

        $this->assertSame(201, $response->getStatusCode());
        $request = self::$webserver->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame('PUT', $request->getRequestMethod());
        $headers = array_change_key_case($request->getHeaders(), CASE_LOWER);
        $this->assertSame('Bearer islandora', $headers['authorization']);
        $this->assertSame($this->expectedExternalLink('image/jpeg'), $headers['link']);
    }

    /**
     * @covers ::__construct
     * @covers ::saveExternal
     */
    public function testSaveExternalRetriesPublicFileWithoutAuthorization()
    {
        $authenticated = ['headers' => ['Authorization' => 'Bearer islandora']];
        $this->drupal_client_prophecy->head(self::EXTERNAL_URL, $authenticated)
            ->willThrow(
                new ClientException(
                    'Unauthorized',
                    new Request('HEAD', self::EXTERNAL_URL),
                    new Response(403)
                )
            )
            ->shouldBeCalledOnce();
        $this->drupal_client_prophecy->head(self::EXTERNAL_URL, ['headers' => []])
            ->willReturn(new Response(200, ['Content-Type' => 'image/png']))
            ->shouldBeCalledOnce();
        self::$webserver->setResponseOfPath($this->fedora_path, $this->no_content_response);

        $response = $this->getMilliner()->saveExternal(
            $this->uuid,
            self::EXTERNAL_URL,
            $this->fedoraBaseUrl,
            'Bearer islandora'
        );

        $this->assertSame(204, $response->getStatusCode());
        $request = self::$webserver->getLastRequest();
        $this->assertNotNull($request);
        $headers = array_change_key_case($request->getHeaders(), CASE_LOWER);
        $this->assertSame($this->expectedExternalLink('image/png'), $headers['link']);
    }

    private function expectedExternalLink(string $mimeType): string
    {
        return '<' . self::EXTERNAL_URL . '>; rel="http://fedora.info/definitions/fcrepo#ExternalContent"; ' .
            'handling="redirect"; type="' . $mimeType . '"';
    }
}
