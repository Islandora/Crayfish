<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 19/06/17
 * Time: 2:13 PM
 */

namespace Islandora\Gemini\UrlMinter;

class UrlMinter implements UrlMinterInterface
{
    /**
     * {@inheritdoc}
     */
    public function mint($context, $fedora_container_url)
    {
        if (strlen($context) < 8) {
            throw new \InvalidArgumentException(
                "Provided UUID must be at least of length 8 to account for pair-trees",
                400
            );
        }

        $segments = str_split(substr($context, 0, 8), 2);

        $path = implode("/", $segments) . "/$context";
        $minted_url = $fedora_container_url . '/' . $path;

        return $minted_url;
    }
}
