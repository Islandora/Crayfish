<?php

namespace Islandora\Hypercube\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HypercubeController
 * @package Islandora\Hypercube\Controller
 */
class HypercubeController
{

    /**
     * @var \Islandora\Crayfish\Commons\CmdExecuteService
     */
    protected $cmd;

    /**
     * @var string
     */
    protected $tesseract_executable;

    /**
     * @var string
     */
    protected $pdftotext_executable;

    /**
     * @var \Monolog\Logger
     */
    protected $log;

    /**
     * HypercubeController constructor.
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     * @param string $tesseract_executable
     * @param string $pdftotext_executable
     * @param $log
     */
    public function __construct(
        CmdExecuteService $cmd,
        $tesseract_executable,
        $pdftotext_executable,
        Logger $log
    ) {
        $this->cmd = $cmd;
        $this->tesseract_executable = $tesseract_executable;
        $this->pdftotext_executable = $pdftotext_executable;
        $this->log = $log;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function get(Request $request)
    {
        // Hack the fedora resource out of the attributes.
        $fedora_resource = $request->attributes->get('fedora_resource');

        // Get tiff as a resource.
        $body = StreamWrapper::getResource($fedora_resource->getBody());

        // Arguments to command line are sent as a custom header
        $args = $request->headers->get('X-Islandora-Args');

	// Check content type and use the appropriate command line tool.
	$content_type = $fedora_resource->getHeader('Content-Type')[0];
	
	$this->log->debug("Got Content-Type:", ['type' => $content_type]);

	if ($content_type == 'application/pdf') {
            $cmd_string = $this->pdftotext_executable . " $args - -";
        }
        else {
            $cmd_string = $this->tesseract_executable . " stdin stdout $args";
        }

	$this->log->debug("Executing command:", ['cmd' => $cmd_string]);

        // Return response.
        try {
            return new StreamedResponse(
                $this->cmd->execute($cmd_string, $body),
                200,
                ['Content-Type' => 'text/plain']
            );
        } catch (\RuntimeException $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    public function options()
    {
        return new BinaryFileResponse(
            __DIR__ . "/../../static/hypercube.ttl",
            200,
            ['Content-Type' => 'text/turtle']
        );
    }
}
