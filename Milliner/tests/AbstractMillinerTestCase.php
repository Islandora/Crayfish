<?php

namespace App\Islandora\Milliner\Tests;

use App\Islandora\Milliner\Service\MillinerService;
use App\Islandora\Milliner\Service\MillinerServiceInterface;
use donatj\MockWebServer\MockWebServer;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\EntityMapper\EntityMapper;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Abstract test to hold common test infrastructure.
 * @author whikloj
 */
class AbstractMillinerTestCase extends TestCase
{

    use ProphecyTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * The predicate to compare when checking date modified.
     * @var string
     */
    protected string $modifiedDatePredicate;

    /**
     * @var string
     */
    protected string $uuid;

    /**
     * Is the Fedora of version >= 6.0.0
     * @var bool
     */
    protected bool $isFedora6 = false;

    /**
     * Whether to strip the ?_format=jsonld from URLs
     * @var bool
     */
    protected bool $stripJsonLd = false;

    /**
     * @var string
     */
    protected string $fedoraBaseUrl;

    /**
     * @var \Islandora\Chullo\IFedoraApi|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $fedora_client_prophecy;

    /**
     * @var \GuzzleHttp\Client|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $drupal_client_prophecy;

    /**
     * A 200 OK response.
     * @var \donatj\MockWebServer\Response
     */
    protected \donatj\MockWebServer\Response $ok_response;

    /**
     * A 201 Created response.
     * @var \donatj\MockWebServer\Response
     */
    protected \donatj\MockWebServer\Response $created_response;

    /**
     * A 204 No Content response.
     * @var \donatj\MockWebServer\Response
     */
    protected \donatj\MockWebServer\Response $no_content_response;

    /**
     * A 404 Not Found response.
     * @var \donatj\MockWebServer\Response
     */
    protected \donatj\MockWebServer\Response $not_found_response;

    /**
     * A 401 Unauthorized response.
     * @var \donatj\MockWebServer\Response
     */
    protected \donatj\MockWebServer\Response $unauthorized_response;

    /**
     * A 403 Forbidden response
     * @var \donatj\MockWebServer\Response
     */
    protected \donatj\MockWebServer\Response $forbidden_response;

    /**
     * A 410 Gone response
     * @var \donatj\MockWebServer\Response
     */
    protected \donatj\MockWebServer\Response $gone_response;

    /**
     * The mock webserver for Fedora responses.
     * @var \donatj\MockWebServer\MockWebServer
     */
    protected static $webserver;

    /**
     * An entity mapper.
     * @var \Islandora\EntityMapper\EntityMapper
     */
    protected EntityMapper $entity_mapper;

    /**
     * The mapped Drupal UUID as a fedora path.
     * @var string
     */
    protected string $fedora_path;

    /**
     * The full Fedora URI.
     * @var string
     */
    protected string $fedora_full_uri;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        self::$webserver = new MockWebServer();
        self::$webserver->start();
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        self::$webserver->stop();
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());
        $this->entity_mapper = new EntityMapper();

        // Default properties
        $this->modifiedDatePredicate = "http://schema.org/dateModified";
        $this->fedoraBaseUrl = 'http://' . self::$webserver->getHost() . ':' .
            self::$webserver->getPort() . '/fcrepo/rest';
        $this->rebuildFedoraUris('9541c0c1-5bee-4973-a9d0-e55c1658bc8');

        // Prophecies
        $this->drupal_client_prophecy = $this->prophesize(Client::class);

        // Reusable responses
        $this->ok_response = new \donatj\MockWebServer\Response("", [], 200);
        $this->created_response = new \donatj\MockWebServer\Response("", [], 201);
        $this->no_content_response = new \donatj\MockWebServer\Response("", [], 204);
        $this->not_found_response = new \donatj\MockWebServer\Response("", [], 404);
        $this->forbidden_response = new \donatj\MockWebServer\Response("", [], 403);
        $this->unauthorized_response = new \donatj\MockWebServer\Response("", [], 401);
        $this->gone_response = new \donatj\MockWebServer\Response("", [], 410);
    }

    /**
     * Rebuild all the variables for Fedora URIs based on this UUID.
     * @param string $uuid the UUID
     * @return void
     */
    protected function rebuildFedoraUris(string $uuid): void
    {
        $this->uuid = $uuid;
        $mapped_path = $this->entity_mapper->getFedoraPath($this->uuid);
        $this->fedora_path = '/fcrepo/rest/' . $mapped_path;
        $this->fedora_full_uri = "{$this->fedoraBaseUrl}/$mapped_path";
    }

    /**
     * @param string $filename
     *   The filename from the static directory.
     * @return string
     *   The full path to the file.
     */
    protected function getStaticFile(string $filename): string
    {
        return __DIR__ . "/static/{$filename}";
    }

    /**
     * Return a new MillinerService
     *
     * @return \App\Islandora\Milliner\Service\MillinerServiceInterface
     */
    protected function getMilliner(): MillinerServiceInterface
    {
        return new MillinerService(
            $this->drupal_client_prophecy->reveal(),
            $this->logger,
            $this->fedoraBaseUrl,
            $this->modifiedDatePredicate,
            $this->stripJsonLd,
            $this->isFedora6
        );
    }
}
