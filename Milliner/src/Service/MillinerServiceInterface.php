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
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function saveContent(
        $uuid,
        $jsonld_url,
        $token = null
    );

    /**
     * @param $json_url
     * @param $jsonld_url
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function saveMedia(
        $json_url,
        $jsonld_url,
        $token = null
    );

    /**
     * @param $uuid
     * @param $file_url
     * @param $checksum_url
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function saveFile(
        $uuid,
        $file_url,
        $checksum_url,
        $token = null
    );

    /**
     * @param $uuid
     * @param $token
     *
     * @throws \Exception
     *
     * @return \GuzzleHttp\Psr7\Response|null
     */
    public function delete(
        $uuid,
        $token = null
    );

}
