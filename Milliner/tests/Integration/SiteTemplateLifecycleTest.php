<?php

namespace App\Islandora\Milliner\Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Islandora\EntityMapper\EntityMapper;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Throwable;

#[CoversNothing]
#[Group('integration')]
#[Group('site-template')]
final class SiteTemplateLifecycleTest extends TestCase
{
    private const FEDORA_TITLE = 'http://purl.org/dc/terms/title';

    private const WAIT_SECONDS = 300;

    private Client $drupal;

    private Client $fedora;

    private ?int $nodeId = null;

    private ?string $fedoraUrl = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('MILLINER_SITE_TEMPLATE_TEST') === false) {
            $this->markTestSkipped('The site-template stack is required for this test.');
        }

        $this->assertSame(
            '1',
            getenv('MILLINER_SITE_TEMPLATE_TEST'),
            'MILLINER_SITE_TEMPLATE_TEST must be set to 1.'
        );

        $this->drupal = new Client([
            'auth' => ['admin', $this->readSecret('/run/secrets/DRUPAL_DEFAULT_ACCOUNT_PASSWORD')],
            'base_uri' => rtrim($this->requireEnvironment('MILLINER_SITE_DRUPAL_URL'), '/') . '/',
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'Host' => $this->requireEnvironment('MILLINER_SITE_DRUPAL_HOST'),
            ],
            'http_errors' => false,
            'timeout' => 30,
        ]);
        $this->fedora = new Client([
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/ld+json',
                'Authorization' => 'Bearer ' . $this->readSecret('/run/secrets/JWT_ADMIN_TOKEN'),
            ],
            'http_errors' => false,
            'timeout' => 30,
        ]);
    }

    public function testDrupalNodeLifecycleIsPersistedToFedora(): void
    {
        $suffix = bin2hex(random_bytes(8));
        $createdTitle = "Milliner integration create $suffix";
        $updatedTitle = "Milliner integration update $suffix";

        try {
            $modelId = $this->collectionModelId();
            $created = $this->drupal->post('node?_format=json', [
                'json' => [
                    'type' => [['target_id' => 'islandora_object']],
                    'title' => [['value' => $createdTitle]],
                    'status' => [['value' => true]],
                    'field_model' => [['target_id' => $modelId]],
                ],
            ]);
            $this->assertResponseStatus(201, $created, 'Drupal node creation');

            $createdEntity = $this->decodeResponse($created, 'Drupal node creation');
            $this->nodeId = $this->positiveIntegerField($createdEntity, 'nid', 'value');
            $uuid = $this->stringField($createdEntity, 'uuid', 'value');
            $this->assertMatchesRegularExpression(
                '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
                $uuid
            );
            $this->assertSame($createdTitle, $this->stringField($createdEntity, 'title', 'value'));
            $this->assertSame(
                $modelId,
                $this->positiveIntegerField($createdEntity, 'field_model', 'target_id')
            );

            $fedoraBaseUrl = rtrim($this->requireEnvironment('MILLINER_SITE_FEDORA_URL'), '/');
            $this->fedoraUrl = $fedoraBaseUrl . '/' . (new EntityMapper())->getFedoraPath($uuid);
            $this->waitForFedoraTitle($createdTitle);

            $createdChanged = $this->dateTimeField($createdEntity, 'changed', 'value');
            $clockDeadline = microtime(true) + 2;
            while (time() <= $createdChanged->getTimestamp() && microtime(true) < $clockDeadline) {
                usleep(100000);
            }
            $this->assertGreaterThan(
                $createdChanged->getTimestamp(),
                time(),
                'The clock did not advance beyond Drupal\'s creation timestamp.'
            );

            $updated = $this->drupal->patch("node/{$this->nodeId}?_format=json", [
                'json' => [
                    'type' => [['target_id' => 'islandora_object']],
                    'title' => [['value' => $updatedTitle]],
                ],
            ]);
            $this->assertResponseStatus(200, $updated, 'Drupal node update');

            $updatedEntity = $this->decodeResponse($updated, 'Drupal node update');
            $this->assertSame($uuid, $this->stringField($updatedEntity, 'uuid', 'value'));
            $this->assertSame($updatedTitle, $this->stringField($updatedEntity, 'title', 'value'));
            $this->waitForFedoraTitle($updatedTitle, [$createdTitle]);

            $nodeId = $this->nodeId;
            $deleted = $this->drupal->delete("node/$nodeId?_format=json");
            $this->assertResponseStatus(204, $deleted, 'Drupal node deletion');
            $this->assertSame('', trim((string) $deleted->getBody()));
            $this->nodeId = null;

            $missing = $this->drupal->get("node/$nodeId?_format=json");
            $this->assertResponseStatus(404, $missing, 'Drupal deleted-node lookup');
            $this->waitForFedoraAbsence();
            $this->fedoraUrl = null;
        } finally {
            $this->cleanUpCreatedResources();
        }
    }

    private function collectionModelId(): int
    {
        $response = $this->drupal->get('term_from_term_name', [
            'query' => [
                '_format' => 'json',
                'name' => 'Collection',
                'vocab' => 'islandora_models',
            ],
        ]);
        $this->assertResponseStatus(200, $response, 'Collection model lookup');

        $models = $this->decodeResponse($response, 'Collection model lookup');
        $this->assertCount(1, $models, 'Expected exactly one Collection model term.');
        $this->assertIsArray($models[0]);

        return $this->positiveIntegerField($models[0], 'tid', 'value');
    }

    private function waitForFedoraTitle(string $expected, array $unexpected = []): void
    {
        $deadline = microtime(true) + self::WAIT_SECONDS;
        $lastResult = 'No request was made.';

        do {
            try {
                $response = $this->fedora->get($this->fedoraUrl);
                $lastResult = $this->describeResponse($response);
                if ($response->getStatusCode() === 200) {
                    $document = $this->decodeResponse($response, 'Fedora resource lookup');
                    $titles = $this->predicateValues($document, self::FEDORA_TITLE);
                    if (in_array($expected, $titles, true)
                        && array_intersect($unexpected, $titles) === []
                    ) {
                        return;
                    }
                    $lastResult .= "\nDecoded Fedora titles: " . json_encode($titles);
                }
            } catch (GuzzleException | JsonException $exception) {
                $lastResult = get_class($exception) . ': ' . $exception->getMessage();
            }

            usleep(5000000);
        } while (microtime(true) < $deadline);

        $this->fail(
            "Fedora did not contain the expected title '$expected' at {$this->fedoraUrl}.\n" .
            "Last result:\n$lastResult"
        );
    }

    private function waitForFedoraAbsence(): void
    {
        $deadline = microtime(true) + self::WAIT_SECONDS;
        $lastResult = 'No request was made.';

        do {
            try {
                $response = $this->fedora->get($this->fedoraUrl);
                $lastResult = $this->describeResponse($response);
                if (in_array($response->getStatusCode(), [404, 410], true)) {
                    return;
                }
            } catch (GuzzleException $exception) {
                $lastResult = get_class($exception) . ': ' . $exception->getMessage();
            }

            usleep(5000000);
        } while (microtime(true) < $deadline);

        $this->fail(
            "Fedora resource still exists at {$this->fedoraUrl}.\nLast result:\n$lastResult"
        );
    }

    private function predicateValues(array $document, string $predicate): array
    {
        $values = [];
        foreach ($document as $key => $value) {
            if ($key === $predicate && is_array($value)) {
                foreach ($value as $item) {
                    if (is_array($item) && isset($item['@value']) && is_string($item['@value'])) {
                        $values[] = $item['@value'];
                    }
                }
            }
            if (is_array($value)) {
                $values = array_merge($values, $this->predicateValues($value, $predicate));
            }
        }

        return array_values(array_unique($values));
    }

    private function positiveIntegerField(array $entity, string $field, string $property): int
    {
        $value = $this->fieldValue($entity, $field, $property);
        $this->assertTrue(
            is_int($value) || (is_string($value) && ctype_digit($value)),
            "$field.$property must be an integer."
        );
        $value = (int) $value;
        $this->assertGreaterThan(0, $value, "$field.$property must be positive.");

        return $value;
    }

    private function stringField(array $entity, string $field, string $property): string
    {
        $value = $this->fieldValue($entity, $field, $property);
        $this->assertIsString($value, "$field.$property must be a string.");
        $this->assertNotSame('', $value, "$field.$property must not be empty.");

        return $value;
    }

    private function dateTimeField(
        array $entity,
        string $field,
        string $property
    ): \DateTimeImmutable {
        $value = $this->stringField($entity, $field, $property);
        $dateTime = \DateTimeImmutable::createFromFormat(DATE_RFC3339, $value);
        $this->assertNotFalse($dateTime, "$field.$property must be an RFC3339 timestamp.");

        return $dateTime;
    }

    private function fieldValue(array $entity, string $field, string $property): mixed
    {
        $this->assertArrayHasKey($field, $entity);
        $this->assertIsArray($entity[$field]);
        $this->assertNotEmpty($entity[$field]);
        $this->assertIsArray($entity[$field][0]);
        $this->assertArrayHasKey($property, $entity[$field][0]);

        return $entity[$field][0][$property];
    }

    private function decodeResponse(ResponseInterface $response, string $operation): array
    {
        $body = (string) $response->getBody();
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->fail("$operation returned invalid JSON: {$exception->getMessage()}\n$body");
        }
        $this->assertIsArray($decoded, "$operation must return a JSON object or array.");

        return $decoded;
    }

    private function assertResponseStatus(
        int $expected,
        ResponseInterface $response,
        string $operation
    ): void {
        $this->assertSame(
            $expected,
            $response->getStatusCode(),
            "$operation returned an unexpected response.\n" . $this->describeResponse($response)
        );
    }

    private function describeResponse(ResponseInterface $response): string
    {
        return "HTTP {$response->getStatusCode()}\n" . (string) $response->getBody();
    }

    private function readSecret(string $path): string
    {
        $contents = @file_get_contents($path);
        $this->assertNotFalse($contents, "Required secret is not readable: $path");
        $contents = trim($contents);
        $this->assertNotSame('', $contents, "Required secret is empty: $path");

        return $contents;
    }

    private function requireEnvironment(string $name): string
    {
        $value = getenv($name);
        $this->assertNotFalse($value, "Required environment variable is missing: $name");
        $this->assertNotSame('', $value, "Required environment variable is empty: $name");

        return $value;
    }

    private function cleanUpCreatedResources(): void
    {
        if ($this->nodeId !== null) {
            try {
                $this->drupal->delete("node/{$this->nodeId}?_format=json");
            } catch (Throwable) {
                // Preserve the original test failure.
            }
        }
        if ($this->fedoraUrl !== null) {
            try {
                $this->fedora->delete($this->fedoraUrl);
            } catch (Throwable) {
                // Preserve the original test failure.
            }
        }
    }
}
