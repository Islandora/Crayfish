<?php

namespace Islandora\Gemini\Controller;

use Islandora\Gemini\UrlMapper\UrlMapperInterface;
use Islandora\Gemini\UrlMinter\UrlMinterInterface;
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
     * @var \Islandora\Gemini\UrlMapper\UrlMapperInterface
     */
    protected $urlMapper;

    /**
     * @var \Islandora\Gemini\UrlMinter\UrlMinterInterface
     */
    protected $urlMinter;

    /**
     * GeminiController constructor.
     * @param \Islandora\Crayfish\Commons\UrlMapper\UrlMapperInterface
     */
    public function __construct(
        UrlMapperInterface $urlMapper,
        UrlMinterInterface $urlMinter
    ) {
        $this->urlMapper = $urlMapper;
        $this->urlMinter = $urlMinter;
    }

    /**
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get($uuid)
    {
        $result = $this->urlMapper->getUrls($uuid);
        if (empty($result)) {
            return new Response("Could not locate URL pair for $uuid", 404);
        }
        return new JsonResponse($result, 200);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function post(Request $request)
    {
        // Request contents are a UUID.
        $uuid = $request->getContent();

        return new Response(
            $this->urlMinter->mint($uuid),
            200
        );
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

        // Parse request and reject malformed bodies.
        $urls = json_decode($request->getContent(), true);

        if (!isset($urls['drupal'])) {
            return new Response("Missing 'drupal' entry in reqeust body.", 400);
        }

        if (!isset($urls['fedora'])) {
            return new Response("Missing 'fedora' entry in reqeust body.", 400);
        }

        $this->urlMapper->saveUrls(
            $uuid,
            $urls['drupal'],
            $urls['fedora']
        );
        return new Response(null, 204);
    }

    /**
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete($uuid)
    {
        $this->urlMapper->deleteUrls($uuid);
        return new Response(null, 204);
    }

}
