<?php

namespace Islandora\Milliner\Converter;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DrupalEntityConverter
 * @package Islandora\Milliner\Converter
 */
class DrupalEntityConverter
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * DrupalEntityConverter constructor.
     * @param \GuzzleHttp\Client $client
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(Client $client, LoggerInterface $log)
    {
        $this->client = $client;
        $this->log = $log;
    }
    /**
     * @param $path
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function convert($path, Request $request)
    {
        // Return the response from Drupal.
        $options = $this->preprocess($this->clean_path($path), $request);
        return $this->client->get($this->clean_path($path), $options);
    }

    /**
     * @param $path
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function convertJsonld($path, Request $request)
    {
        // Return the response from Drupal.
        $options = $this->preprocess($this->clean_path($path), $request);
        return $this->client->get($this->clean_path($path) . '?_format=jsonld', $options);
    }

    /**
     * @param $path
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    protected function preprocess($path, Request $request)
    {
        // Stuff the path onto the request for later.
        $request->attributes->set("path", $path);


        // Pass along authorization token if it exists.
        $options = ['http_errors' => false];
        if ($request->headers->has("Authorization")) {
            $options['headers'] = [
                "Authorization" => $request->headers->get("Authorization")
            ];
        }

        // Return guzzle options.
        return $options;
    }

    /**
     * @param $path
     * @return String of path with leading slash removed
     */
    private function clean_path($path) {
        $new_path = $path;
        // remove leading slash so path is relative to configured 'base_uri' 
        if (0 === strpos($new_path, '/') ) {
            $new_path = substr($new_path, 1, strlen($new_path)-1);
        }
        return $new_path;
    }
}
