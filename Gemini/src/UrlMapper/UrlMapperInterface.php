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
     * @return bool
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
     * @return bool
     */
    public function deleteUrls(
        $uuid
    );
}
