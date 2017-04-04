<?php

namespace Islandora\Hypercube\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Psr\Http\Message\ResponseInterface;
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

    protected $executable;

    /**
     * HypercubeController constructor.
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     * @param string $executable
     */
    public function __construct(CmdExecuteService $cmd, $executable)
    {
        $this->cmd = $cmd;
        $this->executable = $executable;
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
            return new Response(
                $fedora_resource->getReasonPhrase(),
                $status
            );
        }

        // Get tiff as a resource.
        $body = StreamWrapper::getResource($fedora_resource->getBody());

        // Arguments to OCR command are sent as a custom header
        $args = $request->headers->get('X-Islandora-Args');

        $cmd_string = $this->executable . ' stdin stdout ' . $args;

        // Return response.
        try {
            return new StreamedResponse(
                $this->cmd->execute($cmd_string, $body),
                200,
                array('Content-Type' => 'text/plain')
            );
        } catch (\RuntimeException $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}
