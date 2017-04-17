<?php

namespace Islandora\Gemini\Controller;

use Islandora\Crayfish\Commons\PathMapper\PathMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeminiController
 * @package Islandora\Gemini\Controller
 */
class GeminiController
{

    /**
     * @var \Islandora\Crayfish\Commons\PathMapper\PathMapperInterface
     */
    protected $pathMapper;

    /**
     * GeminiController constructor.
     * @param \Islandora\Crayfish\Commons\PathMapper\PathMapperInterface
     */
    public function __construct(PathMapperInterface $pathMapper)
    {
        $this->pathMapper = $pathMapper;
    }

    /**
     * @param string $fedora_path
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDrupalPath($fedora_path)
    {
        try {
            if (!$result = $this->pathMapper->getDrupalPath($fedora_path)) {
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
            if (!$result = $this->pathMapper->getFedoraPath($drupal_path)) {
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
            $this->pathMapper->createPair(
                $this->sanitize($body['drupal']),
                $this->sanitize($body['fedora'])
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
            if (!$result = $this->pathMapper->deleteFromDrupalPath($drupal_path)) {
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
            if (!$result = $this->pathMapper->deleteFromFedoraPath($fedora_path)) {
                return new Response(null, 404);
            }

            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public function sanitize($path)
    {
        $sanitized = ltrim($path);
        return ltrim($sanitized, '/');
    }
}
