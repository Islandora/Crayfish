<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 19/06/17
 * Time: 2:13 PM
 */

namespace Islandora\Milliner\Service;

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
    public function mint($context) {
        $segments = [
            substr($context, 0, 2),
            substr($context, 2, 2),
            substr($context, 4, 2),
            substr($context, 6, 2),
        ];

        $path = implode("/", $segments) . "/$context";

        return $this->base_url . $path;
    }
}
