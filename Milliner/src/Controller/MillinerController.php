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
     * @param \Psr\Http\Message\ResponseInterface $drupal_entity
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createRdf(ResponseInterface $drupal_entity, Request $request)
    {
        if ($response = $this->processDrupalResponse($drupal_entity)) {
            return $response;
        }

        $drupal_jsonld = (string)$drupal_entity->getBody();
        $drupal_path = $request->get('path');
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->createRdf(
                $drupal_jsonld,
                $drupal_path,
                $token
            );
            return new Response(
                $fedora_response->getBody(),
                $fedora_response->getStatusCode(),
                $fedora_response->getHeaders()
            );
        } catch (\Exception $e) {
            $this->log->debug("Exception Creating Fedora Resource: ", [
              'body' => $e->getMessage(),
              'status' => $e->getCode(),
            ]);
            return new Response(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function createBinary(
        ResponseInterface $drupal_entity,
        Request $request
    ) {
        if ($response = $this->processDrupalResponse($drupal_entity)) {
            return $response;
        }

        $drupal_binary = $drupal_entity->getBody();
        $mimetype = $drupal_entity->getHeader("Content-Type");
        $drupal_path = $request->get('path');
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->createBinary(
                $drupal_binary,
                $mimetype,
                $drupal_path,
                $token
            );
            return new Response(
                $fedora_response->getBody(),
                $fedora_response->getStatusCode(),
                $fedora_response->getHeaders()
            );
        } catch (\Exception $e) {
            $this->log->debug("Exception Creating Fedora Resource: ", [
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
     * @param \Psr\Http\Message\ResponseInterface $drupal_entity
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateRdf(ResponseInterface $drupal_entity, Request $request)
    {
        if ($response = $this->processDrupalResponse($drupal_entity)) {
            return $response;
        }

        $drupal_jsonld = (string)$drupal_entity->getBody();
        $drupal_path = $request->get('path');
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->updateRdf(
                $drupal_jsonld,
                $drupal_path,
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
     * @param $drupal_path
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete($drupal_path, Request $request)
    {
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->delete(
                $drupal_path,
                $token
            );
            return new Response(
                $fedora_response->getBody(),
                $fedora_response->getStatusCode()
            );
        } catch (\Exception $e) {
            $this->log->debug("Exception Deleting Fedora Resource: ", [
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
     * @param \Psr\Http\Message\ResponseInterface $drupal_entity
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    protected function processDrupalResponse(ResponseInterface $drupal_entity)
    {
        $status = $drupal_entity->getStatusCode();

        // Exit early if response was OK.
        if ($status == 200) {
            return null;
        }

        // Otherwise return error response.
        $this->log->debug("Drupal Entity: ", [
            'body' => (string)$drupal_entity->getBody(),
            'status' => $status,
            'headers' => $drupal_entity->getHeaders()
        ]);
        return new Response(
            "Error from Drupal: " . $drupal_entity->getReasonPhrase(),
            $status
        );
    }
}
