<?php

namespace App\Islandora\Hypercube\Tests;

use App\Islandora\Hypercube\Controller\HypercubeController;
use Islandora\Crayfish\Commons\CmdExecuteService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \App\Islandora\Hypercube\Controller\HypercubeController
 */
class HypercubeControllerTest extends TestCase
{

    use ProphecyTrait;

    /**
     * @covers ::__construct
     * @covers ::options
     */
    public function testOptions()
    {
        // Mock a controller.
        $controller = $this->mockController();

        $response = $controller->options();
        $this->assertTrue(
            $response->getStatusCode() == 200,
            'Identify OPTIONS should return 200'
        );
        $this->assertTrue(
            $response->headers->get('Content-Type') == 'text/turtle',
            'Identify OPTIONS should return turtle'
        );
    }

    /**
     * @covers ::__construct
     * @covers ::ocr
     */
    public function testTesseractErrorReturns500()
    {
        $this->errorReturns500('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::ocr
     */
    public function testPdfToTextErrorReturns500()
    {
        $this->errorReturns500('application/pdf');
    }

    private function errorReturns500($mimetype)
    {
        // Mock a TesseractService to create a controller.
        $controller = $this->mockController(true);

        // Mock a request.
        $request = $this->mockRequest($mimetype);

        $response = $controller->ocr($request);
        $this->assertTrue(
            $response->getStatusCode() == 500,
            "Response must return 500"
        );
        $this->assertTrue(
            $response->getContent() == "ERROR",
            "Response must return exception's message"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::ocr
     */
    public function testTesseractSuccessReturns200()
    {
        $this->successReturns200('image/tiff');
    }

    /**
     * @covers ::__construct
     * @covers ::ocr
     */
    public function testPdfToTextSuccessReturns200()
    {
        $this->successReturns200('application/pdf');
    }

    private function successReturns200($mimetype)
    {
        // Mock a controller.
        $controller = $this->mockController();

        $request = $this->mockRequest($mimetype);

        // Check success.
        $response = $controller->ocr($request);
        $this->assertTrue(
            $response->getStatusCode() == 200,
            "Response must return 200"
        );
    }

    /**
     * Utility function to mock a HypercubeController
     *
     * @param bool $throwException
     *   Whether the CmdExecuteService should throw an exception or not.
     * @return \App\Islandora\Hypercube\Controller\HypercubeController
     *   The controller
     */
    private function mockController(bool $throwException = false): HypercubeController
    {
        $prophecy = $this->prophesize(CmdExecuteService::class);
        if ($throwException) {
            $prophecy->execute(Argument::any(), Argument::any())
                ->willThrow(new \RuntimeException("ERROR", 500));
        } else {
            $prophecy->execute(Argument::any(), Argument::any())
                ->willReturn(function () {
                    return null;
                });
        }
        $mock_service = $prophecy->reveal();
        $mock_logger = $this->prophesize(LoggerInterface::class)->reveal();
        return new HypercubeController(
            $mock_service,
            'tesseract',
            'pdftotext',
            $mock_logger
        );
    }

    /**
     * Utility function to mock a request.
     *
     * @param string $mimetype
     *   The mimetype to fake as the Fedora object's content-type.
     * @return \Symfony\Component\HttpFoundation\Request
     *   The request.
     */
    private function mockRequest(string $mimetype): Request
    {
        // Mock a stream body for a Fedora response.
        $prophecy = $this->prophesize(StreamInterface::class);
        $prophecy->isReadable()->willReturn(true);
        $prophecy->isWritable()->willReturn(false);
        $mock_stream = $prophecy->reveal();

        // Mock a Fedora response.
        $prophecy = $this->prophesize(ResponseInterface::class);
        $prophecy->getHeader('Content-Type')->willReturn([$mimetype]);
        $prophecy->getStatusCode()->willReturn(200);
        $prophecy->getBody()->willReturn($mock_stream);
        $mock_fedora_response = $prophecy->reveal();

        // Create a Request.
        $request = Request::create(
            "/foo",
            "GET"
        );
        $request->headers->set('Authorization', 'some_token');
        $request->headers->set(
            'ApixLdpResource',
            'http://localhost:8080/fcrepo/rest/foo'
        );
        $request->attributes->set('fedora_resource', $mock_fedora_response);
        return $request;
    }
}
