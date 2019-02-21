<?php

namespace Islandora\Gemini\UrlMapper;

/**
 * Interface UrlMapperInterface
 * @package Islandora\Crayfish\Commons
 */
interface UrlMapperInterface
{
    /**
     * @param string $uuid
     *
     * @throws \Exception
     *
     * @return mixed array|null
     */
    public function getUrls(
        $uuid
    );

    /**
     * @param string $uuid
     * @param string $drupal
     * @param string $fedora
     *
     * @throws \Exception
     *
     * @return bool True if new record is created.
     */
    public function saveUrls(
        $uuid,
        $drupal,
        $fedora
    );

    /**
     * @param string $uuid
     *
     * @throws \Exception
     *
     * @return bool True if record is found and deleted.
     */
    public function deleteUrls(
        $uuid
    );

    /**
     * Locate a URI provided the opposite URI.
     *
     * Given either the Drupal or Fedora URI, search the Gemini DB and return
     * the other URI. Otherwise return null.
     *
     * @param string $uri
     *   The known URI (either Fedora or Drupal).
     *
     * @return mixed array|null
     *   The other URI if found.
     */
    public function findUrls(
        $uri
    );
}
