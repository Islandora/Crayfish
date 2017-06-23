<?php

namespace Islandora\Gemini\Controller;

use Islandora\Crayfish\Commons\IdMapper\IdMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeminiController
 * @package Islandora\Gemini\Controller
 */
class GeminiController
{

    /**
     * @var \Islandora\Crayfish\Commons\IdMapper\IdMapperInterface
     */
    protected $idMapper;

    /**
     * GeminiController constructor.
     * @param \Islandora\Crayfish\Commons\IdMapper\IdMapperInterface
     */
    public function __construct(IdMapperInterface $idMapper)
    {
        $this->idMapper = $idMapper;
    }

    /**
     * @param string $fedora_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDrupalId($fedora_id)
    {
        try {
            if (!$result = $this->idMapper->getDrupalId($fedora_id)) {
                return new Response(null, 404);
            }

            return new Response($result, 200);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $drupal_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getFedoraId($drupal_id)
    {
        try {
            if (!$result = $this->idMapper->getFedoraId($drupal_id)) {
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
            return new Response("POST body must contain Drupal id", 400);
        }

        if (!isset($body['fedora'])) {
            return new Response("POST body must contain Fedora id", 400);
        }

        try {
            $this->idMapper->createPair(
                $this->sanitize($body['drupal']),
                $this->sanitize($body['fedora'])
            );
             return new Response(null, 201);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $drupal_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteFromDrupalId($drupal_id)
    {
        try {
            if (!$result = $this->idMapper->deleteFromDrupalId($drupal_id)) {
                return new Response("Not Found", 404);
            }

            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $fedora_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteFromFedoraId($fedora_id)
    {
        try {
            if (!$result = $this->idMapper->deleteFromFedoraId($fedora_id)) {
                return new Response(null, 404);
            }

            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $id
     * @return string
     */
    public function sanitize($id)
    {
        $sanitized = ltrim($id);
        return ltrim($sanitized, '/');
    }
}
