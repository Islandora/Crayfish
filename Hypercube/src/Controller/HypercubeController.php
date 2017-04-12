<?php

namespace Islandora\Hypercube\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
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
    protected $executable;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * HypercubeController constructor.
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     * @param string $executable
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(CmdExecuteService $cmd, $executable, LoggerInterface $log)
    {
        $this->cmd = $cmd;
        $this->executable = $executable;
        $this->log = $log;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $fedora_resource
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function get(ResponseInterface $fedora_resource, Request $request)
    {
        $status = $fedora_resource->getStatusCode();
        if ($status != 200) {
            $this->log->debug("Fedora Resource: ", [
              'body' => $fedora_resource->getBody(),
              'status' => $status,
              'headers' => $fedora_resource->getHeaders()
            ]);
            return new Response(
                $fedora_resource->getReasonPhrase(),
                $status
            );
        }

        // Get tiff as a resource.
        $body = StreamWrapper::getResource($fedora_resource->getBody());

        // Arguments to OCR command are sent as a custom header
        $args = $request->headers->get('X-Islandora-Args');
        $this->log->debug("X-Islandora-Args:", ['args' => $args]);

        $cmd_string = $this->executable . ' stdin stdout ' . $args;
        $this->log->info('Tesseract Command:', ['cmd' => $cmd_string]);

        // Return response.
        try {
            return new StreamedResponse(
                $this->cmd->execute($cmd_string, $body),
                200,
                array('Content-Type' => 'text/plain')
            );
        } catch (\RuntimeException $e) {
            $this->log->error("RuntimeException:", ['exception' => $e]);
            return new Response($e->getMessage(), 500);
        }
    }
}
