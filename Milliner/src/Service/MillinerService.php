<?php

namespace Islandora\Milliner\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface;
use Psr\Log\LoggerInterface;
use \DateTime;

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
     * @var \GuzzleHttp\Client
     */
    protected $drupal;

    /**
     * @var \Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface
     */
    protected $mapper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * @var string
     */
    protected $modifiedDatePredicate;

    /**
     * @var string
     */
    protected $stripFormatJsonld;

    /**
     * MillinerService constructor.
     *
     * @param \Islandora\Chullo\IFedoraApi $fedora
     * @param \GuzzleHttp\Client
     * @param \Islandora\Crayfish\Commons\EntityMapper\EntityMapperInterface
     * @param \Psr\Log\LoggerInterface $log
     * @param string $modifiedDatePredicate
     * @param string $stripFormatJsonld
     */
    public function __construct(
        IFedoraApi $fedora,
        Client $drupal,
        EntityMapperInterface $mapper,
        LoggerInterface $log,
        $modifiedDatePredicate,
        $stripFormatJsonld
    ) {
        $this->fedora = $fedora;
        $this->drupal = $drupal;
        $this->mapper = $mapper;
        $this->log = $log;
        $this->modifiedDatePredicate = $modifiedDatePredicate;
        $this->stripFormatJsonld = $stripFormatJsonld;
    }

    /**
     * {@inheritDoc}
     */
    public function saveNode(
        $uuid,
        $jsonld_url,
        $islandora_fedora_endpoint,
        $token = null
    ) {
        $path = $this->mapper->getFedoraPath($uuid);
	$islandora_fedora_endpoint = rtrim("/", $islandora_fedora_endpoint);
	$fedora_url  = "$islandora_fedora_endpoint/$path";

	$response = $this->fedora->getResourceHeaders($fedora_url);
        if ($response->getStatusCode() == "404") {
		$this->log->debug("GOT A 404");
            return $this->createNode(
                $jsonld_url,
                $fedora_url,
                $token
            );
        } else {
		$this->log->debug("DID NOT GET 404");
            return $this->updateNode(
                $jsonld_url,
                $fedora_url,
                $token
            );
        }
    }

    /**
     * Creates a new LDP-RS in Fedora from a Node.
     *
     * @param string $jsonld_url
     * @param string $fedora_url
     * @param string $token
     *
     * @return \GuzzleHttp\Psr7\Response
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function createNode(
        $jsonld_url,
        $fedora_url,
        $token = null
    ) {
        // Get the jsonld from Drupal.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $drupal_response = $this->drupal->get(
            $jsonld_url,
            ['headers' => $headers]
        );

        $jsonld = json_decode(
            $drupal_response->getBody(),
            true
        );

        $subject_url = $this->stripFormatJsonld ? rtrim($jsonld_url, '?_format=jsonld') : $jsonld_url;

        // Mash it into the shape Fedora accepts.
        $jsonld = $this->processJsonld(
            $jsonld,
            $subject_url,
            $fedora_url
        );

        // Save it in Fedora.
        $headers['Content-Type'] = 'application/ld+json';
        $headers['Prefer'] = 'return=minimal; handling=lenient';
        $response = $this->fedora->saveResource(
            $fedora_url,
            json_encode($jsonld),
            $headers
        );

        $status = $response->getStatusCode();
        if (!in_array($status, [201, 204])) {
            $reason = $response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `PUT $fedora_url` resulted in a `$status $reason` response: " . $response->getBody(),
                $status
            );
        }

        // Return the response from Fedora.
        return $response;
    }

    /**
     * Updates an existing LDP-RS in Fedora from a Node.
     *
     * @param string $jsonld_url
     * @param string $fedora_url
     * @param string $token
     *
     * @return \GuzzleHttp\Psr7\Response
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function updateNode(
        $jsonld_url,
        $fedora_url,
        $token = null
    ) {

        // Get the RDF from Fedora.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $headers['Accept'] = 'application/ld+json';
        $fedora_response = $this->fedora->getResource(
            $fedora_url,
            $headers
        );

        $status = $fedora_response->getStatusCode();
        if ($status != 200) {
            $reason = $fedora_response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `GET $fedora_url` resulted in a `$status $reason` response: " .
                    $fedora_response->getBody(),
                $status
            );
        }

        // Strip off the W/ prefix to make the ETag strong.
        $etags = $fedora_response->getHeader("ETag");
        $etag = ltrim(reset($etags), "W/");

        // Get the modified date from the RDF.
        $fedora_jsonld = json_decode(
            $fedora_response->getBody(),
            true
        );

        // Account for the fact that new media haven't got a modified date
        // pushed to it from Drupal yet.
        try {
            $fedora_modified = $this->getModifiedTimestamp(
                $fedora_jsonld
            );
        } catch (\RuntimeException $e) {
            $fedora_modified = 0;
        }

        // Get the jsonld from Drupal.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $drupal_response = $this->drupal->get(
            $jsonld_url,
            ['headers' => $headers]
        );
        $drupal_jsonld = json_decode(
            $drupal_response->getBody(),
            true
        );

        // Mash it into the shape Fedora accepts.
        $subject_url = $this->stripFormatJsonld ? rtrim($jsonld_url, '?_format=jsonld') : $jsonld_url;
        $drupal_jsonld = $this->processJsonld(
            $drupal_jsonld,
            $subject_url,
            $fedora_url
        );

        // Get the modified date from the RDF.
        $drupal_modified = $this->getModifiedTimestamp(
            $drupal_jsonld
        );

        // Abort with 412 if the Drupal RDF is stale.
        if ($drupal_modified <= $fedora_modified) {
            throw new \RuntimeException(
                "Not updating $fedora_url because RDF at $jsonld_url is not newer",
                412
            );
        }

        // Conditionally save it in Fedora.
        $headers['Content-Type'] = 'application/ld+json';
        $headers['Prefer'] = 'return=minimal; handling=lenient';
        $headers['If-Match'] = $etag;
        $response = $this->fedora->saveResource(
            $fedora_url,
            json_encode($drupal_jsonld),
            $headers
        );

        $status = $response->getStatusCode();
        if (!in_array($status, [201, 204])) {
            $reason = $response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `PUT $fedora_url` resulted in a `$status $reason` response: " . $response->getBody(),
                $status
            );
        }

        // Return the response from Fedora.
        return $response;
    }

    /**
     * Normalizes Drupal jsonld into a shape Fedora understands.
     *
     * @param array $jsonld
     * @param string $drupal_url
     * @param string $fedora_url
     *
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
     * Gets the first value for a predicate in a JSONLD array.
     *
     * @param $jsonld
     * @param $predicate
     * @param $value
     *
     * @return mixed string|null
     */
    protected function getFirstPredicate(array $jsonld, $predicate, $value = true)
    {
        $key = $value ? '@value' : '@id';
        $malformed = empty($jsonld) ||
            !isset($jsonld[0][$predicate]) ||
            empty($jsonld[0][$predicate]) ||
            !isset($jsonld[0][$predicate][0][$key]);

        if ($malformed) {
            return null;
        }

        return $jsonld[0][$predicate][0][$key];
    }

    /**
     * Extracts a modified date from jsonld and returns it as a timestamp.
     *
     * @param array $jsonld
     *
     * @return int
     *
     * @throws \RuntimeException
     */
    protected function getModifiedTimestamp(array $jsonld)
    {
        $modified = $this->getFirstPredicate(
            $jsonld,
            $this->modifiedDatePredicate
        );

        if (empty($modified)) {
            throw new \RuntimeException(
                "Could not parse {$this->modifiedDatePredicate} from " . json_encode($jsonld),
                500
            );
        }

        $date = \DateTime::createFromFormat(
            \DateTime::W3C,
            $modified
        );

        return $date->getTimestamp();
    }

    /**
     * {@inheritDoc}
     */
    public function saveMedia(
        $json_url,
        $islandora_fedora_endpoint,
        $token = null
    ) {
        $urls = $this->getMediaUrls($json_url, $token);
        return $this->updateNode(
            $urls['jsonld'],
            $urls['fedora'],
            $token
        );
    }

    /**
     * Gets a Link header with the supplied rel name.
     *
     * @param $response
     * @param $rel_name
     *
     * @return null|string
     */
    protected function getLinkHeader($response, $rel_name, $type = null)
    {
        $parsed = Psr7\parse_header($response->getHeader("Link"));
        foreach ($parsed as $header) {
            $has_relation = isset($header['rel']) && $header['rel'] == $rel_name;
            $has_type = $type ? isset($header['type']) && $header['type'] == $type : true;
            if ($has_type && $has_relation) {
                return trim($header[0], '<>');
            }
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteNode(
        $uuid,
        $token = null
    ) {
        $path = $this->mapper->getFedoraPath($uuid);
	$islandora_fedora_endpoint = rtrim("/", $islandora_fedora_endpoint);
	$fedora_url  = "$islandora_fedora_endpoint/$path";

        $headers = empty($token) ? [] : ['Authorization' => $token];
        $response = $this->fedora->deleteResource(
            $fedora_url,
            $headers
        );

        $status = $response->getStatusCode();
        if (!in_array($status, [204, 410, 404])) {
            $reason = $response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `DELETE $fedora_url` resulted in a `$status $reason` response: " .
                    $response->getBody(),
                $status
            );
        }

        return new Response($status);
    }

    /**
     * {@inheritDoc}
     */
    public function saveExternal(
        $uuid,
        $external_url,
        $islandora_fedora_endpoint,
        $token = null
    ) {
        $path = $this->mapper->getFedoraPath($uuid);
	$islandora_fedora_endpoint = rtrim("/", $islandora_fedora_endpoint);
	$fedora_url  = "$islandora_fedora_endpoint/$path";

        $headers = empty($token) ? [] : ['Authorization' => $token];
        $mimetype = $this->drupal->head(
            $external_url,
            ['headers' => $headers]
        )->getHeader('Content-Type')[0];

        // Save it in Fedora as external content.
        $external_rel = "http://fedora.info/definitions/fcrepo#ExternalContent";
        $link = '<' . $external_url . '>; rel="' . $external_rel . '"; handling="redirect"; type="' . $mimetype . '"';
        $headers['Link'] = $link;
        $response = $this->fedora->saveResource(
            $fedora_url,
            null,
            $headers
        );

        $status = $response->getStatusCode();
        if (!in_array($status, [201, 204])) {
            $reason = $response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `PUT $fedora_url` resulted in a `$status $reason` response: " . $response->getBody(),
                $status
            );
        }

        // Return the response from Fedora.
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function createVersion(
        $uuid,
	$islandora_fedora_endpoint,
        $token = null
    ) {
        $path = $this->mapper->getFedoraPath($uuid);
	$islandora_fedora_endpoint = rtrim("/", $islandora_fedora_endpoint);
	$fedora_url  = "$islandora_fedora_endpoint/$path";

        $headers = empty($token) ? [] : ['Authorization' => $token];
        $date = new DateTime();
        $timestamp = $date->format("D, d M Y H:i:s O");
        // create version in Fedora.
        try {
            $response = $this->fedora->createVersion(
                $fedora_url,
                $timestamp,
                null,
                $headers
            );
            $status = $response->getStatusCode();
            if (!in_array($status, [201])) {
                $reason = $response->getReasonPhrase();
                throw new \RuntimeException(
                    "Client error: `POST $fedora_url` resulted in `$status $reason` response: " .
                    $response->getBody(),
                    $status
                );
            }
            // Return the response from Fedora.
            return $response;
        } catch (Exception $e) {
            $this->log->error('Caught exception when creating version: ', $e->getMessage(), "\n");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createMediaVersion(
        $json_url,
	$islandora_fedora_endpoint,
        $token = null
    ) {
        $urls = $this->getMediaUrls($json_url, $token);
	$fedora_url = $urls['fedora'];

        $date = new DateTime();
        $timestamp = $date->format("D, d M Y H:i:s O");
        // create version in Fedora.
        try {
            $response = $this->fedora->createVersion(
                $fedora_url,
                $timestamp,
                null,
                $headers
            );
            $status = $response->getStatusCode();
            if (!in_array($status, [201])) {
                $reason = $response->getReasonPhrase();
                throw new \RuntimeException(
                    "Client error: `POST $fedora_url` resulted in `$status $reason` response: " .
                    $response->getBody(),
                    $status
                );
            }
            // Return the response from Fedora.
            return $response;
        } catch (Exception $e) {
            $this->log->error('Caught exception when creating version: ', $e->getMessage(), "\n");
        }
    }

    protected function getMediaUrls($json_url, $token = null) {
        // HEAD drupal to get urls.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $drupal_response = $this->drupal->head(
            $json_url,
            ['headers' => $headers]
        );

        $jsonld_url = $this->getLinkHeader($drupal_response, "alternate", "application/ld+json");
        if (empty($jsonld_url)) {
            throw new \RuntimeException(
                "Cannot parse 'alternate' link header from response to `HEAD $json_url`",
                500
            );
        }

        $drupal_url = $this->getLinkHeader($drupal_response, "describes");
        if (empty($drupal_url)) {
            throw new \RuntimeException(
                "Cannot parse 'describes' link header from response to `HEAD $json_url`",
                500
            );
        }

	// Construct the fedora url
	$pieces = explode("_flysystem/", $drupal_url);
	$fedora_file_path = end($pieces); 
	$islandora_fedora_endpoint = rtrim("/", $islandora_fedora_endpoint);
        $fedora_file_url = "$islandora_fedora_endpoint/$fedora_file_path" ;

        // Now look for the 'describedby' link header on the file in Fedora.
        // I'm using the drupal http client because I have the full
        // URI and need to squash redirects in case of external content.
        $fedora_response = $this->drupal->head(
            $fedora_file_url,
            ['allow_redirects' => false, 'headers' => $headers]
        );
        $status = $fedora_response->getStatusCode();

        if ($status != 200 && $status != 307) {
            $reason = $fedora_response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `HEAD $fedora_file_url` resulted in a `$status $reason` response: " .
                    $fedora_response->getBody(),
                $status
            );
        }

        $fedora_url = $this->getLinkHeader($fedora_response, "describedby");
        if (empty($fedora_url)) {
            throw new \RuntimeException(
                "Cannot parse 'describedby' link header from response to `HEAD $fedora_file_url`",
                500
            );
        }

	return ['drupal' => $drupal_url, 'fedora' => $fedora_url, 'jsonld' => $jsonld_url];
    }
}
