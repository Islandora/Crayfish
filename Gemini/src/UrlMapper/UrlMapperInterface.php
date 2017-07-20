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
}
