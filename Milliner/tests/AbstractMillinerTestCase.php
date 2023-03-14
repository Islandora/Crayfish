<?php

namespace App\Islandora\Milliner\Tests;

use App\Islandora\Milliner\Service\MillinerService;
use App\Islandora\Milliner\Service\MillinerServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
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
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $ok_response;

    /**
     * A 201 Created response.
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $created_response;

    /**
     * A 204 No Content response.
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $no_content_response;

    /**
     * A 404 Not Found response.
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $not_found_response;

    /**
     * A 401 Unauthorized response.
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $unauthorized_response;

    /**
     * A 403 Forbidden response
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $forbidden_response;

    /**
     * A 410 Gone response
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $gone_response;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());

        // Default properties
        $this->modifiedDatePredicate = "http://schema.org/dateModified";
        $this->fedoraBaseUrl = 'http://localhost:8080/fcrepo/rest';
        $this->uuid = '9541c0c1-5bee-4973-a9d0-e55c1658bc8';

        // Prophecies
        $this->drupal_client_prophecy = $this->prophesize(Client::class);
        $this->fedora_client_prophecy = $this->prophesize(IFedoraApi::class);

        // Reusable responses
        $this->ok_response = new Response(200);
        $this->created_response = new Response(201);
        $this->no_content_response = new Response(204);
        $this->not_found_response = new Response(404);
        $this->forbidden_response = new Response(403, [], null, '1.1', 'FORBIDDEN');
        $this->unauthorized_response = new Response(401, [], null, '1.1', 'UNAUTHORIZED');
        $this->gone_response = new Response(410);
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
            $this->fedora_client_prophecy->reveal(),
            $this->drupal_client_prophecy->reveal(),
            $this->logger,
            $this->modifiedDatePredicate,
            $this->stripJsonLd,
            $this->isFedora6
        );
    }
}
