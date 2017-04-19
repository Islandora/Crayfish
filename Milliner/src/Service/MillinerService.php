<?php

namespace Islandora\Milliner\Service;

use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\PathMapper\PathMapperInterface;
use Psr\Log\LoggerInterface;
use SebastianBergmann\GlobalState\RuntimeException;

/**
 * Class MillinerService
 * @package Islandora\Milliner\Service
 */
class MillinerService implements MillinerServiceInterface
{
    /**
     * @var \Islandora\Chullo\IFedoraApi
     */
    protected $fedora;

    /**
     * @var \Islandora\Crayfish\Commons\PathMapper\PathMapperInterface
     */
    protected $pathMapper;

    /**
     * @var
     */
    protected $logger;

    /**
     * MillinerService constructor.
     * @param \Islandora\Chullo\IFedoraApi $fedora
     * @param \Islandora\Crayfish\Commons\PathMapper\PathMapperInterface $pathMapper
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(
        IFedoraApi $fedora,
        PathMapperInterface $pathMapper,
        LoggerInterface $log
    ) {
        $this->fedora = $fedora;
        $this->pathMapper = $pathMapper;
        $this->log = $log;
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        $drupal_jsonld,
        $drupal_path,
        $token
    ) {
        $fedora_path = $this->pathMapper->getFedoraPath($drupal_path);
        if ($fedora_path !== null) {
            throw new \RuntimeException(
                "$drupal_path already exists in Fedora at $fedora_path",
                200
            );
        }

        $fedora_jsonld = $this->processJsonld(
            $drupal_jsonld,
            $drupal_path
        );

        $headers = [
          'Authorization' => $token,
          'Content-Type' => 'application/ld+json',
        ];

        $fedora_response = $this->fedora->createResource(
            '',
            $fedora_jsonld,
            $headers
        );

        $this->log->debug("Fedora POST Response: ", [
          'body' => $fedora_response->getBody(),
          'status' => $fedora_response->getStatusCode(),
          'headers' => $fedora_response->getHeaders()
        ]);

        return $fedora_response;
    }

    /**
     * {@inheritDoc}
     */
    public function update(
        $drupal_jsonld,
        $drupal_path,
        $token
    ) {
        $fedora_path = $this->pathMapper->getFedoraPath($drupal_path);
        if ($fedora_path === null) {
            throw new \RuntimeException(
                "$drupal_path has not been mapped to Fedora",
                404
            );
        }

        $head_response = $this->fedora->getResourceHeaders(
            $fedora_path,
            ['Authorization' => $token]
        );

        $etag = ltrim($head_response->getHeader('ETag')[0], "W/");

        $fedora_jsonld = $this->processJsonld(
            $drupal_jsonld,
            $drupal_path
        );

        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/ld+json',
            'If-Match' => $etag,
            'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
        ];

        $fedora_response = $this->fedora->saveResource(
            $fedora_path,
            $fedora_jsonld,
            $headers
        );

        $this->log->debug("Fedora PUT Response: ", [
            'body' => $fedora_response->getBody(),
            'status' => $fedora_response->getStatusCode(),
            'headers' => $fedora_response->getHeaders()
        ]);

        return $fedora_response;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(
        $drupal_path,
        $token
    ) {
        $fedora_path = $this->pathMapper->getFedoraPath($drupal_path);
        if ($fedora_path === null) {
            throw new \RuntimeException(
                "$drupal_path is not mapped to Fedora",
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

    /**
     * @param $drupal_jsonld
     * @param $drupal_path
     * @return string
     */
    protected function processJsonld($drupal_jsonld, $drupal_path)
    {
        // Get graph as array.
        $rdf = json_decode($drupal_jsonld, true);

        // Strip out everything other than the resource in question.
        $resource = array_filter(
            $rdf['@graph'],
            function (array $elem) use ($drupal_path) {
                return strpos($elem['@id'], $drupal_path) !== false;
            }
        );
        // Put in an empty string as a placeholder for fedora path.
        $resource[0]['@id'] = "";

        return json_encode($resource);
    }
}
