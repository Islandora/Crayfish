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
            $response = $this->milliner->saveNode(
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

        $json_url = $this->parseJsonUrl($event);
        if (empty($json_url)) {
            return new Response(
                "Could not parse JSON url from request body",
                400
            );
        }

        try {
            $response = $this->milliner->saveMedia(
                $uuid,
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

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    /*
    public function saveNonRdf(Request $request)
    {
        $stream = $request->attributes->get('stream');
        $mimetype = $request->attributes->get('mimetype');
        $rdf_url = $request->attributes->get('rdf_url');
        $file_url = $request->attributes->get('nonrdf_url');
        $uuid = $request->attributes->get('uuid');
        $token = $request->headers->get('Authorization', null);

        try {
            $response = $this->milliner->saveNonRdf(
                $stream,
                $mimetype,
                $nonrdf_url,
                $rdf_url,
                $uuid,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->debug("Exception Saving NonRdf Resource: ", [
                'body' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }
    */

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    /*
    public function saveRdf(Request $request)
    {
        $rdf = $request->attributes->get('rdf');
        $rdf_url = $request->attributes->get('rdf_url');
        $uuid = $request->attributes->get('uuid');
        $token = $request->headers->get('Authorization', null);

        try {
            $response = $this->milliner->saveRdf(
                $rdf,
                $rdf_url,
                $uuid,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->debug("Exception Saving Rdf Resource: ", [
                'body' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }
    */

    // TODO: FINISH CHANGING ATTRIBUTE NAMES FOR THE DELETES AND THEN TOUCH UP THE MIDDLEWARE
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    /*
    public function deleteNonRdf(Request $request)
    {
        $token = $request->headers->get('Authorization');
        $file_url = $request->attributes->get('file_url');
        $jsonld_url = $request->attributes->get('jsonld_url');

        try {
            $fedora_response = $this->milliner->delete(
                $file_url,
                $jsonld_url,
                $token
            );

            if ($fedora_response) {
                return new Response(
                    $fedora_response->getBody(),
                    $fedora_response->getStatusCode()
                );
            } else {
                return new Response(
                    "No Fedora binary found for $file_url",
                    404
                );
            }
        } catch (\Exception $e) {
            $this->log->debug("Exception Deleting Fedora Binary: ", [
                'body' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }
    */

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    /*
    public function deleteRdf(Request $request)
    {
        $uuid = $request->attributes->get('uuid');
        $token = $request->headers->get('Authorization');
        $url = $request->attributes->get('rdf_url');

        try {
            $fedora_response = $this->milliner->deleteRdf(
                $url,
                $uuid,
                $token
            );

            if ($fedora_response) {
                return new Response(
                    $fedora_response->getBody(),
                    $fedora_response->getStatusCode()
                );
            } else {
                return new Response(
                    "No Fedora resource found for $url",
                    404
                );
            }
        } catch (\Exception $e) {
            $this->log->debug("Exception Deleting Fedora Binary: ", [
                'body' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }
    */
}
