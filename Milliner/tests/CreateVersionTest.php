<?php

namespace App\Islandora\Milliner\Tests;

use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseByMethod;
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
        self::$webserver->setResponseOfPath(
            $this->fedora_path . '/fcr:versions',
            new ResponseByMethod([
                ResponseByMethod::METHOD_POST => $this->created_response
            ])
        );
        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            new ResponseByMethod([
                ResponseByMethod::METHOD_HEAD => new Response(
                    '',
                    ['Link' => '<' . $this->fedora_full_uri . '/fcr:versions>; rel="timemap"'],
                    200
                )
            ])
        );

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
        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            new ResponseByMethod([
                ResponseByMethod::METHOD_HEAD => $this->not_found_response
            ])
        );

        $this->expectException(\RuntimeException::class);

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
        self::$webserver->setResponseOfPath(
            $this->fedora_path,
            new ResponseByMethod([
                ResponseByMethod::METHOD_HEAD => $this->forbidden_response
            ])
        );

        $this->expectException(\RuntimeException::class);

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
