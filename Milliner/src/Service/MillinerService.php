<?php

namespace Islandora\Milliner\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\Client\GeminiClient;
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
     * @var \GuzzleHttp\Client
     */
    protected $drupal;

    /**
     * @var \Islandora\Milliner\Gemini\GeminiClient
     */
    protected $gemini;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * @var string
     */
    protected $modifiedDatePredicate;

    /**
     * MillinerService constructor.
     *
     * @param \Islandora\Chullo\IFedoraApi $fedora
     * @param \GuzzleHttp\Client
     * @param \Islandora\Milliner\Gemini\GeminiClient
     * @param string $modifiedDatePredicate
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(
        IFedoraApi $fedora,
        Client $drupal,
        GeminiClient $gemini,
        LoggerInterface $log,
        $modifiedDatePredicate
    ) {
        $this->fedora = $fedora;
        $this->drupal = $drupal;
        $this->gemini = $gemini;
        $this->log = $log;
        $this->modifiedDatePredicate = $modifiedDatePredicate;
    }

    /**
     * {@inheritDoc}
     */
    public function saveNode(
        $uuid,
        $jsonld_url,
        $token = null
    ) {
        $urls = $this->gemini->getUrls($uuid, $token);

        if (empty($urls)) {
            return $this->createNode(
                $uuid,
                $jsonld_url,
                $token
            );
        } else {
            return $this->updateNode(
                $uuid,
                $jsonld_url,
                $urls['fedora'],
                $token
            );
        }
    }

    /**
     * Creates a new LDP-RS in Fedora from a Node.
     *
     * @param string $uuid
     * @param string $jsonld_url
     * @param string $token
     *
     * @return \GuzzleHttp\Psr7\Response
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function createNode(
        $uuid,
        $jsonld_url,
        $token = null
    ) {
        // Mint a new Fedora URL.
        $fedora_url = $this->gemini->mintFedoraUrl($uuid, $token);

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

        // Mash it into the shape Fedora accepts.
        $jsonld = $this->processJsonld(
            $jsonld,
            $jsonld_url,
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

        // Map the URLS.
        $this->gemini->saveUrls(
            $uuid,
            $jsonld_url,
            $fedora_url,
            $token
        );

        // Return the response from Fedora.
        return $response;
    }

    /**
     * Updates an existing LDP-RS in Fedora from a Node.
     *
     * @param string $uuid
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
        $uuid,
        $jsonld_url,
        $fedora_url,
        $token = null
    ) {
        // Get the jsonld from Fedora.
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

        $fedora_modified = $this->getModifiedTimestamp(
            $fedora_jsonld
        );

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
        $drupal_jsonld = $this->processJsonld(
            $drupal_jsonld,
            $jsonld_url,
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

        // Conditional save it in Fedora.
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
        $source_field,
        $json_url,
        $token = null
    ) {
        // First get the media json from Drupal.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $drupal_response = $this->drupal->get(
            $json_url,
            ['headers' => $headers]
        );

        $jsonld_url = $this->getLinkHeader($drupal_response, "alternate", "application/ld+json");
        $media_json = json_decode(
            $drupal_response->getBody(),
            true
        );

        if (!isset($media_json[$source_field]) || empty($media_json[$source_field])) {
            throw new \RuntimeException(
                "Cannot parse file UUID from $json_url.  Ensure $source_field exists on the media and is populated.",
                500
            );
        }
        $file_uuid = $media_json[$source_field][0]['target_uuid'];

        // Get the file's LDP-NR counterpart in Fedora.
        $urls = $this->gemini->getUrls($file_uuid, $token);
        if (empty($urls)) {
            $file_url = $media_json[$source_field][0]['url'];
            throw new \RuntimeException(
                "$file_url has not been mapped in Gemini with uuid $file_uuid",
                404
            );
        }
        $fedora_file_url = $urls['fedora'];

        // Now look for the 'describedby' link header on the file in Fedora.
        $fedora_response = $this->fedora->getResourceHeaders(
            $fedora_file_url,
            $headers
        );
        $status = $fedora_response->getStatusCode();
        if ($status != 200) {
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
        // Be sure to give it the URL of the file being described, not that of
        // the RDF itself.
        $drupal_jsonld = $this->processJsonld(
            $drupal_jsonld,
            $jsonld_url,
            $fedora_file_url
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
        // Conditional save it in Fedora.
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
        $urls = $this->gemini->getUrls($uuid, $token);

        if (!empty($urls)) {
            $fedora_url = $urls['fedora'];
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
        }

        $gemini_success = $this->gemini->deleteUrls($uuid, $token);

        if ($gemini_success) {
            return new Response(204);
        }

        return new Response(isset($status) ? $status : 404);
    }
}
