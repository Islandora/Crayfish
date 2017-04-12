<?php

namespace Islandora\Milliner\Service;

use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\PathMapper\PathMapperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SebastianBergmann\GlobalState\RuntimeException;
use Symfony\Component\HttpFoundation\Request;

class MillinerService implements MillinerServiceInterface
{
    protected $fedora;

    protected $gemini;

    protected $logger;

    public function __construct(
        IFedoraApi $fedora,
        PathMapperInterface $gemini,
        LoggerInterface $log
    ) {
        $this->fedora = $fedora;
        $this->gemini = $gemini;
        $this->log = $log;
    }

    public function create(ResponseInterface $drupal_entity, Request $request)
    {
        $path = $request->get('path');
        $token = $request->headers->get("Authorization");

        $fedora_path = $this->gemini->getFedoraPath($path);
        if ($fedora_path !== null) {
            throw new RuntimeException(
                "$path already exists in Fedora at $fedora_path",
                200
            );
        }

        $jsonld = $this->processJsonld(
            (string)$drupal_entity->getBody(),
            $path
        );

        $headers = [
          'Authorization' => $token,
          'Content-Type' => 'application/ld+json',
        ];

        $fedora_response = $this->fedora->createResource(
            '',
            $jsonld,
            $headers
        );

        $this->log->debug("Fedora POST Response: ", [
          'body' => $fedora_response->getBody(),
          'status' => $fedora_response->getStatusCode(),
          'headers' => $fedora_response->getHeaders()
        ]);

        return $fedora_response;
    }

    public function update(ResponseInterface $drupal_entity, Request $request)
    {
        $path = $request->get('path');
        $token = $request->headers->get("Authorization");

        $fedora_path = $this->gemini->getFedoraPath($path);
        if ($fedora_path === null) {
            throw new RuntimeException(
                "$path has not been mapped to Fedora",
                404
            );
        }

        $head_response = $this->fedora->getResourceHeaders(
            $fedora_path,
            ['Authorization' => $token]
        );

        $etag = ltrim($head_response->getHeader('ETag')[0], "W/");

        $jsonld = $this->processJsonld(
            (string)$drupal_entity->getBody(),
            $path
        );

        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/ld+json',
            'If-Match' => $etag,
            'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
        ];

        $fedora_response = $this->fedora->saveResource(
            $fedora_path,
            $jsonld,
            $headers
        );

        $this->log->debug("Fedora PUT Response: ", [
            'body' => $fedora_response->getBody(),
            'status' => $fedora_response->getStatusCode(),
            'headers' => $fedora_response->getHeaders()
        ]);

        return $fedora_response;
    }

    public function delete($path, Request $request)
    {
        $token = $request->headers->get("Authorization");

        $fedora_path = $this->gemini->getFedoraPath($path);
        if ($fedora_path === null) {
            throw new RuntimeException(
                "$path has not been mapped to Fedora",
                404
            );
        }

        $headers = [
            'Authorization' => $token,
        ];

        $fedora_response = $this->fedora->deleteResource(
            $fedora_path,
            $headers
        );

        $this->log->debug("Fedora DELETE Response: ", [
            'body' => $fedora_response->getBody(),
            'status' => $fedora_response->getStatusCode(),
            'headers' => $fedora_response->getHeaders()
        ]);

        return $fedora_response;
    }

    protected function processJsonld($jsonld, $path)
    {
        // Get graph as array.
        $rdf = json_decode($jsonld, true);

        // Strip out everything other than the resource in question.
        $resource = array_filter(
            $rdf['@graph'],
            function (array $elem) use ($path) {
                return strpos($elem['@id'], $path) !== false;
            }
        );
        // Put in an empty string as a placeholder for fedora path.
        $resource[0]['@id'] = "";

        return json_encode($resource);
    }

}