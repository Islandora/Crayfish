<?php

namespace Islandora\Milliner\Middleware;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MillinerMiddleware
 * @package Islandora\Milliner\Middleware
 */
class AS2Middleware
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * DrupalEntityConverter constructor.
     * @param \GuzzleHttp\Client $client
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(Client $client, LoggerInterface $log)
    {
        $this->client = $client;
        $this->log = $log;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function parseEvent(Request $request)
    {
        if (0 === strpos($request->headers->get('Content-Type'), 'application/ld+json')) {
            $event = json_decode($request->getContent(), true);
            $request->attributes->set("event", $event);

            $this->extractUuid($request);
            $this->extractJsonldUrl($request);
            $this->extractHtmlUrl($request);
        }
        else {
            // Short circuit if the request is not JSONLD.
            return new Response(
                "Content-Type MUST be application/ld+json",
                400
            );
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function extractUuid(Request $request)
    {
        $event = $request->attributes->get("event");
        $urn = $event['object']['id'];
        if (preg_match("/urn:islandora:(?<uuid>.*)/", $urn, $matches)) {
            if (isset($matches['uuid'])) {
                $request->attributes->set("uuid", $matches['uuid']);
            }
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function extractJsonldUrl(Request $request)
    {
        $event = $request->attributes->get("event");
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return $elem['name'] == 'Drupal Metadata';
        });
        if ($url = reset($filtered)) {
            $request->attributes->set("jsonld_url", $url['href']);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function extractHtmlUrl(Request $request)
    {
        $event = $request->attributes->get("event");
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return $elem['name'] == 'Drupal HTML';
        });
        if ($url = reset($filtered)) {
            $request->attributes->set("html_url", $url['href']);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getJsonld(Request $request)
    {
        if ($request->headers->has("Authorization")) {
            $options['headers'] = [
                "Authorization" => $request->headers->get("Authorization")
            ];
        }

        $response = $this->client->get(
            $request->attributes->get("jsonld_url"),
            $options
        );

        $this->log->debug("Drupal Jsonld Response: ", [
            'body' => $response->getBody(true),
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders()
        ]);

        // Short circuit if the response is not OK.
        if ($response->getStatusCode() != 200) {
            return new Response(
                "Error from Drupal retrieving jsonld: " . $response->getReasonPhrase(),
                $response->getStatusCode()
            );
        }

        // Otherwise set the entity as a request attribute.
        $request->attributes->set('jsonld', json_decode($response->getBody(true), true));
    }

    public function getFile(Request $request)
    {
        $jsonld = $request->attributes->get("jsonld");

        $malformed = !isset($jsonld['uri']) ||
            empty($jsonld['uri']) ||
            !isset($jsonld['uri'][0]['value']);

        if ($malformed) {
            return new Response("Malformed Media jsonld. Cannot extract file url.", 500);
        }

        $file_url = $jsonld['uri'][0]['value'];
        $request->attributes->set("file_url", $file_url);

        if ($request->headers->has("Authorization")) {
            $options['headers'] = [
                "Authorization" => $request->headers->get("Authorization")
            ];
        }

        $response = $this->client->get(
            $request->attributes->get($file_url),
            $options
        );

        $this->log->debug("Drupal File Response: ", [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders()
        ]);

        // Short circuit if the response is not OK.
        if ($response->getStatusCode() != 200) {
            return new Response(
                "Error from Drupal retrieving file: " . $response->getReasonPhrase(),
                $response->getStatusCode()
            );
        }

        // Otherwise set request attributes.
        $request->attributes->set('file', $response->getBody());
        $request->attributes->set('mimetype', $response->getHeader('Content-Type'));
    }

}
