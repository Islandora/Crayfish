<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 07/04/17
 * Time: 12:05 AM
 */

namespace Islandora\Milliner\Converter;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class DrupalEntityConverter
{
    protected $client;

    protected $log;

    public function __construct(Client $client, LoggerInterface $log)
    {
        $this->client = $client;
        $this->log = $log;
    }

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