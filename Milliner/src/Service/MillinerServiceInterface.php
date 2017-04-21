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
    public function create(
        $drupal_jsonld,
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
    public function update(
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
