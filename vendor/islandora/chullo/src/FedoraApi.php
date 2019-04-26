<?php

/**
 * This file is part of Islandora.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP Version 5.5.9
 *
 * @category Islandora
 * @package  Islandora
 * @author   Daniel Lamb <dlamb@islandora.ca>
 * @author   Nick Ruest <ruestn@gmail.com>
 * @author   Jared Whiklo <Jared.Whiklo@umanitoba.ca>
 * @author   Diego Pino <dpino@metro.org>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://www.islandora.ca
 */

namespace Islandora\Chullo;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation of IFedoraApi using Guzzle.
 */
class FedoraApi implements IFedoraApi
{

    protected $client;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create($fedora_rest_url)
    {
        $normalized = rtrim($fedora_rest_url);
        $normalized = rtrim($normalized, '/') . '/';
        $guzzle = new Client(['base_uri' => $normalized]);
        return new static($guzzle);
    }

    /**
     * Gets the Fedora base uri (e.g. http://localhost:8080/fcrepo/rest)
     *
     * @return string
     */
    public function getBaseUri()
    {
        return $this->client->getConfig('base_uri');
    }

    /**
     * Gets a Fedora resource.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return ResponseInterface
     */
    public function getResource(
        $uri = "",
        $headers = []
    ) {
        // Set headers
        $options = ['http_errors' => false, 'headers' => $headers];

        // Send the request.
        return $this->client->request(
            'GET',
            $uri,
            $options
        );
    }

    /**
     * Gets a Fedora resoure's headers.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return ResponseInterface
     */
    public function getResourceHeaders(
        $uri = "",
        $headers = []
    ) {

        // Send the request.
        return $this->client->request(
            'HEAD',
            $uri,
            ['http_errors' => false, 'headers' => $headers]
        );
    }

    /**
     * Gets information about the supported HTTP methods, etc., for a Fedora resource.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return ResponseInterface
     */
    public function getResourceOptions(
        $uri = "",
        $headers = []
    ) {
        return $this->client->request(
            'OPTIONS',
            $uri,
            ['http_errors' => false, 'headers' => $headers]
        );
    }

    /**
     * Creates a new resource in Fedora.
     *
     * @param string    $uri                  Resource URI
     * @param string    $content              String or binary content
     * @param array     $headers              HTTP Headers
     *
     * @return ResponseInterface
     */
    public function createResource(
        $uri = "",
        $content = null,
        $headers = []
    ) {
        $options = ['http_errors' => false];

        // Set content.
        $options['body'] = $content;

        // Set headers.
        $options['headers'] = $headers;

        return $this->client->request(
            'POST',
            $uri,
            $options
        );
    }

    /**
     * Saves a resource in Fedora.
     *
     * @param string    $uri                  Resource URI
     * @param string    $content              String or binary content
     * @param array     $headers              HTTP Headers
     *
     * @return ResponseInterface
     */
    public function saveResource(
        $uri,
        $content = null,
        $headers = []
    ) {
        $options = ['http_errors' => false];

        // Set content.
        $options['body'] = $content;

        // Set headers.
        $options['headers'] = $headers;

        return $this->client->request(
            'PUT',
            $uri,
            $options
        );
    }

    /**
     * Modifies a resource using a SPARQL Update query.
     *
     * @param string    $uri            Resource URI
     * @param string    $sparql         SPARQL Update query
     * @param array     $headers        HTTP Headers
     *
     * @return ResponseInterface
     */
    public function modifyResource(
        $uri,
        $sparql = "",
        $headers = []
    ) {
        $options = ['http_errors' => false];

        // Set content.
        $options['body'] = $sparql;

        // Set headers.
        $options['headers'] = $headers;
        $options['headers']['content-type'] = 'application/sparql-update';

        return $this->client->request(
            'PATCH',
            $uri,
            $options
        );
    }

    /**
     * Issues a DELETE request to Fedora.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return ResponseInterface
     */
    public function deleteResource(
        $uri = '',
        $headers = []
    ) {
        $options = ['http_errors' => false, 'headers' => $headers];

        return $this->client->request(
            'DELETE',
            $uri,
            $options
        );
    }
}
