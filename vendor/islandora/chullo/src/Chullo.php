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
 * @author   Diego Pino <dpino@metro.org>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://www.islandora.ca
 */

namespace Islandora\Chullo;

use GuzzleHttp\Client;

/**
 * Default implementation of IFedoraClient
 */
class Chullo implements IFedoraClient
{

    protected $api; // IFedoraApi

    /**
     * @codeCoverageIgnore
     */
    public function __construct(IFedoraApi $api)
    {
        $this->api = $api;
    }

    public static function create($fedora_rest_url)
    {
        $api = FedoraApi::create($fedora_rest_url);
        return new static($api);
    }

    /**
     * Gets the Fedora base uri (e.g. http://localhost:8080/fcrepo/rest)
     *
     * @return string
     */
    public function getBaseUri()
    {
        return $this->api->getBaseUri();
    }

    /**
     * Gets a Fedora resource.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return mixed    Full response if found.  Null otherwise.
     */
    public function getResource(
        $uri = "",
        $headers = []
    ) {
        $response = $this->api->getResource(
            $uri,
            $headers
        );
        if ($response->getStatusCode() != 200) {
            return null;
        }

        return (string)$response->getBody();
    }

    /**
     * Gets a Fedora resource's headers.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return array    Headers of a resource, null on failure
     */
    public function getResourceHeaders(
        $uri = "",
        $headers = []
    ) {
        $response = $this->api->getResourceHeaders(
            $uri,
            $headers
        );

        if ($response->getStatusCode() != 200) {
            return null;
        }

        return $response->getHeaders();
    }

    /**
     * Gets information about the supported HTTP methods, etc., for a Fedora resource.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return string   Options of a resource.
     */
    public function getResourceOptions(
        $uri = "",
        $headers = []
    ) {
        $response = $this->api->getResourceOptions(
            $uri,
            $headers
        );

        return $response->getHeaders();
    }

    /**
     * Gets RDF metadata from Fedora.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return EasyRdf_Graph    EasyRdf_Graph if found, null otherwise
     */
    public function getGraph(
        $uri = "",
        $headers = []
    ) {
        $headers['Accept'] = 'application/ld+json';
        $rdf = $this->getResource($uri, $headers);
        if (empty($rdf)) {
            return null;
        }

        $graph = new \EasyRdf_Graph();
        $graph->parse($rdf, 'jsonld');
        return $graph;
    }

    /**
     * Creates a new resource in Fedora.
     *
     * @param string    $uri                  Resource URI
     * @param string    $content              String or binary content
     * @param array     $headers              HTTP Headers
     *
     * @return string   Uri of newly created resource or null if failed
     */
    public function createResource(
        $uri = "",
        $content = null,
        $headers = []
    ) {
        $response = $this->api->createResource(
            $uri,
            $content,
            $headers
        );

        if ($response->getStatusCode() != 201) {
            return null;
        }

        // Return the value of the location header
        $locations = $response->getHeader('Location');
        return reset($locations);
    }

    /**
     * Saves a resource in Fedora.
     *
     * @param string    $uri                  Resource URI
     * @param string    $content              String or binary content
     * @param array     $headers              HTTP Headers
     *
     * @return boolean  True if successful
     */
    public function saveResource(
        $uri,
        $content = null,
        $headers = []
    ) {
        $response = $this->api->saveResource(
            $uri,
            $content,
            $headers
        );

        return $response->getStatusCode() == 204;
    }

    /**
     * Saves RDF in Fedora.
     *
     * @param string            $uri            Resource URI
     * @param EasyRdf_Resource  $graph          Graph to save
     * @param array             $headers        HTTP Headers
     *
     * @return boolean  True if successful
     */
    public function saveGraph(
        $uri,
        \EasyRdf_Graph $graph,
        $headers = []
    ) {
        // Serialze the rdf.
        $turtle = $graph->serialise('turtle');

        // Checksum it.
        $checksum_value = sha1($turtle);

        // Set headers.
        $headers['Content-Type'] = 'text/turtle';
        $headers['digest'] = 'sha1=' . $checksum_value;

        // Save it.
        return $this->saveResource(
            $uri,
            $turtle,
            $headers
        );
    }

    /**
     * Modifies a resource using a SPARQL Update query.
     *
     * @param string    $uri            Resource URI
     * @param string    $sparql         SPARQL Update query
     * @param array     $headers        HTTP Headers
     *
     * @return boolean  True if successful
     */
    public function modifyResource(
        $uri,
        $sparql = "",
        $headers = []
    ) {
        $response = $this->api->modifyResource(
            $uri,
            $sparql,
            $headers
        );

        return $response->getStatusCode() == 204;
    }

    /**
     * Issues a DELETE request to Fedora.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return boolean  True if successful
     */
    public function deleteResource(
        $uri = '',
        $headers = []
    ) {
        $response = $this->api->deleteResource(
            $uri,
            $headers
        );

        return $response->getStatusCode() == 204;
    }
}
