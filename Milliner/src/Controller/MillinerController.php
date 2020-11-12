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
     * @param string $uuid
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveNode($uuid, Request $request)
    {
        $token = $request->headers->get("Authorization", null);
        $jsonld_url = $request->headers->get("Content-Location");
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        if (empty($jsonld_url)) {
            return new Response("Expected JSONLD url in Content-Location header", 400);
        }

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
            $this->log->error("", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $uuid
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteNode($uuid, Request $request)
    {
        $token = $request->headers->get("Authorization", null);

        try {
            $response = $this->milliner->deleteNode(
                $uuid,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $source_field
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveMedia($source_field, Request $request)
    {
        $token = $request->headers->get("Authorization", null);
        $json_url = $request->headers->get("Content-Location");
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        if (empty($json_url)) {
            return new Response("Expected JSON url in Content-Location header", 400);
        }

        try {
            $response = $this->milliner->saveMedia(
                $json_url,
                $islandora_fedora_endpoint,
                $token
            );

            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $uuid
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveExternal($uuid, Request $request)
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
            $this->log->error("", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $uuid
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createNodeVersion($uuid, Request $request)
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
            $this->log->error("", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }

    /**
     * @param string $source_field
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createMediaVersion($source_field, Request $request)
    {
        $token = $request->headers->get("Authorization", null);
        $json_url = $request->headers->get("Content-Location");
        $islandora_fedora_endpoint = $request->headers->get("X-Islandora-Fedora-Endpoint");

        try {
            $response = $this->milliner->createMediaVersion(
                $json_url,
		$islandora_fedora_endpoint,
                $token
            );
            return new Response(
                $response->getBody(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->error("", ['Exception' => $e]);
            $code = $e->getCode() == 0 ? 500 : $e->getCode();
            return new Response($e->getMessage(), $code);
        }
    }
}
