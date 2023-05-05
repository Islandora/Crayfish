<?php

namespace App\Islandora\Milliner\Service;

use DateTime;
use DateTimeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Header;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\FedoraApi;
use Islandora\EntityMapper\EntityMapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class MillinerService
 * @package App\Islandora\Milliner\Service
 */
class MillinerService implements MillinerServiceInterface
{
    /**
     * Configured Chullo client for Fedora.
     * @var \Islandora\Chullo\IFedoraApi
     */
    protected $fedora;

    /**
     * A Guzzle client for working with Drupal.
     * @var \GuzzleHttp\Client
     */
    protected $drupal;

    /**
     * Entity path mapper instance.
     * @var \Islandora\EntityMapper\EntityMapperInterface
     */
    protected $mapper;

    /**
     * Logger instance.
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * The modified date predicate to use for comparing last altered datetimes.
     * @var string
     */
    protected $modifiedDatePredicate;

    /**
     * Whether to remove the ?_format=jsonld from URLs being indexed.
     * @var bool
     */
    protected $stripFormatJsonld;

    /**
     * Whether the Fedora we are pointing at is Fedora 6.
     * @var bool
     */
    protected $isFedora6;

    /**
     * MillinerService constructor.
     *
     * @param \GuzzleHttp\Client $drupal
     *   Http client for Drupal.
     * @param \Psr\Log\LoggerInterface $log
     *   Logger
     * @param string $fedoraBaseUrl
     *   The base url to Fedora
     * @param string $modifiedDatePredicate
     *   The modified date predicate to use for comparing last altered datetimes.
     * @param bool $stripFormatJsonld
     *   Whether to remove the ?_format=jsonld from URLs being indexed.
     * @param bool $isFedora6
     *   Whether the Fedora we are pointing at is Fedora 6.
     */
    public function __construct(
        Client $drupal,
        LoggerInterface $log,
        string $fedoraBaseUrl,
        string $modifiedDatePredicate,
        bool $stripFormatJsonld,
        bool $isFedora6
    ) {
        $this->fedora = FedoraApi::create($fedoraBaseUrl);
        $this->drupal = $drupal;
        $this->log = $log;
        $this->modifiedDatePredicate = $modifiedDatePredicate;
        $this->stripFormatJsonld = $stripFormatJsonld;
        $this->isFedora6 = $isFedora6;
        $this->mapper = new EntityMapper();
    }

    /**
     * @inheritDoc
     */
    public function saveNode(
        string $uuid,
        string $jsonld_url,
        string $islandora_fedora_endpoint,
        ?string $token = null
    ): ResponseInterface {
        $path = $this->mapper->getFedoraPath($uuid);
        $islandora_fedora_endpoint = rtrim($islandora_fedora_endpoint, "/");
        $fedora_url = "$islandora_fedora_endpoint/$path";

        $response = $this->fedora->getResourceHeaders($fedora_url);
        if ($response->getStatusCode() == 404) {
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
     * @param string      $jsonld_url The Drupal Json-LD ID of the resource.
     * @param string      $fedora_url The Fedora ID of the associated resource.
     * @param string|null $token      The JWT token or null if none.
     *
     * @return \Psr\Http\Message\ResponseInterface The response from the chullo saveResource call to Fedora.
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function createNode(
        string $jsonld_url,
        string $fedora_url,
        ?string $token = null
    ): ResponseInterface {
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
        $this->log->debug("HEADERS " . json_encode($headers));
        $this->log->debug("FEDORA URL " . $fedora_url);
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
     * @param string      $jsonld_url The Drupal Json-LD ID of the resource.
     * @param string      $fedora_url The Fedora ID of the associated resource.
     * @param string|null $token      The JWT token or null if none.
     *
     * @return \Psr\Http\Message\ResponseInterface The response from the chullo saveResource call to Fedora.
     *
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    protected function updateNode(
        string $jsonld_url,
        string $fedora_url,
        ?string $token = null
    ): ResponseInterface {
        // Get the RDF from Fedora.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $headers['Accept'] = 'application/ld+json';
        if ($this->isFedora6) {
            $prefer = 'return=representation; omit="http://fedora.info/definitions/v4/repository#ServerManaged"';
            $headers['Prefer'] = $prefer;
        }
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
        $state_tokens = $fedora_response->getHeader("X-State-Token");
        $state_token = '"' . ltrim(reset($state_tokens)) . '"';

        $this->log->debug("FEDORA State Token: $state_token");

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
        $subject_url = $this->getLinkHeader($drupal_response, "describes");
        if (empty($subject_url)) {
            $subject_url = $this->stripFormatJsonld ? rtrim($jsonld_url, '?_format=jsonld') : $jsonld_url;
        }
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
        $headers['Prefer'] = 'handling=lenient';
        if (!$this->isFedora6) {
            $headers['Prefer'] .= ';received=minimal';
        }
        $headers['X-If-State-Match'] = $state_token;
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
     * @param array $jsonld The Json-LD array.
     * @param string $drupal_url The Drupal URL.
     * @param string $fedora_url The Fedora URL.
     *
     * @return array The processed Json-LD array
     */
    protected function processJsonld(array $jsonld, string $drupal_url, string $fedora_url): array
    {
        $this->log->debug("DRUPAL URL: $drupal_url");
        $this->log->debug("FEDORA URL: $fedora_url");
        $this->log->debug("BEFORE: " . json_encode($jsonld));
        // Strip out everything other than the resource in question.
        // Ignore http/https.
        $parts = parse_url($drupal_url);
        $subject_url = $parts['host'] . $parts['path'];
        $resource = array_filter(
            $jsonld['@graph'],
            function (array $elem) use ($subject_url) {
                $parts = parse_url($elem['@id']);
                $other_url = $parts['host'] . $parts['path'];
                return $other_url == $subject_url;
            }
        );

        // Put in an fedora url for the resource.
        $resource[0]['@id'] = $fedora_url;


        $this->log->debug("AFTER: " . json_encode($resource));
        return $resource;
    }

    /**
     * Gets the first value for a predicate in a JSONLD array.
     *
     * @param array $jsonld The Json-LD array.
     * @param string $predicate the predicate to look for.
     * @param bool $value Is this a @value and not an @id.
     *
     * @return string|null
     */
    protected function getFirstPredicate(array $jsonld, string $predicate, bool $value = true): ?string
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
    protected function getModifiedTimestamp(array $jsonld): int
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
            DateTimeInterface::W3C,
            $modified
        );

        return $date->getTimestamp();
    }

    /**
     * @inheritDoc
     */
    public function saveMedia(
        $source_field,
        $json_url,
        $islandora_fedora_endpoint,
        $token = null
    ): ResponseInterface {
        $urls = $this->getMediaUrls($source_field, $json_url, $islandora_fedora_endpoint, $token);
        return $this->updateNode(
            $urls['jsonld'],
            $urls['fedora'],
            $token
        );
    }

    /**
     * Gets a Link header with the supplied rel name.
     *
     * @param ResponseInterface $response
     *   The response to get headers from.
     * @param string $rel_name
     *   The rel property to match.
     * @param string|null $type
     *   A type to match if provided.
     *
     * @return null|string
     *   The first matching header or null if none match.
     */
    protected function getLinkHeader(ResponseInterface $response, string $rel_name, string $type = null): ?string
    {
        $parsed = Header::parse($response->getHeader("Link"));
        foreach ($parsed as $header) {
            $has_relation = isset($header['rel']) && $header['rel'] == $rel_name;
            $has_type = is_null($type) || isset($header['type']) && $header['type'] == $type;
            if ($has_type && $has_relation) {
                return trim($header[0], '<>');
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function deleteNode(
        $uuid,
        $islandora_fedora_endpoint,
        $token = null
    ): ResponseInterface {
        $path = $this->mapper->getFedoraPath($uuid);
        $islandora_fedora_endpoint = rtrim($islandora_fedora_endpoint, "/");
        $fedora_url = "$islandora_fedora_endpoint/$path";

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
    ): ResponseInterface {
        $path = $this->mapper->getFedoraPath($uuid);
        $islandora_fedora_endpoint = rtrim($islandora_fedora_endpoint, "/");
        $fedora_url = "$islandora_fedora_endpoint/$path";

        $headers = empty($token) ? [] : ['Authorization' => $token];
        // Try it with an without auth b/c files can be public or private.
        try {
            $drupal_response = $this->drupal->head(
                $external_url,
                ['headers' => $headers]
            );
        } catch (ClientException $e) {
            $this->log->debug("GOT {$e->getCode()}, TRYING WITHOUT AUTH HEADER");
            $drupal_response = $this->drupal->head(
                $external_url,
                ['headers' => []]
            );
        }

        $mimetype = $drupal_response->getHeader('Content-Type')[0];

        if (preg_match("/^([^;]+);/", $mimetype, $matches)) {
            $mimetype = $matches[1];
        }

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
    ): ResponseInterface {
        $path = $this->mapper->getFedoraPath($uuid);
        $islandora_fedora_endpoint = rtrim($islandora_fedora_endpoint, "/");
        $fedora_url = "$islandora_fedora_endpoint/$path";

        $headers = empty($token) ? [] : ['Authorization' => $token];
        $date = new DateTime();
        $timestamp = $date->format("D, d M Y H:i:s O");
        // create version in Fedora.
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
    }

    /**
     * {@inheritDoc}
     */
    public function createMediaVersion(
        $source_field,
        $json_url,
        $islandora_fedora_endpoint,
        $token = null
    ): ResponseInterface {
        $urls = $this->getMediaUrls($source_field, $json_url, $islandora_fedora_endpoint, $token);
        $fedora_url = $urls['fedora'];

        $headers = empty($token) ? [] : ['Authorization' => $token];
        $date = new DateTime();
        $timestamp = $date->format("D, d M Y H:i:s O");
        // create version in Fedora.
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
    }

    /**
     * Utility function to get media urls.
     *
     * @param string $source_field
     *   The source field to get media urls for.
     * @param string $json_url
     *   The drupal media resource's JSON format URL.
     * @param string $islandora_fedora_endpoint
     *   The Fedora Base URL.
     * @param string|null $token
     *   The authorization token or null for no auth.
     * @return array
     *   Associative array with keys 'drupal', 'fedora' and 'jsonld' with the various URLs.
     */
    protected function getMediaUrls(
        string $source_field,
        string $json_url,
        string $islandora_fedora_endpoint,
        string $token = null
    ): array {
        // GET request for link headers and file UUID.
        $headers = empty($token) ? [] : ['Authorization' => $token];
        $drupal_response = $this->drupal->get(
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

        // Construct the fedora url.
        // Try to handle flysystem files first.
        $islandora_fedora_endpoint = rtrim($islandora_fedora_endpoint, "/");
        $pieces = explode("_flysystem/fedora/", $drupal_url);
        if (count($pieces) > 1) {
            $fedora_file_path = end($pieces);
        } else {
            $fedora_file_path = $this->mapper->getFedoraPath($file_uuid);
        }
        $fedora_file_url = "$islandora_fedora_endpoint/$fedora_file_path";

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
