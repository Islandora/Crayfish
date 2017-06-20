<?php

namespace Islandora\Milliner\Service;

/**
 * Interface MillinerServiceInterface
 * @package Islandora\Milliner\Service
 */
interface MillinerServiceInterface
{
    /**
     * @param $stream
     * @param $mimetype
     * @param $drupal_url
     * @param $uuid
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function saveBinary(
        $stream,
        $mimetype,
        $drupal_url,
        $uuid,
        $token
    );

    /**
     * @param $jsonld
     * @param $drupal_url
     * @param $uuid
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function saveJsonld(
        $jsonld,
        $drupal_url,
        $uuid,
        $token
    );

    /**
     * @param $drupal_url
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function delete(
        $drupal_url,
        $token
    );
}
