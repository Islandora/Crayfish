<?php

namespace Islandora\Milliner\Controller;

use Islandora\Milliner\Service\MillinerServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MillinerController
 * @package Islandora\Milliner\Controller
 */
class MillinerController
{

    /**
     * @var \Islandora\Milliner\Service\MillinerServiceInterface
     */
    protected $milliner;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * MillinerController constructor.
     * @param \Islandora\Milliner\Service\MillinerServiceInterface $milliner
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(MillinerServiceInterface $milliner, LoggerInterface $log)
    {
        $this->milliner = $milliner;
        $this->log = $log;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveContent(Request $request)
    {
        if (0 !== strpos($request->headers->get('Content-Type'), 'application/ld+json')) {
            return new Response(
                "Expecting 'Content-Type' of 'application/ld+json'",
                400
            );
        }

        $token = $request->headers->get("Authorization", null);

        $event = json_decode($request->getContent(), true);

        $uuid = $this->parseUuid($event);
        if (empty($uuid)) {
            return new Response(
                "Could not parse UUID from request body",
                400
            );
        }

        $jsonld_url = $this->parseJsonldUrl($event);
        if (empty($jsonld_url)) {
            return new Response(
                "Could not parse JSONLD url from request body",
                400
            );
        }

        try {
            $response = $this->milliner->saveContent(
                $uuid,
                $jsonld_url,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );

        } catch (\Exception $e) {
            return new Response($e->getMessage(), $e->getCode());
        }
    }

    protected function parseUuid(array $event)
    {
        if (!isset($event['object']) || !isset($event['object']['id'])) {
            return null;
        }

        $urn = $event['object']['id'];
        if (preg_match("/urn:uuid:(?<uuid>.*)/", $urn, $matches)) {
            if (isset($matches['uuid'])) {
                return $matches['uuid'];
            }
        }

        return null;
    }

    protected function parseJsonldUrl(array $event)
    {
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return $elem['mediaType'] == 'application/ld+json';
        });
        if ($url = reset($filtered)) {
            return $url['href'];
        }
        return null;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveMedia(Request $request)
    {
        if (0 !== strpos($request->headers->get('Content-Type'), 'application/ld+json')) {
            return new Response(
                "Expecting 'Content-Type' of 'application/ld+json'",
                400
            );
        }

        $token = $request->headers->get("Authorization", null);

        $event = json_decode($request->getContent(), true);

        $jsonld_url = $this->parseJsonldUrl($event);
        if (empty($jsonld_url)) {
            return new Response(
                "Could not parse JSONLD url from request body",
                400
            );
        }

        $json_url = $this->parseJsonUrl($event);
        if (empty($json_url)) {
            return new Response(
                "Could not parse JSON url from request body",
                400
            );
        }

        try {
            $response = $this->milliner->saveMedia(
                $json_url,
                $jsonld_url,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );

        } catch (\Exception $e) {
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    protected function parseJsonUrl(array $event)
    {
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return $elem['mediaType'] == 'application/json';
        });
        if ($url = reset($filtered)) {
            return $url['href'];
        }
        return null;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveFile(Request $request)
    {
        if (0 !== strpos($request->headers->get('Content-Type'), 'application/ld+json')) {
            return new Response(
                "Expecting 'Content-Type' of 'application/ld+json'",
                400
            );
        }

        $token = $request->headers->get("Authorization", null);

        $event = json_decode($request->getContent(), true);

        $uuid = $this->parseUuid($event);
        if (empty($uuid)) {
            return new Response(
                "Could not parse UUID from request body",
                400
            );
        }

        $file_url = $this->parseFileUrl($event);
        if (empty($file_url)) {
            return new Response(
                "Could not parse Drupal File URL from request body",
                400
            );
        }

        $checksum_url = $this->parseChecksumUrl($event);
        if (empty($checksum_url)) {
            return new Response(
                "Could not parse Drupal File Checksum URL from request body",
                400
            );
        }

        try {
            $response = $this->milliner->saveFile(
                $uuid,
                $file_url,
                $checksum_url,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );

        } catch (\Exception $e) {
            return new Response($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param array $event
     * @return string|null
     */
    protected function parseFileUrl(array $event)
    {
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return isset($elem['rel']) && $elem['rel'] == 'canonical';
        });
        if ($url = reset($filtered)) {
            return $url['href'];
        }
        return null;
    }

    /**
     * @param array $event
     * @return string|null
     */
    protected function parseChecksumUrl(array $event)
    {
        $filtered = array_filter($event['object']['url'], function ($elem) {
            return $elem['name'] == 'Drupal Checksum';
        });
        if ($url = reset($filtered)) {
            return $url['href'];
        }
        return null;
    }

    /**
     * @param string $uuid
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete($uuid, Request $request)
    {
        $token = $request->headers->get("Authorization", null);

        try {
            $response = $this->milliner->delete(
                $uuid,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );

        } catch (\Exception $e) {
            return new Response($e->getMessage(), $e->getCode());
        }
    }

}
