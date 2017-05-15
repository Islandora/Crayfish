<?php

namespace Islandora\Milliner\Service;

/**
 * Interface MillinerServiceInterface
 * @package Islandora\Milliner\Service
 */
interface MillinerServiceInterface
{
    /**
     * @param $drupal_jsonld
     * @param $drupal_path
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function createRdf(
        $drupal_jsonld,
        $drupal_path,
        $token
    );

    /**
     * @param $drupal_binary
     * @param $mimetype
     * @param $drupal_path
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function createBinary(
        $drupal_binary,
        $mimetype,
        $drupal_path,
        $token
    );

    /**
     * @param $drupal_jsonld
     * @param $drupal_path
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function updateRdf(
        $drupal_jsonld,
        $drupal_path,
        $token
    );

    /**
     * @param $drupal_path
     * @param $token
     *
     * @throws \RuntimeException
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function delete(
        $drupal_path,
        $token
    );
}
