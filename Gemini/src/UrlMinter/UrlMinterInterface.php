<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 19/06/17
 * Time: 2:11 PM
 */

namespace Islandora\Gemini\UrlMinter;

interface UrlMinterInterface
{
    /**
     * Mints a new uri from an arbitrary input.
     *
     * @param $context
     * @param $fedora_container_url
     * @return string
     */
    public function mint($context, $fedora_container_url);
}
