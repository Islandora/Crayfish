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
    public function saveBinary(Request $request)
    {
        $stream = $request->attributes->get('file');
        $mimetype = $request->attributes->get('mimetype');
        $jsonld_url = $request->attributes->get('jsonld_url');
        $file_url = $request->attributes->get('file_url');
        $uuid = $request->attributes->get('uuid');
        $token = $request->headers->get('Authorization', null);

        try {
            $response = $this->milliner->saveBinary(
                $stream,
                $mimetype,
                $file_url,
                $jsonld_url,
                $uuid,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
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
        $token = $request->headers->get('Authorization', null);

        try {
            $response = $this->milliner->saveJsonld(
                $jsonld,
                $url,
                $uuid,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
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

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteJsonld(Request $request)
    {
        $token = $request->headers->get('Authorization');
        $url = $request->attributes->get('jsonld_url');

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
