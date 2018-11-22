<?php

namespace Islandora\Homarus\Tests;

use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Crayfish\Commons\ApixFedoraResourceRetriever;
use Islandora\Homarus\Controller\HomarusController;
use Monolog\Logger;

/**
 * @coversDefaultClass \Islandora\Homarus\Controller\HomarusController
 */
class HomarusControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::convert
     * @covers ::getContentType
     * @covers ::getFfmpegFormat
     */
    public function testOptions()
    {
        $mock_service = $this->prophesize(CmdExecuteService::class)->reveal();
        $controller = new HomarusController(
            $mock_service,
            [],
            '',
            'convert',
            $this->prophesize(Logger::class)->reveal(),
            ''
        );

        $response = $controller->convertOptions();
        $this->assertTrue($response->getStatusCode() == 200, 'Convert OPTIONS should return 200');
        $this->assertTrue(
            $response->headers->get('Content-Type') == 'text/turtle',
            'Convert OPTIONS should return turtle'
        );
    }
}
