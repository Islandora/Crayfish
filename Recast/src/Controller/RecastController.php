<?php

namespace Islandora\Recast\Controller;

use Islandora\Crayfish\Commons\Client\GeminiClient;
use Psr\Log\LoggerInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RecastController
{

  /**
   * @var \Monolog\Logger
   */
    private $log;

  /**
   * @var \Islandora\Crayfish\Commons\Client\GeminiClient
   */
    private $geminiClient;

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
   * @param \Islandora\Crayfish\Commons\Client\GeminiClient $geminiClient
   * @param \Psr\Log\LoggerInterface $log
   */
    public function __construct(
        GeminiClient $geminiClient,
        LoggerInterface $log
    ) {
        $this->geminiClient = $geminiClient;
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
        $body = (string) $fedora_resource->getBody();
        $mimeType = $fedora_resource->getHeader('Content-type');
        if (is_array($mimeType)) {
            $mimeType = reset($mimeType);
        }
        if (preg_match("/^([^;]+);/", $mimeType, $matches)) {
            $mimeType = $matches[1];
        }
        try {
            $format = \EasyRdf_Format::getFormat($mimeType)->getName();
        } catch (\EasyRdf_Exception $e) {
            $this->log->info("Could not parse format {$mimeType}");
            return new Response("Cannot process resource in format ({$mimeType})", 400);
        }

        try {
            $graph = new \EasyRdf_Graph();
            $graph->parse(
                $body,
                $format,
                $fedora_uri
            );
        } catch (\EasyRdf_Exception $e) {
            $this->log->error("Error parsing graph in {$format}");
            return new Response("Error parsing graph", 400);
        }

        $resources = $graph->resources();
        foreach ($resources as $uri => $data) {
            if (strpos($uri, $app['crayfish.drupal_base_url']) === 0) {
                $this->log->debug("Checking resource ", [
                'uri' => $uri,
                ]);
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
                    $format = \EasyRdf_Format::getFormat($output_type)->getName();
                    break;
                } catch (\EasyRdf_Exception $e) {
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
                    if (\EasyRdf_Namespace::prefixOfUri($uri) == '') {
                        $this->log->debug("Adding $prefix -> $uri");
                        \EasyRdf_Namespace::set($prefix, $uri);
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
   * @param \EasyRdf_Graph $graph
   *   The graph to look in.
   * @param string $object
   *   The object to look for.
   *
   * @return mixed string|null
   *   Return the predicate or null.
   */
    private function findPredicateForObject(\EasyRdf_Graph $graph, $object)
    {
        $properties = $graph->reversePropertyUris($object);
        foreach ($properties as $p) {
            return $p;
        }
        return null;
    }
}
