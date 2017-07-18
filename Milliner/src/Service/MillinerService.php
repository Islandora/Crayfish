<?php

namespace Islandora\Milliner\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Milliner\Client\GeminiClient;
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
     * @var \Islandora\Milliner\Client\GeminiClient
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
     * @param \Islandora\Milliner\Client\GeminiClient
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
    public function saveContent(
        $uuid,
        $jsonld_url,
        $token = null
    ) {
        $urls = $this->gemini->getUrls($uuid, $token);

        if (empty($urls)) {
            return $this->createContent(
                $uuid,
                $jsonld_url,
                $token
            );
        } else {
            return $this->updateContent(
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
    protected function createContent(
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
    protected function updateContent(
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
                "Client error: `GET $fedora_url` resulted in a `$status $reason` response: " . $fedora_response->getBody(),
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
    protected function getFirstPredicate(array $jsonld, $predicate, $value = true) {
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
        $uuid,
        $json_url,
        $jsonld_url,
        $token = null
    ) {
        // Back your way into the media url in Fedora by looking up the file first.
        $fedora_url = $this->getFedoraMediaUrl($json_url, $token);

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
                "Client error: `GET $fedora_url` resulted in a `$status $reason` response: " . $fedora_response->getBody(),
                $status
            );
        }

        // Get the URL for the LDP-NR the media describes.
        $describes_url = $this->getLinkHeader($fedora_response, "describes");

        if (empty($describes_url)) {
            throw new \RuntimeException(
                "Cannot parse 'describes' link header from response to `HEAD $fedora_url`",
                500
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
            $describes_url
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
     * Backs its way into the Fedora URL for a Media entity by getting the Media
     * JSON from Drupal, getting the File UUID, looking that up in Gemini, and
     * then issuing HEAD request for the 'describedby' Link header.
     *
     * @param $json_url
     * @param $token
     *
     * @return string
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function getFedoraMediaUrl(
        $json_url,
        $token = null
    ) {
        // First get the media json from Drupal.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $drupal_response = $this->drupal->get(
            $json_url,
            ['headers' => $headers]
        );

        $media_json = json_decode(
            $drupal_response->getBody(),
            true
        );

        // Extract the file uuid.  It can be under 'field_file' or 'field_image'.
        if (!isset($media_json['field_image']) && !isset($media_json['field_file'])) {
            throw new \RuntimeException(
                "Cannot parse file UUID from $json_url.  Media must use 'field_file' or 'field_image'.",
                500
            );
        }

        $field_name = isset($media_json['field_image']) ? 'field_image' : 'field_file';

        if (empty($media_json[$field_name])) {
            throw new \RuntimeException(
                "Cannot parse file UUID from $json_url.  'field_file' or 'field_image' is empty.",
                500
            );
        }

        $file_uuid = $media_json[$field_name][0]['target_uuid'];

        // Get the file's LDP-NR counterpart in Fedora.
        $urls = $this->gemini->getUrls($file_uuid, $token);

        if (empty($urls)) {
            $file_url = $media_json[$field_name][0]['url'];
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
                "Client error: `HEAD $fedora_file_url` resulted in a `$status $reason` response: " . $fedora_response->getBody(),
                $status
            );
        }

        $described_by = $this->getLinkHeader($fedora_response, "describedby");

        if (empty($described_by)) {
            throw new \RuntimeException(
                "Cannot parse 'describedby' link header from response to `HEAD $fedora_file_url`",
                500
            );
        }

        return $described_by;
    }

    /**
     * {@inheritDoc}
     */
    public function saveFile(
        $uuid,
        $file_url,
        $checksum_url,
        $token = null
    ) {
        $urls = $this->gemini->getUrls($uuid, $token);

        if (empty($urls)) {
            return $this->createFile(
                $uuid,
                $file_url,
                $token
            );
        } else {
            return $this->updateFile(
                $uuid,
                $file_url,
                $checksum_url,
                $urls['fedora'],
                $token
            );
        }
    }

    /**
     * Creates a new LDP-NR in Fedora from a Drupal file.
     *
     * @param $uuid
     * @param $file_url
     * @param $token
     *
     * @return \GuzzleHttp\Psr7\Response
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function createFile(
        $uuid,
        $file_url,
        $token = null
    ) {
        // Mint a new Fedora URL.
        $fedora_url = $this->gemini->mintFedoraUrl($uuid, $token);

        // Get the file from Drupal.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $drupal_response = $this->drupal->get(
            $file_url,
            ['headers' => $headers]
        );

        // Save it in Fedora.
        $headers['Content-Type'] = reset($drupal_response->getHeader('Content-Type'));
        $response = $this->fedora->saveResource(
            $fedora_url,
            $drupal_response->getBody(),
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
            $file_url,
            $fedora_url,
            $token
        );

        // Return the response from Fedora.
        return $response;
    }

    /**
     * Updates an existing LDP-NR in Fedora from a Drupal file.
     *
     * @param $uuid
     * @param $file_url
     * @param $checksum_url
     * @param $fedora_url
     * @param $token
     *
     * @return \GuzzleHttp\Psr7\Response
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function updateFile(
        $uuid,
        $file_url,
        $checksum_url,
        $fedora_url,
        $token = null
    ) {
        // Get the headers for the file from Fedora.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $fedora_response = $this->fedora->getResourceHeaders(
            $fedora_url,
            $headers
        );

        $status = $fedora_response->getStatusCode();
        if ($status != 200) {
            $reason = $fedora_response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `HEAD $fedora_url` resulted in a `$status $reason` response: " . $fedora_response->getBody(),
                $status
            );
        }

        // Get the ETag.
        $etags = $fedora_response->getHeader("ETag");
        $etag = reset($etags);

        // Get the 'describedby' link.
        $described_by = $this->getLinkHeader($fedora_response, "describedby");
        if (empty($described_by)) {
            throw new \RuntimeException(
                "Cannot parse 'describedby' link header from response to `HEAD $fedora_url`",
                500
            );
        }

        // Get the RDF describing the file from Fedora.
        $headers['Accept'] = 'application/ld+json';
        $fedora_response = $this->fedora->getResource(
            $described_by,
            $headers
        );

        $status = $fedora_response->getStatusCode();
        if ($status != 200) {
            $reason = $fedora_response->getReasonPhrase();
            throw new \RuntimeException(
                "Client error: `GET $described_by` resulted in a `$status $reason` response: " . $fedora_response->getBody(),
                $status
            );
        }

        $fedora_jsonld = json_decode(
            $fedora_response->getBody(),
            true
        );

        // Get the checksum from the RDF.
        $fedora_checksum = $this->parseChecksum($fedora_jsonld);

        // Get the checksum from Drupal.
        unset($headers['Accept']);
        $drupal_response = $this->drupal->get(
            $checksum_url,
            ['headers' => $headers]
        );

        $checksum_json = json_decode(
            $drupal_response->getBody(),
            true
        );

        $drupal_checksum = $checksum_json[0]['sha1'];

        // Abort with 412 if the files haven't changed.
        if ($fedora_checksum == $drupal_checksum) {
            throw new \RuntimeException(
                "Not updating $fedora_url because file at $file_url has not changed",
                412
            );
        }

        // Get the file from Drupal.
        $drupal_response = $this->drupal->get(
            $file_url,
            ['headers' => $headers]
        );

        // Save it in Fedora.
        $headers['Content-Type'] = reset($drupal_response->getHeader('Content-Type'));
        $headers['If-Match'] = $etag;
        $response = $this->fedora->saveResource(
            $fedora_url,
            $drupal_response->getBody(),
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
            $file_url,
            $fedora_url,
            $token
        );

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
    protected function getLinkHeader($response, $rel_name) {
        $parsed = Psr7\parse_header($response->getHeader("Link"));
        foreach ($parsed as $header) {
            if (isset($header['rel']) && $header['rel'] == $rel_name) {
                return trim($header[0], '<>');
            }
        }
        return null;
    }

    /**
     * Gets a checksum from Fedora jsonld.
     *
     * @param array $jsonld
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function parseChecksum(array $jsonld) {
        $predicate = 'http://www.loc.gov/premis/rdf/v1#hasMessageDigest';
        $urn = $this->getFirstPredicate($jsonld, $predicate, false);

        if (preg_match("/urn:sha1:(?<checksum>.*)/", $urn, $matches)) {
            if (isset($matches['checksum'])) {
                return $matches['checksum'];
            }
        }

        throw new \RuntimeException(
            "Could not parse $predicate from " . json_encode($jsonld),
            500
        );
    }

    /**
     * {@inheritDoc}
     */
    public function delete(
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
            if (!in_array($status, [204, 410])) {
                $reason = $response->getReasonPhrase();
                throw new \RuntimeException(
                    "Client error: `DELETE $fedora_url` resulted in a `$status $reason` response: " . $response->getBody(),
                    $status
                );
            }

            $this->gemini->deleteUrls($uuid, $token);
        }

        return new Response(204);
    }
}
