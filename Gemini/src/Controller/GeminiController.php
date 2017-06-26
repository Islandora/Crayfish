<?php

namespace Islandora\Gemini\Controller;

use Islandora\Crayfish\Commons\UrlMapper\UrlMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeminiController
 * @package Islandora\Gemini\Controller
 */
class GeminiController
{

    /**
     * @var \Islandora\Crayfish\Commons\UrlMapper\UrlMapperInterface
     */
    protected $urlMapper;

    /**
     * GeminiController constructor.
     * @param \Islandora\Crayfish\Commons\UrlMapper\UrlMapperInterface
     */
    public function __construct(UrlMapperInterface $urlMapper)
    {
        $this->urlMapper = $urlMapper;
    }

    /**
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get($uuid)
    {
        try {
            $result = $this->urlMapper->getUrls($uuid);
            if (empty($result)) {
                return new Response(null, 404);
            }

            return new JsonResponse($result, 200);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $uuid
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function put($uuid, Request $request)
    {
        // Reject non json requests.
        if (0 !== strpos($request->headers->get('Content-Type'), 'application/json')) {
            return new Response("Invalid Content-Type.  Expecting application/json", 400);
        }

        $urls = json_decode($request->getContent(), true);

        // Parse request reject malformed bodies.
        if (!isset($urls['drupal_rdf'])) {
            return new Response("Missing 'drupal_rdf' entry in reqeust body.", 400);
        }
        $drupal_rdf = $urls['drupal_rdf'];

        if (!isset($urls['fedora_rdf'])) {
            return new Response("Missing 'fedora_rdf' entry in reqeust body.", 400);
        }
        $fedora_rdf = $urls['fedora_rdf'];

        $drupal_nonrdf = isset($urls['drupal_nonrdf']) ? $urls['drupal_nonrdf'] : null;
        if (count($urls) > 2 && empty($drupal_nonrdf)) {
            return new Response("Missing 'drupal_nonrdf' entry in reqeust body.", 400);
        }

        $fedora_nonrdf = isset($urls['fedora_nonrdf']) ? $urls['fedora_nonrdf'] : null;
        if (count($urls) > 2 && empty($fedora_nonrdf)) {
            return new Response("Missing 'fedora_nonrdf' entry in reqeust body.", 400);
        }

        try {
            $this->urlMapper->saveUrls(
                $uuid,
                $drupal_rdf,
                $fedora_rdf,
                $drupal_nonrdf,
                $fedora_nonrdf
            );
            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete($uuid)
    {
        try {
            $this->urlMapper->deleteUrls($uuid);
            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

}
