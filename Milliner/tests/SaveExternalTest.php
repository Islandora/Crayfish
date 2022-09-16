<?php

namespace App\Islandora\Milliner\Tests;

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
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
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

        $this->fedora_client_prophecy->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->forbidden_response);

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

        $milliner->saveExternal(
            $this->uuid,
            'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );
    }
}
