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
    public function create(ResponseInterface $drupal_entity, Request $request)
    {
        $status = $drupal_entity->getStatusCode();
        if ($status != 200) {
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

        $drupal_jsonld = (string)$drupal_entity->getBody();
        $drupal_path = $request->get('path');
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->create(
                $drupal_jsonld,
                $drupal_path,
                $token
            );
            return new Response(
                $fedora_response->getBody(),
                $fedora_response->getStatusCode()
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
    public function update(ResponseInterface $drupal_entity, Request $request)
    {
        $status = $drupal_entity->getStatusCode();
        if ($status != 200) {
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

        $drupal_jsonld = (string)$drupal_entity->getBody();
        $drupal_path = $request->get('path');
        $token = $request->headers->get('Authorization');

        try {
            $fedora_response = $this->milliner->update(
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
}
