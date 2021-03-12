<?php

namespace Islandora\Milliner\Service;

/**
 * Interface MillinerServiceInterface
 * @package Islandora\Milliner\Service
 */
interface MillinerServiceInterface
{
    /**
     * @param $uuid
     * @param $jsonld_url
     * @param $islandora_fedora_endpoint
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function saveNode(
        $uuid,
        $jsonld_url,
        $islandora_fedora_endpoint,
        $token = null
    );

    /**
     * @param $source_field
     * @param $json_url
     * @param $islandora_fedora_endpoint
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function saveMedia(
        $source_field,
        $json_url,
        $islandora_fedora_endpoint,
        $token = null
    );

    /**
     * @param $uuid
     * @param $islandora_fedora_endpoint
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response|null
     */
    public function deleteNode(
        $uuid,
        $islandora_fedora_endpoint,
        $token = null
    );

    /**
     * @param $uuid
     * @param $external_url
     * @param $islandora_fedora_endpoint
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response|null
     */
    public function saveExternal(
        $uuid,
        $external_url,
        $islandora_fedora_endpoint,
        $token = null
    );

    /**
     * @param $uuid
     * @param $islandora_fedora_endpoint
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function createVersion(
        $uuid,
        $islandora_fedora_endpoint,
        $token = null
    );

    /**
     * @param $source_field
     * @param $json_url
     * @param $islandora_fedora_endpoint
     * @param $token
     *
     * @throws \Exception
     *
     * @return array
     */
    public function createMediaVersion(
        $source_field,
        $json_url,
        $islandora_fedora_endpoint,
        $token = null
    );
}
