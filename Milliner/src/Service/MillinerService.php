<?php

namespace Islandora\Milliner\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\IdMapper\IdMapperInterface;
use Psr\Log\LoggerInterface;

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
        $file_url,
        $jsonld_url,
        $uuid,
        $token
    ) {
        $headers = [
            'Authorization' => $token,
            'Content-Type' => $mimetype,
        ];

        $fedora_url = $this->idMapper->getFedoraId($drupal_url);
        $fedora_metadata_url = null;

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

        $status = $fedora_response->getStatusCode();
        if ($status == 201 || $status == 204) {
            $fedora_metadata_url = $this->getFedoraMetadataUrl($fedora_response);

            // Map IDs.
            $this->idMapper->saveFromDrupalId($file_url, $fedora_url);
            $this->idMapper->saveFromDrupalId($jsonld_url, $fedora_metadata_url);
        }

        return $fedora_response;
    }

    protected getFedoraMetadataUrl($response) {
        $parsed = Psr7\parse_header($response->getHeader("Link"));
        foreach ($parsed as $header) {
            if (isset($header['rel']) && $header['rel'] = 'describedby') {
                return trim($header[0], '<>');
            }
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function saveJsonld(
        $jsonld,
        $url,
        $uuid,
        $token
    ) {
        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/ld+json',
            'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
        ];

        $fedora_url = $this->idMapper->getFedoraId($url);

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

        $jsonld = $this->processJsonld($jsonld, $url, $fedora_url);

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

        $status = $fedora_response->getStatusCode();
        if ($status == 201 || $status == 204) {
            $this->idMapper->saveFromDrupalId($url, $fedora_url);
        }

        return $fedora_response;
    }

    /**
     * @param $jsonld
     * @param $drupal_path
     * @return string
     */
    protected function processJsonld($jsonld, $drupal_url, $fedora_url)
    {
        // Strip out everything other than the resource in question.
        $resource = array_filter(
            $jsonld['@graph'],
            function (array $elem) use ($drupal_url) {
                return $elem['@id'] == $drupal_url;
            }
        );

        // Put in an fedora url for the resource.
        $resource[0]['@id'] = $fedora_url;

        return json_encode($resource);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(
        $url,
        $token
    ) {
        $headers = [
            'Authorization' => $token,
        ];

        $fedora_url = $this->idMapper->getFedoraId($url);

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

            $this->idMapper->deleteFromDrupalId($url, $fedora_url);

            return $fedora_response;
        }

        $this->idMapper->deleteFromDrupalId($url);

        return null;
    }

    public function deleteBinary(
        $file_url,
        $jsonld_url,
        $token
    ) {
// TODO: HAVE TO UPDATE ID MAPPER AND GEMINI TABLE TO HANDLE ASSOCIATION OF LDP-NR TO LDP-RS THERE.
        $headers = [
            'Authorization' => $token,
        ];

        $fedora_url = $this->idMapper->getFedoraId($url);

        if ($fedora_url) {
            $head_response = $this->fedora->getResourceHeaders(
                $fedora_url,
                ['Authorization' => $token]
            );

            $fedora_metadata_url = $this->getFedoraMetadataUrl($head_response);

            $fedora_response = $this->fedora->deleteResource(
                $fedora_url,
                $headers
            );

            $this->log->debug("Fedora DELETE Response: ", [
                'body' => $fedora_response->getBody(),
                'status' => $fedora_response->getStatusCode(),
                'headers' => $fedora_response->getHeaders()
            ]);

            $this->idMapper->deleteFromDrupalId($url, $fedora_url);

            return $fedora_response;
        }

        $this->idMapper->deleteFromDrupalId($url);

        return null;
        $this->idMapper->deleteFromDrupalId($jsonld_url);
    }
}
