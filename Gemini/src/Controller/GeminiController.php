<?php

namespace Islandora\Gemini\Controller;

use Islandora\Gemini\Service\GeminiServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeminiController
 * @package Islandora\Gemini\Controller
 */
class GeminiController
{

    /**
     * @var \Islandora\Gemini\Service\GeminiServiceInterface
     */
    protected $gemini;

    /**
     * GeminiController constructor.
     * @param \Islandora\Gemini\Service\GeminiServiceInterface $gemini
     */
    public function __construct(GeminiServiceInterface $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * @param string $fedora_path
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDrupalPath($fedora_path)
    {
        try {
            if (!$result = $this->gemini->getDrupalPath($fedora_path)) {
                return new Response(null, 404);
            }

            return new Response($result, 200);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $drupal_path
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getFedoraPath($drupal_path)
    {
        try {
            if (!$result = $this->gemini->getFedoraPath($drupal_path)) {
                return new Response(null, 404);
            }

            return new Response($result, 200);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createPair(Request $request)
    {
        $content_type = $request->headers->get("Content-Type");
        if (strcmp($content_type, "application/json") != 0) {
            return new Response("POST only accepts json requests", 400);
        }

        $body = json_decode($request->getContent(), true);

        if (!isset($body['drupal'])) {
            return new Response("POST body must contain Drupal path", 400);
        }

        if (!isset($body['fedora'])) {
            return new Response("POST body must contain Fedora path", 400);
        }

        try {
            $this->gemini->createPair(
                $body['drupal'],
                $body['fedora']
            );
             return new Response(null, 201);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $drupal_path
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteFromDrupalPath($drupal_path)
    {
        try {
            if (!$result = $this->gemini->deleteFromDrupalPath($drupal_path)) {
                return new Response("Not Found", 404);
            }

            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $fedora_path
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteFromFedoraPath($fedora_path)
    {
        try {
            if (!$result = $this->gemini->deleteFromFedoraPath($fedora_path)) {
                return new Response(null, 404);
            }

            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}
