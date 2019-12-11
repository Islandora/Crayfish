<?php

namespace Islandora\Gemini\Controller;

use Islandora\Gemini\UrlMapper\UrlMapperInterface;
use Islandora\Gemini\UrlMinter\UrlMinterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;

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
     * @var \Symfony\Component\Routing\Generator\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * GeminiController constructor.
     * @param \Islandora\Gemini\UrlMapper\UrlMapperInterface
     * @param \Islandora\Gemini\UrlMinter\UrlMinterInterface
     * @param \Symfony\Component\Routing\Generator\UrlGenerator
     */
    public function __construct(
        UrlMapperInterface $urlMapper,
        UrlMinterInterface $urlMinter,
        UrlGenerator $urlGenerator
    ) {
        $this->urlMapper = $urlMapper;
        $this->urlMinter = $urlMinter;
        $this->urlGenerator = $urlGenerator;
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

        if (empty($uuid)) {
            return new Response(
                "Requests to mint new URLS must contain a UUID in the request body",
                400
            );
        }

        $islandora_fedora_endpoint = $request->headers->get('X-Islandora-Fedora-Endpoint', '');

        try {
            return new Response(
                $this->urlMinter->mint($uuid, $islandora_fedora_endpoint),
                200
            );
        } catch (\InvalidArgumentException $e) {
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
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

        // Parse request and reject malformed bodies.
        $urls = json_decode($request->getContent(), true);

        if (!isset($urls['drupal'])) {
            return new Response("Missing 'drupal' entry in request body.", 400);
        }

        if (!isset($urls['fedora'])) {
            return new Response("Missing 'fedora' entry in request body.", 400);
        }

        // Save URL pair.
        $is_new = $this->urlMapper->saveUrls(
            $uuid,
            $urls['drupal'],
            $urls['fedora']
        );

        // Return 201 or 204 depending on if a new record was created.
        $response = new Response(null, $is_new ? 201 : 204);
        if ($is_new) {
            // Add a Location header if a new record was created.
            $url = $this->urlGenerator->generate(
                'GET_uuid',
                ['uuid' => $uuid],
                UrlGenerator::ABSOLUTE_URL
            );
            $response->headers->add(['Location' => $url]);
        }
        return $response;
    }

    /**
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete($uuid)
    {
        $deleted = $this->urlMapper->deleteUrls($uuid);
        return new Response(null, $deleted ? 204 : 404);
    }

    /**
     * Find the opposite URI for the on provided in X-Islandora-URI.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   A response 200 with Location or 404.
     */
    public function getByUri(Request $request)
    {
        if (!$request->headers->has('X-Islandora-URI')) {
            return new Response('Require the X-Islandora-URI header', 400);
        }
        $uri = $request->headers->get('X-Islandora-URI');
        if (is_array($uri)) {
            // Can only return one Location header.
            $uri = reset($uri);
        }
        $uri = $this->urlMapper->findUrls($uri);
        $headers = [];
        if ($uri) {
            $headers['Location'] = $uri;
        }
        return new Response(null, ($uri ? 200 : 404), $headers);
    }
}
