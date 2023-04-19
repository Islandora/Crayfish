<?php

namespace App\Islandora\Milliner\Tests;

use donatj\MockWebServer\ResponseByMethod;
use donatj\MockWebServer\ResponseStack;
use Prophecy\Argument;

/**
 * Class MillinerServiceTest
 * @package \App\Islandora\Milliner\Tests
 * @coversDefaultClass \App\Islandora\Milliner\Service\MillinerService
 */
class DeleteTest extends AbstractMillinerTestCase
{

    /**
     * @covers ::__construct
     * @covers ::deleteNode
     */
    public function testDeleteThrowsFedoraError()
    {
        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            new ResponseByMethod([
                ResponseByMethod::METHOD_DELETE => $this->forbidden_response,
            ])
        );

        $milliner = $this->getMilliner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

        $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
    }

    /**
     * @covers ::__construct
     * @covers ::deleteNode
     */
    public function testDeleteReturnsFedoraResult()
    {
        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            new ResponseStack(
                new ResponseByMethod([
                    ResponseByMethod::METHOD_DELETE => $this->no_content_response,
                ]),
                new ResponseByMethod([
                    ResponseByMethod::METHOD_DELETE => $this->not_found_response,
                ]),
                new ResponseByMethod([
                    ResponseByMethod::METHOD_DELETE => $this->gone_response,
                ]),
            )
        );

        $milliner = $this->getMilliner();

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 404,
            "Milliner must return 404 when Fedora returns 404.  Received: $status"
        );

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 410,
            "Milliner must return 410 when Fedora returns 410.  Received: $status"
        );
    }
}
