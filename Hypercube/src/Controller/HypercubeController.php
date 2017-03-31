<?php

namespace Islandora\Hypercube\Controller;

use Islandora\Hypercube\Service\HypercubeServiceInterface;
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
     * @var \Islandora\Hypercube\Service\HypercubeServiceInterface
     */
    protected $ocr;

    /**
     * HypercubeController constructor.
     * @param \Islandora\Hypercube\Service\HypercubeServiceInterface $ocr
     */
    public function __construct(HypercubeServiceInterface $ocr)
    {
        $this->ocr = $ocr;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function post(Request $request)
    {
        // Filter out non-TIFFs, returning 400
        $content_type = $request->headers->get("Content-Type");
        if (strcmp($content_type, "image/tiff") != 0) {
            return new Response("Hypercube only works on tiffs", 400);
        }

        // Filter out empty requests, returning 400
        $size = intval($request->headers->get('Content-Length'));
        if ($size == 0) {
            return new Response("No TIFF image provided in request.", 400);
        }

        // Get tiff as a resource.
        $body = $request->getContent(true);

        // Arguments to OCR command are sent as a custom header
        $args = $request->headers->get('X-Islandora-Args');

        // Return response.
        try {
            return new StreamedResponse(
                $this->ocr->execute($args, $body),
                200,
                array('Content-Type' => 'text/plain')
            );
        } catch (\RuntimeException $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}
