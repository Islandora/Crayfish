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
    protected $base_url;

    public function __construct(
        $base_url
    ) {
        $trimmed = trim($base_url);
        $this->base_url = rtrim($trimmed, '/') . '/';
    }

    /**
     * {@inheritdoc}
     */
    public function mint($context, $container_name)
    {
        if (strlen($context) < 8) {
            throw new \InvalidArgumentException(
                "Provided UUID must be at least of length 8 to account for pair-trees",
                400
            );
        }

        $segments = str_split(substr($context, 0, 8), 2);

        $path = implode("/", $segments) . "/$context";

        if (!empty($container_name)) {
            $minted_url = $this->base_url . $container_name . '/' . $path;
        } else {
            $minted_url = $this->base_url . $path;
        }

        return $minted_url;
    }
}
