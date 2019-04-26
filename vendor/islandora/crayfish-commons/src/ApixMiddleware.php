<?php

namespace Islandora\Crayfish\Commons;

use Islandora\Chullo\IFedoraApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Retrieves a Fedora resource using the Apix-Ldp-Resource header.
 *
 * @package Islandora\Crayfish\Commons
 */
class ApixMiddleware
{

    /**
     * @var \Islandora\Chullo\IFedoraApi
     */
    protected $api;

    /**
     * @var null|\Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * ApixFedoraResourceRetriever constructor.
     * @param \Islandora\Chullo\IFedoraApi $api
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(
        IFedoraApi $api,
        LoggerInterface $log
    ) {
        $this->api = $api;
        $this->log = $log;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function before(Request $request)
    {
        // Short circuit if there's no Apix-Ldp-Resource header.
        if (!$request->headers->has("Apix-Ldp-Resource")) {
            $this->log->debug("Malformed request, no Apix-Ldp-Resource header present");
            return new Response(
                "Malformed request, no Apix-Ldp-Resource header present",
                400
            );
        }

        // Get the resource.
        $fedora_resource = $this->getFedoraResource($request);

        // Short circuit if the Fedora response is not 200.
        $status = $fedora_resource->getStatusCode();
        if ($status != 200) {
            $this->log->debug("Fedora Resource: ", [
              'body' => $fedora_resource->getBody(),
              'status' => $fedora_resource->getStatusCode(),
              'headers' => $fedora_resource->getHeaders()
            ]);
            return new Response(
                $fedora_resource->getReasonPhrase(),
                $status
            );
        }

        // Set the Fedora resource on the request.
        $request->attributes->set('fedora_resource', $fedora_resource);
    }

    protected function getFedoraResource(Request $request)
    {
        // Pass along auth headers if present.
        $headers = [];
        if ($request->headers->has("Authorization")) {
            $headers['Authorization'] = $request->headers->get("Authorization");
        }

        $uri = $request->headers->get("Apix-Ldp-Resource");

        return $this->api->getResource(
            $uri,
            $headers
        );
    }
}
