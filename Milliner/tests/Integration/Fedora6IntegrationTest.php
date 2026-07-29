<?php

namespace App\Islandora\Milliner\Tests\Integration;

use App\Islandora\Milliner\Service\MillinerService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Islandora\EntityMapper\EntityMapper;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
class Fedora6IntegrationTest extends TestCase
{
    private string $fedoraBaseUrl;

    private string $token;

    protected function setUp(): void
    {
        $fedoraBaseUrl = getenv('MILLINER_FEDORA6_URL');
        if ($fedoraBaseUrl === false || $fedoraBaseUrl === '') {
            $this->markTestSkipped('Set MILLINER_FEDORA6_URL to run the Fedora 6 integration test.');
        }

        $this->fedoraBaseUrl = rtrim($fedoraBaseUrl, '/');
        $this->token = getenv('MILLINER_FEDORA6_TOKEN') ?: 'Basic YWRtaW46cGFzc3dvcmQ=';
    }

    public function testNodeLifecycleAgainstFedora6(): void
    {
        $uuid = $this->uuid();
        $drupalUrl = "http://drupal.example/node/$uuid?_format=jsonld";
        $fedoraUrl = $this->fedoraBaseUrl . '/' . (new EntityMapper())->getFedoraPath($uuid);
        $createdTitle = "Milliner integration create $uuid";
        $updatedTitle = "Milliner integration update $uuid";
        $drupal = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                $this->jsonLdResponse($drupalUrl, $createdTitle, '2026-07-29T10:00:00+00:00'),
                $this->jsonLdResponse($drupalUrl, $updatedTitle, '2026-07-29T10:00:01+00:00'),
            ])),
        ]);
        $logger = new Logger('milliner-integration');
        $logger->pushHandler(new NullHandler());
        $milliner = new MillinerService(
            $drupal,
            $logger,
            $this->fedoraBaseUrl,
            'http://schema.org/dateModified',
            true,
            true
        );
        $fedora = new Client(['http_errors' => false]);
        $deleted = false;

        try {
            $createResponse = $milliner->saveNode(
                $uuid,
                $drupalUrl,
                $this->fedoraBaseUrl,
                $this->token
            );
            $this->assertContains($createResponse->getStatusCode(), [201, 204]);
            $this->assertFedoraContains($fedora, $fedoraUrl, $createdTitle);

            $updateResponse = $milliner->saveNode(
                $uuid,
                $drupalUrl,
                $this->fedoraBaseUrl,
                $this->token
            );
            $this->assertContains($updateResponse->getStatusCode(), [201, 204]);
            $this->assertFedoraContains($fedora, $fedoraUrl, $updatedTitle);

            $versionResponse = $milliner->createVersion(
                $uuid,
                $this->fedoraBaseUrl,
                $this->token
            );
            $this->assertSame(201, $versionResponse->getStatusCode());

            $deleteResponse = $milliner->deleteNode(
                $uuid,
                $this->fedoraBaseUrl,
                $this->token
            );
            $this->assertSame(204, $deleteResponse->getStatusCode());
            $deleted = true;

            $response = $fedora->get($fedoraUrl, $this->fedoraOptions());
            $this->assertContains($response->getStatusCode(), [404, 410]);
        } finally {
            if (!$deleted) {
                $fedora->delete($fedoraUrl, $this->fedoraOptions());
            }
        }
    }

    private function jsonLdResponse(
        string $drupalUrl,
        string $title,
        string $modified
    ): Response {
        $jsonld = [
            '@graph' => [[
                '@id' => $drupalUrl,
                '@type' => ['http://pcdm.org/models#Object'],
                'http://purl.org/dc/terms/title' => [['@value' => $title]],
                'http://schema.org/dateModified' => [['@value' => $modified]],
            ]],
        ];

        return new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            json_encode($jsonld, JSON_THROW_ON_ERROR)
        );
    }

    private function assertFedoraContains(Client $fedora, string $fedoraUrl, string $value): void
    {
        $options = $this->fedoraOptions();
        $options['headers']['Accept'] = 'application/ld+json';
        $response = $fedora->get($fedoraUrl, $options);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString($value, (string) $response->getBody());
    }

    private function fedoraOptions(): array
    {
        return [
            'headers' => ['Authorization' => $this->token],
            'http_errors' => false,
        ];
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );
    }
}
