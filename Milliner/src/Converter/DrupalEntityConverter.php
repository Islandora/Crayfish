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
        // Stuff the path onto the request for later.
        $request->attributes->set("path", $path);


        // Pass along authorization token if it exists.
        $options = ['http_errors' => false];
        if ($request->headers->has("Authorization")) {
            $options['headers'] = [
              "Authorization" => $request->headers->get("Authorization")
            ];
        }

        // Return the response from Drupal.
        return $this->client->get("$path?_format=jsonld", $options);
    }
}
