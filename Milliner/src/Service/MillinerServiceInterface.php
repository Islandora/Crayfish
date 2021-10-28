<?php

namespace App\Islandora\Milliner\Service;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface MillinerServiceInterface
 * @package App\Islandora\Milliner\Service
 */
interface MillinerServiceInterface
{
    /**
     * @param string $uuid
     *   UUID of the Drupal resource.
     * @param string $jsonld_url
     *   URL of the Drupal resource in JSON-LD format.
     * @param string $islandora_fedora_endpoint
     *   Base URL of Fedora.
     * @param string|null $token
     *   The authorization token or null if no auth.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function saveNode(
        string $uuid,
        string $jsonld_url,
        string $islandora_fedora_endpoint,
        string $token = null
    ): ResponseInterface;

    /**
     * @param string $source_field
     *   The source field of the media being saved.
     * @param string $json_url
     *   The URL to the Drupal media resource in JSON format.
     * @param string $islandora_fedora_endpoint
     *   The Fedora base URL.
     * @param string|null $token
     *   The authorization token or null if no auth.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function saveMedia(
        string $source_field,
        string $json_url,
        string $islandora_fedora_endpoint,
        string $token = null
    ): ResponseInterface;

    /**
     * @param string $uuid
     *   The UUID of the Drupal resource to delete from Fedora.
     * @param string $islandora_fedora_endpoint
     *   The Fedora base URL.
     * @param string|null $token
     *   The authorization token or null if no auth.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function deleteNode(
        string $uuid,
        string $islandora_fedora_endpoint,
        string $token = null
    ): ResponseInterface;

    /**
     * @param string $uuid
     *   The UUID of the Drupal resource to save.
     * @param string $external_url
     *   The external URL Fedora will redirect to.
     * @param string $islandora_fedora_endpoint
     *   The Fedora base URL.
     * @param string|null $token
     *   The authorization token or null if no auth.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function saveExternal(
        string $uuid,
        string $external_url,
        string $islandora_fedora_endpoint,
        string $token = null
    ): ResponseInterface;

    /**
     * @param string $uuid
     *   The UUID of the Drupal resource to create a version of.
     * @param string $islandora_fedora_endpoint
     *   The Fedora base URL.
     * @param string|null $token
     *   The authorization token or null if no auth.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createVersion(
        string $uuid,
        string $islandora_fedora_endpoint,
        string $token = null
    ): ResponseInterface;

    /**
     * @param string $source_field
     *   The source field of the media to create a version of.
     * @param string $json_url
     *   The URL to the Drupal media resource in JSON format.
     * @param string $islandora_fedora_endpoint
     *   The Fedora base URL.
     * @param string|null $token
     *   The authorization token or null if no auth.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createMediaVersion(
        string $source_field,
        string $json_url,
        string $islandora_fedora_endpoint,
        string $token = null
    ): ResponseInterface;
}
