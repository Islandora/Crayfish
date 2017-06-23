<?php

namespace Islandora\Milliner\Controller;

use Islandora\Milliner\Service\MillinerServiceInterface;
use GuzzleHttp\Psr7;
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
    public function saveBinary(Request $request)
    {
        // Middeware will already have returned error if these don't exist.
        $stream = $request->attributes->get("file");
        $mimetype = $request->attributes->get("mimetype");
        $url = $request->attributes->get('file_url');

        // Return error if UUID could not be extracted.
        $uuid = $request->attributes->get('uuid', null);
        if (!$uuid) {
            return new Response(
                "Could not extract UUID from AS2 event",
                400
            );
        }

        // Get token if it exists.
        $token = $request->headers->get('Authorization', null);

        try {
            $response = $this->milliner->saveBinary(
                $stream,
                $mimetype,
                $url,
                $uuid,
                $token
            );

            $status = $response->getStatusCode();

            // Return errors as-is.
            if (!($status == 201 || $status == 204)) {
                return new Response(
                    $response->getBody(),
                    $response->getStatusCode()
                );
            }

            // Otherwise enrich the event with additional URLs and return it.
            $event = $request->attributes->get("event");
            $event['object']['url'][] = [
                "name" => "Drupal File",
                "type" => "Link",
                "href" => $url,
                "mediaType" => $mimetype,
            ];
            $event['object']['url'][] = [
                "name" => "Fedora File",
                "type" => "Link",
                "href" => $response->getHeader("Location"),
                "mediaType" => $mimetype,
            ];
            $parsed = Psr7\parse_header($response->getHeader("Link"));
            foreach ($parsed as $header) {
                if (isset($header['rel']) && $header['rel'] = 'describedby') {
                    $event['object']['url'][] = [
                        "name" => "Fedora Metadata",
                        "type" => "Link",
                        "href" => trim($parsed[0], '<>'),
                    ];
                    break;
                }
            }

            return new Response(
                json_encode($event),
                $status,
                ['Content-Type' => 'application/ld+json']
            );
        } catch (\Exception $e) {
            $this->log->debug("Exception Saving Fedora Binary: ", [
                'body' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveJsonld(Request $request)
    {
        $jsonld = $request->attributes->get('jsonld');
        $url = $request->attributes->get('jsonld_url');
        $uuid = $request->attributes->get('uuid');
        $token = $request->headers->get('Authorization');

        try {
            $response = $this->milliner->saveJsonld(
                $jsonld,
                $url,
                $uuid,
                $token
            );

            $status = $response->getStatusCode();

            // Return errors as-is.
            if (!($status == 201 || $status == 204)) {
                return new Response(
                    $response->getBody(),
                    $response->getStatusCode()
                );
            }

            // Otherwise enrich the event with Fedora URL and return it.
            $event = $request->attributes->get("event");
            $event['object']['url'][] = [
                "name" => "Fedora Metadata",
                "type" => "Link",
                "href" => $response->getHeader("Location"),
            ];

            return new Response(
                json_encode($event),
                $status,
                ['Content-Type' => 'application/ld+json']
            );
        } catch (\Exception $e) {
            $this->log->debug("Exception Updating Fedora Resource: ", [
                'body' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteBinary(Request $request)
    {
        $token = $request->headers->get('Authorization');
        $url = $request->attributes->get('file_url');

        try {
            $fedora_response = $this->milliner->delete(
                $url,
                $token
            );

            if ($fedora_response) {
                return new Response(
                    $fedora_response->getBody(),
                    $fedora_response->getStatusCode()
                );
            } else {
                return new Response(
                    "No Fedora binary found for $url",
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

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteJsonld(Request $request)
    {
        $token = $request->headers->get('Authorization');
        $url = $request->attributes->get('html_url');

        try {
            $fedora_response = $this->milliner->delete(
                $url,
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
}
