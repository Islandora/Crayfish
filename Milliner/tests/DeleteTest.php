<?php

namespace App\Islandora\Milliner\Tests;

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
        $this->fedora_client_prophecy->deleteResource(Argument::any(), Argument::any())
            ->willReturn($this->forbidden_response);

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
        $this->fedora_client_prophecy->deleteResource(Argument::any(), Argument::any())
            ->willReturn($this->no_content_response);

        $milliner = $this->getMilliner();

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );

        $this->fedora_client_prophecy->deleteResource(Argument::any(), Argument::any())
            ->willReturn($this->not_found_response);

        $milliner = $this->getMilliner();

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 404,
            "Milliner must return 404 when Fedora returns 404.  Received: $status"
        );

        $this->fedora_client_prophecy->deleteResource(Argument::any(), Argument::any())
            ->willReturn($this->gone_response);

        $milliner = $this->getMilliner();

        $response = $milliner->deleteNode($this->uuid, $this->fedoraBaseUrl, "Bearer islandora");
        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 410,
            "Milliner must return 410 when Fedora returns 410.  Received: $status"
        );
    }
}
