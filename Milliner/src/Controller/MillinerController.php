<?php

namespace App\Islandora\Milliner\Controller;

use App\Islandora\Milliner\Service\MillinerServiceInterface;
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
     * @var \App\Islandora\Milliner\Service\MillinerServiceInterface
     */
    protected MillinerServiceInterface $milliner;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $log;

    /**
     * MillinerController constructor.
     * @param \App\Islandora\Milliner\Service\MillinerServiceInterface $milliner
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(MillinerServiceInterface $milliner, LoggerInterface $log)
    {
        $this->milliner = $milliner;
        $this->log = $log;
    }

    /**
     * @param string $uuid
     *   The UUID of the Drupal resource to save.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     * @return \Symfony\Component\HttpFoundation\Response
     *   The response generated from the response from Fedora.
     */
    public function saveNode(string $uuid, Request $request): Response
    {
        $token = $request->headers->get("Authorization", null);
        $jsonld_url = $request->headers->get("Content-Location");
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        if (empty($jsonld_url)) {
            return new Response("Expected JSONLD url in Content-Location header", 400);
        }

        $this->log->debug("JSONLD URL: $jsonld_url");
        $this->log->debug("FEDORA ENDPOINT: $islandora_fedora_endpoint");
        try {
            $response = $this->milliner->saveNode(
                $uuid,
                $jsonld_url,
                $islandora_fedora_endpoint,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("Caught exception creating node resource.", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $uuid
     *  The UUID of the Drupal resource to delete.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *  The request.
     * @return \Symfony\Component\HttpFoundation\Response
     *  The response generated from the response from Fedora.
     */
    public function deleteNode(string $uuid, Request $request): Response
    {
        $token = $request->headers->get("Authorization", null);
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        try {
            $response = $this->milliner->deleteNode(
                $uuid,
                $islandora_fedora_endpoint,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("Caught exception deleting resource.", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $source_field
     *  The source field of the media being saved.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *  The request.
     * @return \Symfony\Component\HttpFoundation\Response
     *  The response generated from the response from Fedora.
     */
    public function saveMedia(string $source_field, Request $request): Response
    {
        $token = $request->headers->get("Authorization", null);
        $json_url = $request->headers->get("Content-Location");
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        if (empty($json_url)) {
            return new Response("Expected JSON url in Content-Location header", 400);
        }

        try {
            $response = $this->milliner->saveMedia(
                $source_field,
                $json_url,
                $islandora_fedora_endpoint,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("Caught exception saving media resource.", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $uuid
     *  The UUID of the Drupal resource to save as external.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *  The request.
     * @return \Symfony\Component\HttpFoundation\Response
     *  The response generated from the response from Fedora.
     */
    public function saveExternal(string $uuid, Request $request): Response
    {
        $token = $request->headers->get("Authorization", null);
        $external_url = $request->headers->get("Content-Location");
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        if (empty($external_url)) {
            return new Response("Expected external url in Content-Location header", 400);
        }

        try {
            $response = $this->milliner->saveExternal(
                $uuid,
                $external_url,
                $islandora_fedora_endpoint,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("Caught exception saving external content resource.", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $uuid
     *  The UUID of the Drupal resource to create a version of.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *  The request.
     * @return \Symfony\Component\HttpFoundation\Response
     *  The response generated from the response from Fedora.
     */
    public function createNodeVersion(string $uuid, Request $request): Response
    {
        $token = $request->headers->get("Authorization", null);
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        try {
            $response = $this->milliner->createVersion(
                $uuid,
                $islandora_fedora_endpoint,
                $token
            );
            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("Caught exception creating node version", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $source_field
     *  The source field of the Drupal resource to create a version of media.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *  The request.
     * @return \Symfony\Component\HttpFoundation\Response
     *  The response generated from the response from Fedora.
     */
    public function createMediaVersion(string $source_field, Request $request): Response
    {
        $token = $request->headers->get("Authorization", null);
        $json_url = $request->headers->get("Content-Location");
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        try {
            $response = $this->milliner->createMediaVersion(
                $source_field,
                $json_url,
                $islandora_fedora_endpoint,
                $token
            );
            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("Caught exception when creating media version", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }
}
