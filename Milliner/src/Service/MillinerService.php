<?php

namespace Islandora\Milliner\Service;

use GuzzleHttp\Client;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\IdMapper\IdMapperInterface;
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
     * @var \Islandora\Crayfish\Commons\IdMapper\IdMapperInterface
     */
    protected $idMapper;

    /**
     * @var \Islandora\Milliner\Service\UrlMinterInterface
     */
    protected $urlMinter;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * MillinerService constructor.
     * @param \Islandora\Chullo\IFedoraApi $fedora
     * @param \Islandora\Crayfish\Commons\IdMapper\IdMapperInterface
     * @param \Islandora\Milliner\Service\UrlMinterInterface
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(
        IFedoraApi $fedora,
        IdMapperInterface $id_mapper,
        UrlMinterInterface $url_minter,
        LoggerInterface $log
    ) {
        $this->fedora = $fedora;
        $this->idMapper = $id_mapper;
        $this->urlMinter = $url_minter;
        $this->log = $log;
    }

    /**
     * {@inheritDoc}
     */
    public function saveBinary(
        $stream,
        $mimetype,
        $drupal_url,
        $uuid,
        $token
    ) {
        $headers = [
            'Authorization' => $token,
            'Content-Type' => $mimetype,
        ];

        $fedora_url = $this->idMapper->getFedoraId($drupal_url);

        if ($fedora_url) {
            $head_response = $this->fedora->getResourceHeaders(
                $fedora_url,
                ['Authorization' => $token]
            );

            if ($head_response->getStatusCode() != 200) {
                return $head_response;
            }

            $headers['If-Match'] = $head_response->getEtag();
        }
        else {
            $fedora_url = $this->urlMinter->mint($uuid);
        }

        $fedora_response = $this->fedora->saveResource(
            $fedora_url,
            $stream,
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
    public function saveJsonld(
        $jsonld,
        $drupal_url,
        $uuid,
        $token
    ) {
        $jsonld = $this->processJsonld($jsonld, $drupal_url);

        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/ld+json',
            'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
        ];

        $fedora_url = $this->idMapper->getFedoraId($drupal_url);

        if ($fedora_url) {
            $head_response = $this->fedora->getResourceHeaders(
                $fedora_url,
                ['Authorization' => $token]
            );

            if ($head_response->getStatusCode() != 200) {
                return $head_response;
            }

            $headers['If-Match'] = ltrim($head_response->getEtag(), "W/");
        }
        else {
            $fedora_url = $this->urlMinter->mint($uuid);
        }

        $fedora_response = $this->fedora->saveResource(
            $fedora_url,
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

    /**
     * {@inheritDoc}
     */
    public function delete(
        $drupal_url,
        $token
    ) {
        $headers = [
            'Authorization' => $token,
        ];

        $fedora_url = $this->idMapper->getFedoraId($drupal_url);

        if ($fedora_url) {
            $fedora_response = $this->fedora->deleteResource(
                $fedora_url,
                $headers
            );

            $this->log->debug("Fedora DELETE Response: ", [
                'body' => $fedora_response->getBody(),
                'status' => $fedora_response->getStatusCode(),
                'headers' => $fedora_response->getHeaders()
            ]);

            return $fedora_response;
        }

        return null;
    }

    /**
     * @param $drupal_jsonld
     * @param $drupal_path
     * @return string
     */
    protected function processJsonld($drupal_jsonld, $drupal_url)
    {
        // Get graph as array.
        $rdf = json_decode($drupal_jsonld, true);

        // Strip out everything other than the resource in question.
        $resource = array_filter(
            $rdf['@graph'],
            function (array $elem) use ($drupal_url) {
                return $elem['@id'] == $drupal_url;
            }
        );

        // Put in an empty string as a placeholder for fedora path.
        $resource[0]['@id'] = "";

        return json_encode($resource);
    }

}
