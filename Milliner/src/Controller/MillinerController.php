<?php

namespace Islandora\Milliner\Controller;

use Islandora\Milliner\Service\MillinerServiceInterface;
use Psr\Http\Message\ResponseInterface;
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
        $drupal_response = $request->attributes->get("drupal_response");
        $stream = $drupal_response->getBody();
        $mimetype = $drupal_response->getHeader("Content-Type");
        $url = $request->attributes->get('file_url');
        $uuid = $request->attributes->get('uuid');
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->saveBinary(
                $stream,
                $mimetype,
                $url,
                $uuid,
                $token
            );
            return new Response(
                $fedora_response->getBody(),
                $fedora_response->getStatusCode(),
                $fedora_response->getHeaders()
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
        $drupal_response = $request->attributes->get("drupal_response");
        $jsonld = (string)$drupal_response->getBody();
        $url = $request->attributes->get('html_url');
        $uuid = $request->attributes->get('uuid');
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->saveJsonld(
                $jsonld,
                $url,
                $uuid,
                $token
            );
            return new Response(
                $fedora_response->getBody(),
                $fedora_response->getStatusCode()
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
