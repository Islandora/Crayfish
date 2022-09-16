<?php

namespace App\Islandora\Milliner\Tests;

use Prophecy\Argument;

/**
 * Class MillinerServiceTest
 * @package \App\Islandora\Milliner\Tests
 * @coversDefaultClass \App\Islandora\Milliner\Service\MillinerService
 */
class CreateVersionTest extends AbstractMillinerTestCase
{

    /**
     * @covers ::__construct
     * @covers ::createVersion
     */
    public function testCreateVersionReturnsFedora201()
    {

        $this->fedora_client_prophecy->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->created_response);

        $milliner = $this->getMilliner();

        $response = $milliner->createVersion(
            $this->uuid,
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
     * @covers ::createVersion
     */

    public function testCreateVersionReturnsFedora404()
    {
        $this->fedora_client_prophecy->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->not_found_response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(404);

        $milliner = $this->getMilliner();

        $response = $milliner->createVersion(
            $this->uuid,
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 404,
            "Milliner must return 404 when Fedora returns 404.  Received: $status"
        );
    }


    /**
     * @covers ::__construct
     * @covers ::createVersion
     */
    public function testcreateVersionThrowsOnFedoraSaveError()
    {
        $this->fedora_client_prophecy->createVersion(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->forbidden_response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

        $milliner = $this->getMilliner();

        $response = $milliner->createVersion(
            $this->uuid,
            $this->fedoraBaseUrl,
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 403,
            "Milliner must return 403 when Fedora returns 403.  Received: $status"
        );
    }
}
