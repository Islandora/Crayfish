<?php

namespace Islandora\Milliner\Controller;

use Islandora\Milliner\Service\MillinerServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MillinerController
{

    protected $milliner;

    protected $log;

    public function __construct(MillinerServiceInterface $milliner, LoggerInterface $log)
    {
        $this->milliner = $milliner;
        $this->log = $log;
    }

    public function create(ResponseInterface $drupal_entity, Request $request)
    {
        $status = $drupal_entity->getStatusCode();
        if ($status != 200) {
            $this->log->debug("Drupal Entity: ", [
              'body' => $drupal_entity->getBody(),
              'status' => $status,
              'headers' => $drupal_entity->getHeaders()
            ]);
            return new Response(
                "Error from Drupal: " . $drupal_entity->getReasonPhrase(),
                $status
            );
        }

        try {
            $fedora_response = $this->milliner->create($drupal_entity, $request);
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

    public function update(ResponseInterface $drupal_entity, Request $request)
    {
        $status = $drupal_entity->getStatusCode();
        if ($status != 200) {
            $this->log->debug("Drupal Entity: ", [
                'body' => $drupal_entity->getBody(),
                'status' => $status,
                'headers' => $drupal_entity->getHeaders()
            ]);
            return new Response(
                "Error from Drupal: " . $drupal_entity->getReasonPhrase(),
                $status
            );
        }

        try {
            $fedora_response = $this->milliner->update($drupal_entity, $request);
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

    public function delete($path, Request $request)
    {
        try {
            $fedora_response = $this->milliner->delete($path, $request);
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
