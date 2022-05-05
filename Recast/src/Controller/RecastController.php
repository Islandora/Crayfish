<?php

namespace App\Islandora\Recast\Controller;

use EasyRdf\Exception;
use EasyRdf\Format;
use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Recast Controller
 */
class RecastController
{

    /**
     * @var \Monolog\Logger
     */
    private $log;

    /**
     * @var \Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface
     */
    private $entityMapper;

    /**
     * @var \GuzzleHttp\Client
     */
    private $http;

    /**
     * @var array
     */
    protected $availableMethods = [
        'add',
        'replace',
    ];

    /**
     * The Fedora base url, for URL detection.
     * @var string
     */
    private $fcrepo_base_url;

    /**
     * The Drupal base url, for URL detection.
     * @var string
     */
    private $drupal_base_url;

    /**
     * Array of Fedora namespace prefixes.
     * @var array
     */
    private $namespaces;

    /**
     * RecastController constructor.
     *
     * @param \Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface $entityMapper
     * @param \GuzzleHttp\Client $http
     * @param \Psr\Log\LoggerInterface $log
     * @param string $drupal_base_url
     * @param string $fcrepo_base_url
     * @param array $namespaces
     */
    public function __construct(
        EntityMapperInterface $entityMapper,
        Client $http,
        LoggerInterface $log,
        string $drupal_base_url,
        string $fcrepo_base_url,
        array $namespaces
    ) {
        $this->entityMapper = $entityMapper;
        $this->http = $http;
        $this->log = $log;
        $this->drupal_base_url = $drupal_base_url;
        $this->fcrepo_base_url = $fcrepo_base_url;
        $this->namespaces = $namespaces;
    }

    /**
     * Send API-X Options information.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     *   The turtle file of the options response.
     */
    public function recastOptions(): BinaryFileResponse
    {
        return new BinaryFileResponse(
            __DIR__ . "/../../public/static/options.ttl",
            200,
            ['Content-Type' => 'text/turtle']
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request.
     * @param string $operation
     *   Whether to add or
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   The response.
     */
    public function recast(Request $request, string $operation): Response
    {
        if (!in_array($operation, $this->availableMethods)) {
            return new Response(
                sprintf(
                    'Valid methods for recast are [%s] received "%s".',
                    implode(', ', $this->availableMethods),
                    $operation
                ),
                400
            );
        }
        $this->log->info("Request to {$operation} resource.");

        $token = null;
        if ($request->headers->has('Authorization')) {
            $token = $request->headers->get('Authorization');
        }

        $fedora_uri = $request->headers->get("Apix-Ldp-Resource");
        $fedora_resource = $request->attributes->get('fedora_resource');

        // Look for a describes Link header.
        $describe_uri = $fedora_resource->hasHeader('Link') ? self::describeUri($fedora_resource->getheader('Link'))
            : false;
        if ($describe_uri !== false) {
            // We found a describes URI so use that for the subject of the graph.
            $fedora_uri = $describe_uri;
        }

        $body = (string)$fedora_resource->getBody();
        $mimeType = $fedora_resource->getHeader('Content-type');
        if (is_array($mimeType)) {
            $mimeType = reset($mimeType);
        }
        if (preg_match("/^([^;]+);/", $mimeType, $matches)) {
            $mimeType = $matches[1];
        }
        try {
            $format = Format::getFormat($mimeType)->getName();
        } catch (Exception $e) {
            $this->log->info("Could not parse format {$mimeType}");
            return new Response("Cannot process resource in format ({$mimeType})", 400);
        }

        try {
            $graph = new Graph();
            $graph->parse(
                $body,
                $format,
                $fedora_uri
            );
        } catch (Exception $e) {
            $this->log->error("Error parsing graph in {$format}");
            return new Response("Error parsing graph", 400);
        }

        $resources = $graph->resources();
        foreach ($resources as $uri => $data) {
            // Ignore http vs https
            $exploded = explode('://', $uri);
            // Add a default, just in case.
            $protocol = "http";
            if (count($exploded) > 1) {
                $protocol = $exploded[0];
                $without_protocol = $exploded[1];
            }

            // Check for Drupal urls, making sure to ignore Fedora urls.
            // They may share a domain so false positives can happen.
            $is_drupal_url = strpos($without_protocol, $this->drupal_base_url) === 0 &&
                strpos($without_protocol, $this->fcrepo_base_url) !== 0;

            $this->log->debug("Looking for reverse URI for: $uri");
            $this->log->debug("$uri " . $is_drupal_url ? 'is a Drupal URL' : 'is not a Drupal URL');

            if ($is_drupal_url) {
                $reverse_uri = $this->getFedoraUrl($uri, $this->fcrepo_base_url, $token);

                if (!empty($reverse_uri)) {
                    // Add the protocol back in.
                    $reverse_uri = "{$protocol}://{$reverse_uri}";
                    // Don't rewrite the current URI (in-case of sameAs)
                    if ($reverse_uri !== $fedora_uri) {
                        $predicate = $this->findPredicateForObject($graph, $uri);
                        $this->log->debug('Found a reverse URI', [
                            'original_uri' => $uri,
                            'reverse_uri' => $reverse_uri,
                        ]);
                        if (!is_null($predicate)) {
                            $graph->addResource(
                                $fedora_uri,
                                $predicate,
                                $reverse_uri
                            );
                            if (strtolower($operation) == 'replace') {
                                $graph->deleteResource(
                                    $fedora_uri,
                                    $predicate,
                                    $uri
                                );
                            }
                        }
                    }
                }
            }
        }
        if ($request->headers->has('Accept')) {
            $acceptable_content_types = $request->getAcceptableContentTypes();
            $format = null;
            foreach ($acceptable_content_types as $output_type) {
                if ($output_type == "*/*") {
                    $output_type = "text/turtle";
                }
                try {
                    $format = Format::getFormat($output_type)->getName();
                    break;
                } catch (Exception $e) {
                    // pass
                }
            }
            if (is_null($format)) {
                return new Response('Cannot graph convert to requested format', 500);
            }
        } else {
            $format = 'turtle';
            $output_type = 'text/turtle';
        }

        // Add in user configured prefixes/uris for rdf mapping.
        if (is_array($this->namespaces) && count($this->namespaces) > 0) {
            foreach ($this->namespaces as $prefix => $uri) {
                if (RdfNamespace::prefixOfUri($uri) == '') {
                    $this->log->debug("Adding $prefix -> $uri");
                    RdfNamespace::set($prefix, $uri);
                }
            }
        }

        $new_body = $graph->serialise($format);

        if ($format == 'jsonld') {
            // TODO: Not have to do this to remove the extraneous resources.
            $temp = array_filter(
                json_decode($new_body, true),
                function ($item) use ($fedora_uri) {
                    return $item['@id'] == $fedora_uri;
                }
            );
            $new_body = json_encode(array_values($temp));
        }
        $headers = [
            'Content-type' => $output_type,
            'Content-length' => strlen($new_body),
        ];

        return new Response($new_body, 200, $headers);
    }

    private function getFedoraUrl($drupal_url, $fcrepo_base_url, $token)
    {
        try {
            // Strip off any query params and force the json format.
            $exploded = explode('?', $drupal_url);
            $drupal_url = $exploded[0] . '?_format=json';

            $response = $this->http->get($drupal_url, ['Authorization' => $token]);
            $json_str = $response->getBody();
            $json = json_decode($json_str, true);
            $this->log->debug("GOT THIS JSON: $json_str");

            $is_media = isset($json['bundle']) &&
                !empty($json['bundle']) &&
                $json['bundle'][0]['target_type'] == 'media_type';

            if ($is_media) {
                $link_headers = $response->getHeader('Link');
                $describes = $this->describeUri($link_headers);
                $this->log->debug("DESCRIBES $describes");
                foreach ($json as $field => $value) {
                    $is_file = $field != "thumbnail" &&
                        !empty($json[$field]) &&
                        isset($json[$field][0]["url"]) &&
                        $json[$field][0]["url"] == $describes;

                    if ($is_file) {
                        $exploded = explode("_flysystem/fedora", $json[$field][0]["url"]);
                        $in_fedora = count($exploded) > 1;
                        if ($in_fedora) {
                            return rtrim($fcrepo_base_url, '/') . $exploded[1] . "/fcr:metadata";
                        } else {
                            $uuid = $json[$field][0]['target_uuid'];
                            return rtrim($fcrepo_base_url, '/') .
                                "/{$this->entityMapper->getFedoraPath($uuid)}/fcr:metadata";
                        }
                    }
                }
            } else {
                $uuid = $json['uuid'][0]['value'];
                return rtrim($fcrepo_base_url, '/') . '/' . $this->entityMapper->getFedoraPath($uuid);
            }
        } catch (RequestException $e) {
            $this->log->warn($e->getMessage());
            return null;
        }
    }

    /**
     * Locate the predicate for an object in a graph.
     *
     * @param \EasyRdf\Graph $graph
     *   The graph to look in.
     * @param string $object
     *   The object to look for.
     *
     * @return string|null
     *   Return the predicate or null.
     */
    private function findPredicateForObject(Graph $graph, $object): ?string
    {
        $properties = $graph->reversePropertyUris($object);
        foreach ($properties as $p) {
            return $p;
        }
        return null;
    }

    /**
     * Return any found describes link headers or FALSE.
     *
     * @param array $link_header
     *   The array of Link headers.
     * @return false|string
     *   The URI described or false if not found.
     */
    private static function describeUri(array $link_header)
    {
        array_walk($link_header, ['self', 'parseLinkHeaders']);
        $match = array_search('describes', array_column($link_header, 'rel'));
        if (is_int($match)) {
            $match = $link_header[$match]['uri'];
        }
        return $match;
    }

    /**
     * Parse an array of string link headers in to associative arrays.
     *
     * Format is [
     *   'uri' => 'the uri',
     *   'rel' => 'the rel parameter',
     * ]
     *
     * @param $o
     *   The input array of link headers.
     */
    private static function parseLinkHeaders(&$o)
    {
        $part = trim($o);
        if (preg_match("/<([^>]+)>;\s*rel=\"?(\w+)\"?/", $part, $match)) {
            $o = [
                'uri' => $match[1],
                'rel' => $match[2],
            ];
        }
    }
}
