<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 11/07/17
 * Time: 11:43 AM
 */

namespace Islandora\Milliner\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Class GeminiClient
 * @package Islandora\Milliner\Client
 */
class GeminiClient
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
     * GeminiClient constructor.
     * @param \GuzzleHttp\Client $client
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(Client $client, LoggerInterface $log)
    {
        $this->client = $client;
        $this->log = $log;
    }

    public static function create($base_url, LoggerInterface $log)
    {
        $trimmed = trim($base_url);
        $with_trailing_slash = rtrim($trimmed, '/') . '/';
        return new GeminiClient(
            new Client(['base_uri' => $with_trailing_slash]),
            $log
        );
    }

    /**
     * Gets a pair of drupal/fedora urls for a UUID.
     * @param $uuid
     * @param $token
     *
     * @throws \GuzzleHttp\Exception\RequestException
     *
     * @return array|null
     */
    public function getUrls(
        $uuid,
        $token = null
    ) {
        try {
            if (empty($token)) {
                $response = $this->client->get($uuid);
            } else {
                $response = $this->client->get($uuid, [
                    'headers' => [
                        'Authorization' => $token,
                    ],
                ]);
            }

            $this->log->debug("Gemini GET response", [
                'uuid' => $uuid,
                'response' => $response,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * Mints a new Fedora URL for a UUID.
     * @param $uuid
     * @param $token
     *
     * @throws \GuzzleHttp\Exception\RequestException
     *
     * @return string
     */
    public function mintFedoraUrl(
        $uuid,
        $token = null
    ) {
        $headers = ['Content-Type' => 'text/plain'];

        if (!empty($token)) {
            $headers['Authorization'] = $token;
        }

        $response = $this->client->post('', [
            'body' => $uuid,
            'headers' => $headers,
        ]);

        $this->log->debug("Gemini POST response", [
            'uuid' => $uuid,
            'response' => $response,
        ]);

        return (string)$response->getBody();
    }

    /**
     * Saves a pair of drupal/fedora urls for a UUID.
     * @param $uuid
     * @param $drupal
     * @param $fedora
     * @param $token
     *
     * @throws \GuzzleHttp\Exception\RequestException
     *
     * @return bool
     */
    public function saveUrls(
        $uuid,
        $drupal,
        $fedora,
        $token = null
    ) {
        $body = json_encode(['drupal' => $drupal, 'fedora' => $fedora]);

        $headers = ['Content-Type' => 'application/json'];

        if (!empty($token)) {
            $headers['Authorization'] = $token;
        }

        $response = $this->client->put($uuid, [
            'body' => $body,
            'headers' => $headers,
        ]);

        $this->log->debug("Gemini PUT response", [
            'uuid' => $uuid,
            'response' => $response,
        ]);

        return true;
    }

    /**
     * Deletes a pair of drupal/fedora urls for a UUID.
     * @param $uuid
     * @param $token
     *
     * @throws \GuzzleHttp\Exception\RequestException
     *
     * @return bool
     */
    public function deleteUrls(
        $uuid,
        $token
    ) {
        $response = $this->client->delete($uuid, [
            'headers' => [
                'Authorization' => $token,
            ],
        ]);
        $this->log->debug("Gemini DELETE response", [
            'uuid' => $uuid,
            'response' => $response,
        ]);
        return true;
    }
}