<?php

namespace Islandora\Gemini\Service;

/**
 * Interface GeminiServiceInterface
 * @package Islandora\Gemini\Service
 */
interface GeminiServiceInterface
{
    /**
     * @param string $fedora_path
     * @return mixed string|null
     * @throws \Exception
     */
    public function getDrupalPath($fedora_path);

    /**
     * @param string $drupal_path
     * @return mixed string|null
     * @throws \Exception
     */
    public function getFedoraPath($drupal_path);

    /**
     * @param string $drupal_path
     * @param string $fedora_path
     * @throws \Exception
     */
    public function createPair($drupal_path, $fedora_path);

    /**
     * @param string $drupal_path
     * @return boolean
     * @throws \Exception
     */
    public function deleteFromDrupalPath($drupal_path);

    /**
     * @param string $fedora_path
     * @return boolean
     * @throws \Exception
     */
    public function deleteFromFedoraPath($fedora_path);
}
