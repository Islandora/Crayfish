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
class MillinerMiddleware
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
            $data = json_decode($request->getContent(), true);
            $request->attributes->set("event", $data);

            if ($error_response = $this->extractUuid($request)) {
                return $error_response;
            }

            if ($error_response = $this->extractObjectJsonldUrl($request)) {
                return $error_response;
            }

            if ($error_response = $this->extractObjectHtmlUrl($request)) {
                return $error_response;
            }
        }
        else {
            // Short circuit if the request is not JSON.
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
            else {
                return new Response(
                    "Request has a malformed URN, cannot extract uuid.",
                    400
                );
            }
        }
        else {
            return new Response(
                "Request is missing an object id",
                400
            );
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function extractObjectJsonldUrl(Request $request)
    {
        $event = $request->attributes->get("event");
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return $elem['mediaType'] == 'application/ld+json';
        });

        if (empty($filtered)) {
            return new Response(
                "Request is missing 'application/ld+json' url for object",
                400
            );
        }

        $url = reset($filtered);
        $request->attributes->set("jsonld_url", $url['href']);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function extractObjectHtmlUrl(Request $request)
    {
        $event = $request->attributes->get("event");
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return $elem['mediaType'] == 'text/html';
        });

        if (empty($filtered)) {
            return new Response(
                "Request is missing 'text/html' url for object",
                400
            );
        }

        $url = reset($filtered);
        $request->attributes->set("html_url", $url['href']);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function extractFileUrl(Request $request)
    {
        $event = $request->attributes->get("event");
        if (!isset($event['object']['attachment'])) {
            return new Response(
                "Event is missing 'attachment' for object",
                400
            );
        }

        $url = $event['object']['attachment']['url'];
        $request->attributes->set("file_url", $url);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDrupalFile(Request $request)
    {
        $url = $request->attributes->get("file_url");
        return $this->getDrupalResponse($url, $request);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDrupalJsonld(Request $request)
    {
        $url = $request->attributes->get("jsonld_url");
        return $this->getDrupalResponse($url, $request);
    }

    /**
     * @param string $url
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getDrupalResponse($url, Request $request)
    {
        $options = ['http_errors' => false];
        if ($request->headers->has("Authorization")) {
            $options['headers'] = [
                "Authorization" => $request->headers->get("Authorization")
            ];
        }

        $drupal_response = $this->client->get($url, $options);

        $this->log->debug("Drupal Response: ", [
            'body' => (string)$drupal_response->getBody(),
            'status' => $drupal_response->getStatusCode(),
            'headers' => $drupal_response->getHeaders()
        ]);

        // Short circuit if the response is not OK.
        if ($drupal_response->getStatusCode() != 200) {
            return new Response(
                "Error from Drupal: " . $drupal_response->getReasonPhrase(),
                $drupal_response->getStatusCode()
            );
        }

        // Otherwise set the entity as a request attribute.
        $request->attributes->set('drupal_response', $drupal_response);
    }
}
