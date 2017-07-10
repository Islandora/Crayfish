<?php

namespace Islandora\Milliner\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\UrlMapper\UrlMapperInterface;
use Psr\Log\LoggerInterface;

/**
 * Class MillinerService
 * @package Islandora\Milliner\Service */
class MillinerService implements MillinerServiceInterface
{
    /**
     * @var \Islandora\Chullo\IFedoraApi
     */
    protected $fedora;

    /**
     * @var \Islandora\Crayfish\Commons\UrlMapper\UrlMapperInterface
     */
    protected $urlMapper;

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
     * @param \Islandora\Crayfish\Commons\UrlMapper\UrlMapperInterface
     * @param \Islandora\Milliner\Service\UrlMinterInterface
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(
        IFedoraApi $fedora,
        UrlMapperInterface $id_mapper,
        UrlMinterInterface $url_minter,
        LoggerInterface $log
    ) {
        $this->fedora = $fedora;
        $this->urlMapper = $id_mapper;
        $this->urlMinter = $url_minter;
        $this->log = $log;
    }

    /**
     * {@inheritDoc}
     */
    public function saveRdf(
        $rdf,
        $rdf_url,
        $uuid,
        $token
    ) {
        $urls = $this->urlMapper->getUrls($uuid);

        if (!empty($urls) && isset($urls['fedora_rdf'])) {
            // GET the resource if it's already been mapped.
            $fedora_rdf_url = $urls['fedora_rdf'];
            $get_response = $this->fedora->getResource(
                $fedora_rdf_url,
                ['Authorization' => $token, 'Accept' => 'application/ld+json']
            );

            // Exit early if GET fails.
            if ($get_response->getStatusCode() != 200) {
                return $get_response;
            }

            // Compare modified dates from each copy, and ignore if Drupal rdf is stale.
            $drupal_rdf = $this->processJsonld($rdf, $rdf_url, $fedora_rdf_url);
            $fedora_rdf = json_decode($get_response->getBody(true), true);

            $predicate = "http://schema.org/dateModified";
            $drupal_modified = \DateTime::createFromFormat(
                \DateTime::W3C,
                $this->getFirstPredicateValue($drupal_rdf, $predicate)
            );
            $fedora_modified = \DateTime::createFromFormat( 
                \DateTime::W3C,
                $this->getFirstPredicateValue($fedora_rdf, $predicate)
            );
            
            if ($drupal_modified->getTimestamp() <= $fedora_modified->getTimestamp()) {
                $msg = "Ignoring save because drupal rdf is old." .
                    " Drupal modified date: " . $drupal_modified->format(\DateTime::W3C) . 
                    " Fedora modified date: " . $fedora_modified->format(\DateTime::W3C) . ".";
                throw new \RuntimeException(
                    $msg,
                    200
                );
            }

            // Get ETag from GET response.
            $etags = $get_response->getHeader("ETag");
            $headers['If-Match'] = ltrim(reset($etags), "W/");
        }
        else {
            // Mint a new url if it hasn't been mapped yet.
            $fedora_rdf_url = $this->urlMinter->mint($uuid);
            $drupal_rdf = $this->processJsonld($rdf, $rdf_url, $fedora_rdf_url);
        }

        // Save the resource in Fedora.
        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/ld+json',
            'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
        ];
        $fedora_response = $this->fedora->saveResource(
            $fedora_rdf_url,
            json_encode($drupal_rdf),
            $headers
        );

        $this->log->debug("Fedora PUT Response: ", [
            'body' => $fedora_response->getBody(),
            'status' => $fedora_response->getStatusCode(),
            'headers' => $fedora_response->getHeaders()
        ]);

        // Map the urls if successful.
        $status = $fedora_response->getStatusCode();
        if ($status == 201 || $status == 204) {
            $this->urlMapper->saveUrls($uuid, $rdf_url, $fedora_rdf_url);
        }

        return $fedora_response;
    }

    /**
     * @param $rdf
     * @return \DateTime
     */
    protected function getFirstPredicateValue(array $rdf, $predicate) {
        // Check to make sure date exists before extracting it.
        $malformed = empty($rdf) ||
            !isset($rdf[0][$predicate]) ||
            empty($rdf[0][$predicate]) ||
            !isset($rdf[0][$predicate][0]['@value']); 
        if ($malformed) {
            throw new \RuntimeException(
                "Cannot extract $predicate from rdf",
                "500"
            );
        }

        // Extract as W3C string and conver to DateTime.
        return $rdf[0][$predicate][0]['@value'];
    }

    /**
     * @param $jsonld
     * @param $drupal_path
     * @return array 
     */
    protected function processJsonld(array $jsonld, $drupal_url, $fedora_url)
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

        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteRdf(
        $url,
        $uuid,
        $token
    ) {
        $headers = [
            'Authorization' => $token,
        ];

        $urls = $this->urlMapper->getUrls($uuid);

        if (!empty($urls) && isset($urls['fedora_rdf'])) {
            $fedora_rdf_url = $urls['fedora_rdf'];
            $fedora_response = $this->fedora->deleteResource(
                $fedora_rdf_url,
                $headers
            );

            $this->log->debug("Fedora DELETE Response: ", [
                'body' => $fedora_response->getBody(),
                'status' => $fedora_response->getStatusCode(),
                'headers' => $fedora_response->getHeaders()
            ]);

            $this->urlMapper->deleteUrls($uuid);

            return $fedora_response;
        }

        $this->urlMapper->deleteUrls($uuid);
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function saveNonRdf(
        $rdf,
        $rdf_url,
        $stream,
        $mimetype,
        $nonrdf_url,
        $uuid,
        $token
    ) {
        $headers = [
            'Authorization' => $token,
            'Content-Type' => $mimetype,
        ];

        $urls = $this->urlMapper->getUrls($uuid);

        if (empty($urls)) {
            // Mint a new url if it hasn't been mapped yet.
            $fedora_nonrdf_url = $this->urlMinter->mint($uuid);

            // Save the nonrdf source in Fedora.
            $headers = [
                'Authorization' => $token,
                'Content-Type' => $mimetype,
            ];

            $fedora_response = $this->fedora->saveResource(
                $fedora_nonrdf_url,
                $stream,
                $headers
            );

            $this->log->debug("Fedora PUT Response: ", [
                'body' => $fedora_response->getBody(),
                'status' => $fedora_response->getStatusCode(),
                'headers' => $fedora_response->getHeaders()
            ]);

            $status = $fedora_response->getStatusCode();

            // Exit early on fail.
            if ($status != 201 && $status != 204) {
                return $fedora_response;
            }

            // Get the metadata url from link header.
            $fedora_rdf_url = $this->getLinkHeader($fedora_response, 'describedby');

            // Save the rdf source in Fedora.
            $headers = [
                'Authorization' => $token,
                'Content-Type' => 'application/ld+json',
                'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
            ];
            $rdf_fedora_response = $this->fedora->saveResource(
                $fedora_rdf_url,
                $this->processJsonld($rdf, $rdf_url, $fedora_rdf_url),
                $headers
            );

            $this->log->debug("Fedora PUT Response: ", [
                'body' => $rdf_fedora_response->getBody(),
                'status' => $rdf_fedora_response->getStatusCode(),
                'headers' => $rdf_fedora_response->getHeaders()
            ]);

            $status = $rdf_fedora_response->getStatusCode();

            // Exit early on fail.
            if ($status != 201 && $status != 204) {
                return $rdf_fedora_response;
            }

            // Save urls.
            $this->urlMapper->saveUrls($uuid, $rdf_url, $fedora_rdf_url, $nonrdf_url, $fedora_nonrdf_url);

            // Return nonrdf response.
            return $fedora_response;
        }
        else {
            // GET the RDF resource.
            $fedora_rdf_url = $urls['fedora_rdf'];
            $get_response = $this->fedora->getResource(
                $fedora_rdf_url,
                ['Authorization' => $token, 'Accept' => 'application/ld+json']
            );

            // Exit early if GET fails.
            if ($get_response->getStatusCode() != 200) {
                return $get_response;
            }

            // Compare modified dates from each copy, and ignore if Drupal rdf is stale.
            $drupal_rdf = $this->processJsonld($rdf, $rdf_url, $fedora_rdf_url);
            $fedora_rdf = json_decode($get_response->getBody(true), true);

            $predicate = "http://schema.org/dateModified";
            $drupal_modified = \DateTime::createFromFormat(
                \DateTime::W3C,
                $this->getFirstPredicateValue($drupal_rdf, $predicate)
            );
            $fedora_modified = \DateTime::createFromFormat( 
                \DateTime::W3C,
                $this->getFirstPredicateValue($fedora_rdf, $predicate)
            );
            
            if ($drupal_modified->getTimestamp() <= $fedora_modified->getTimestamp()) {
                $msg = "Ignoring save because drupal rdf is old." .
                    " Drupal modified date: " . $drupal_modified->format(\DateTime::W3C) . 
                    " Fedora modified date: " . $fedora_modified->format(\DateTime::W3C) . ".";
                throw new \RuntimeException(
                    $msg,
                    200
                );
            }

            // Save the rdf source in Fedora.
            $headers = [
                'Authorization' => $token,
                'Content-Type' => 'application/ld+json',
                'Prefer' => 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"',
            ];
            $rdf_fedora_response = $this->fedora->saveResource(
                $fedora_rdf_url,
                $this->processJsonld($rdf, $rdf_url, $fedora_rdf_url),
                $headers
            );

            $this->log->debug("Fedora PUT Response: ", [
                'body' => $rdf_fedora_response->getBody(),
                'status' => $rdf_fedora_response->getStatusCode(),
                'headers' => $rdf_fedora_response->getHeaders()
            ]);

            $status = $rdf_fedora_response->getStatusCode();

            // Exit early on fail.
            if ($status != 201 && $status != 204) {
                return $rdf_fedora_response;
            }

            // Check to see if the file has changed. 
        }
/*
        if (!empty($urls) && isset($urls['fedora_rdf']) && !empty($rdf) && !empty($rdf_url)) {
            $this->saveRdf(


            // Compare modified dates from each copy, and ignore if Drupal rdf is stale.
            $drupal_rdf = $this->processJsonld($rdf, $rdf_url, $fedora_rdf_url);
            $fedora_rdf = json_decode($get_response->getBody(true), true);

            $drupal_modified = $this->getModifiedDateTime($drupal_rdf);
            $fedora_modified = $this->getModifiedDateTime($fedora_rdf); 
            
            if ($drupal_modified->getTimestamp() <= $fedora_modified->getTimestamp()) {
                $msg = "Ignoring save because drupal rdf is old." .
                    " Drupal modified date: " . $drupal_modified->format(\DateTime::W3C) . 
                    " Fedora modified date: " . $fedora_modified->format(\DateTime::W3C) . ".";
                throw new \RuntimeException(
                    $msg,
                    200
                );
            }

            // Get ETag from GET response.
            $etags = $get_response->getHeader("ETag");
            $headers['If-Match'] = ltrim(reset($etags), "W/");
        }
        if (!empty($urls) && isset($urls['drupal_nonrdf']) && isset($urls['fedora_nonrdf'])) {
            $fedora_nonrdf_url = $urls['fedora_nonrdf'];

            $head_response = $this->fedora->getResourceHeaders(
                $fedora_nonrdf_url,
                ['Authorization' => $token]
            );

            if ($head_response->getStatusCode() != 200) {
                return $head_response;
            }

            $headers['If-Match'] = $head_response->getEtag();
        }
        else {
            $fedora_nonrdf_url = $this->urlMinter->mint($uuid);
        }

        $fedora_metadata_url = null;

        $fedora_response = $this->fedora->saveResource(
            $fedora_nonrdf_url,
            $stream,
            $headers
        );

        $this->log->debug("Fedora PUT Response: ", [
            'body' => $fedora_response->getBody(),
            'status' => $fedora_response->getStatusCode(),
            'headers' => $fedora_response->getHeaders()
        ]);

        $fedora_rdf_url = $this->getFedoraMetadataUrl($fedora_response);

        $status = $fedora_response->getStatusCode();
        if ($status == 201) {
            // Map IDs.
            $this->urlMapper->save(
                $uuid,
                $rdf_url,
                $fedora_rdf_url,
                $nonrdf_url,
                $fedora_nonrdf_url
            );
        }

        return $fedora_response;
*/
    }

    protected function getLinkHeader($response, $rel_name) {
        $parsed = Psr7\parse_header($response->getHeader("Link"));
        foreach ($parsed as $header) {
            if (isset($header['rel']) && $header['rel'] = $rel_name) {
                return trim($header[0], '<>');
            }
        }
        return null;
    }

    public function deleteNonRdf(
        $nonrdf_url,
        $rdf_url,
        $token
    ) {
// TODO: HAVE TO UPDATE ID MAPPER AND GEMINI TABLE TO HANDLE ASSOCIATION OF LDP-NR TO LDP-RS THERE.
        $headers = [
            'Authorization' => $token,
        ];

        $fedora_url = $this->urlMapper->getFedoraId($url);

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

            $this->urlMapper->deleteFromDrupalId($url, $fedora_url);

            return $fedora_response;
        }

        $this->urlMapper->deleteFromDrupalId($url);

        return null;
        $this->urlMapper->deleteFromDrupalId($jsonld_url);
    }
}
