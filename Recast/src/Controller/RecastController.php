<?php

namespace Islandora\Recast\Controller;

use Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface;
use Psr\Log\LoggerInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// phpcs:disable
if (class_exists('\EasyRdf_Graph')) {
    class_alias('\EasyRdf_Graph', ' \EasyRdf\Graph');
}

if (class_exists('\EasyRdf_Format')) {
    class_alias('\EasyRdf_Format', ' \EasyRdf\Format');
}

if (class_exists('\EasyRdf_Exception')) {
    class_alias('\EasyRdf_Exception', ' \EasyRdf\Exception');
}

if (class_exists('\EasyRdf_Namespace')) {
    class_alias('\EasyRdf_Namespace', ' \EasyRdf\RdfNamespace');
}
// phpcs:enable

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
   * @var array
   */
    protected $availableMethods = [
    'add',
    'replace',
    ];

  /**
   * RecastController constructor.
   *
   * @param \Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface $entityMapper
   * @param \Psr\Log\LoggerInterface $log
   */
    public function __construct(
        EntityMapperInterface $entityMapper,
        LoggerInterface $log
    ) {
        $this->entityMapper = $entityMapper;
        $this->log = $log;
    }

  /**
   * Send API-X Options information.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The turtle file of the options response.
   */
    public function recastOptions()
    {
        return new BinaryFileResponse(
            __DIR__ . "/../../static/recast.ttl",
            200,
            ['Content-Type' => 'text/turtle']
        );
    }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   * @param \Silex\Application $app
   *   The Silex application
   * @param string $operation
   *   Whether to add or
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
    public function recast(Request $request, Application $app, $operation)
    {
        if (!in_array($operation, $this->availableMethods)) {
            return new Response(sprintf(
                'Valid methods for recast are [%s] received "%s".',
                implode(', ', $this->availableMethods),
                $operation
            ), 400);
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

        $body = (string) $fedora_resource->getBody();
        $mimeType = $fedora_resource->getHeader('Content-type');
        if (is_array($mimeType)) {
            $mimeType = reset($mimeType);
        }
        if (preg_match("/^([^;]+);/", $mimeType, $matches)) {
            $mimeType = $matches[1];
        }
        try {
            $format = \EasyRdf\Format::getFormat($mimeType)->getName();
        } catch (\EasyRdf\Exception $e) {
            $this->log->info("Could not parse format {$mimeType}");
            return new Response("Cannot process resource in format ({$mimeType})", 400);
        }

        try {
            $graph = new \EasyRdf\Graph();
            $graph->parse(
                $body,
                $format,
                $fedora_uri
            );
        } catch (\EasyRdf\Exception $e) {
            $this->log->error("Error parsing graph in {$format}");
            return new Response("Error parsing graph", 400);
        }

        $resources = $graph->resources();
        foreach ($resources as $uri => $data) {
            if (strpos($uri, $app['crayfish.drupal_base_url']) === 0) {
                $this->log->debug("Checking resource ", [
                'uri' => $uri,
                ]);
                /*
                $reverse_uri = $this->geminiClient->findByUri($uri, $token);
                if (!is_null($reverse_uri)) {
                    if (is_array($reverse_uri)) {
                        $reverse_uri = reset($reverse_uri);
                    }
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
                */
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
                    $format = \EasyRdf\Format::getFormat($output_type)->getName();
                    break;
                } catch (\EasyRdf\Exception $e) {
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
        if (isset($app['crayfish.namespaces'])) {
            // To get the prefixes we nest the associative array inside an array
            $namespaces = $app['crayfish.namespaces'][0];
            if (is_array($namespaces) && count($namespaces) > 0) {
                foreach ($namespaces as $prefix => $uri) {
                    if (\EasyRdf\RdfNamespace::prefixOfUri($uri) == '') {
                        $this->log->debug("Adding $prefix -> $uri");
                        \EasyRdf\RdfNamespace::set($prefix, $uri);
                    }
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

  /**
   * Locate the predicate for an object in a graph.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to look in.
   * @param string $object
   *   The object to look for.
   *
   * @return mixed string|null
   *   Return the predicate or null.
   */
    private function findPredicateForObject(\EasyRdf\Graph $graph, $object)
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
