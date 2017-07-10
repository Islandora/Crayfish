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
     * @param $file_url
     * @param $jsonld_url
     * @param $uuid
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function saveNonRdf(
        $stream,
        $mimetype,
        $file_url,
        $jsonld_url,
        $uuid,
        $token
    );

    /**
     * @param $jsonld
     * @param $url
     * @param $uuid
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function saveRdf(
        $jsonld,
        $url,
        $uuid,
        $token
    );

    /**
     * @param $url
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \GuzzleHttp\Psr7\Response|null
     */
    public function deleteRdf(
        $url,
        $uuid,
        $token
    );

    /**
     * @param $url
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \GuzzleHttp\Psr7\Response|null
     */
    public function deleteNonRdf(
        $file_url,
        $jsonld_url,
        $token
    );
}
