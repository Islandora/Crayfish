<?php

namespace Islandora\Gemini\Tests;

use Doctrine\DBAL\Driver\DriverException;
use Islandora\Gemini\UrlMapper\UrlMapper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Class UrlMapperTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\UrlMapper\UrlMapper
 */
class UrlMapperTest extends TestCase
{
    use ProphecyTrait;
    /**
     * @covers ::__construct
     * @covers ::getUrls
     */
    public function testGetUrlsReturnsUnmodifiedResultsIfNotConfigured()
    {
        // Simulate a record being returned.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
            ->willReturn(['fedora' => 'foo', 'drupal' => 'bar']);
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);

        $results = $mapper->getUrls("abc");

        $this->assertTrue(
            $results['fedora'] == 'foo',
            "getUrls() modified connection results.  Actual: ${results['fedora']}. Expected: foo"
        );
        $this->assertTrue(
            $results['drupal'] == 'bar',
            "getUrls() modified connection results.  Actual: ${results['drupal']}. Expected: bar"
        );

        // Simulate when no record is found.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
            ->willReturn([]);
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);

        $results = $mapper->getUrls("abc");

        $this->assertTrue(
            empty($results),
            "getUrls() modified connection results.  Expected empty array, received " . json_encode($results)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getUrls
     */
    public function testGetUrlsReturnsModifiedResultsIfConfigured()
    {
        // Simulate a record being returned.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
            ->willReturn(['fedora' => 'http://exapmle.org/foo', 'drupal' => 'http://example.org/bar']);
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection, 'drupal.example.org', 'fcrepo.example.org');

        $results = $mapper->getUrls("abc");

        $this->assertTrue(
            $results['fedora'] == 'http://fcrepo.example.org/foo',
            "getUrls() disobeyed configuration.  Actual: ${results['fedora']}. Expected: " .
            "http://fcrepo.example.org/foo"
        );
        $this->assertTrue(
            $results['drupal'] == 'http://drupal.example.org/bar',
            "getUrls() modified connection results.  Actual: ${results['drupal']}. Expected: " .
            "http://drupal.example.org/bar"
        );

        // Simulate when no record is found.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
            ->willReturn([]);
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);

        $results = $mapper->getUrls("abc");

        $this->assertTrue(
            empty($results),
            "getUrls() modified connection results.  Expected empty array, received " . json_encode($results)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveUrls
     */
    public function testSaveUrlsReturnsTrueOnCreation()
    {
        // Simulate a record being created.
        $connection = $this->prophesize(Connection::class);
        $connection->beginTransaction()->shouldBeCalled();
        $connection->insert(Argument::any(), Argument::any())
            ->willReturn(1);
        $connection->commit()->shouldBeCalled();
        $connection->rollBack()->shouldNotBeCalled();
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);

        $this->assertTrue(
            $mapper->saveUrls("foo", "bar", "baz"),
            "saveUrls() must return true when a new record is created"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveUrls
     */
    public function testSaveUrlsReturnsFalseOnUpdate()
    {
        // Simulate a record being updated.
        $exception = $this->prophesize(UniqueConstraintViolationException::class)->reveal();

        $connection = $this->prophesize(Connection::class);
        $connection->beginTransaction()->shouldBeCalled();
        $connection->insert(Argument::any(), Argument::any())
            ->willThrow($exception);
        $connection->update(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(1);
        $connection->commit()->shouldBeCalled();
        $connection->rollBack()->shouldNotBeCalled();
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);

        $this->assertFalse(
            $mapper->saveUrls("foo", "bar", "baz"),
            "saveUrls() must return false when an existing record is updated"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveUrls
     */
    public function testSaveUrlsRollsBackOnException()
    {
        $connection = $this->prophesize(Connection::class);
        $connection->beginTransaction()->shouldBeCalled();
        $connection->insert(Argument::any(), Argument::any())
            ->willThrow(new \Exception(""));
        $connection->rollBack()->shouldBeCalled();
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);
        $this->expectException(\Exception::class);
        $mapper->saveUrls("foo", "bar", "baz");
    }

    /**
     * @covers ::__construct
     * @covers ::deleteUrls
     */
    public function testDeleteUrlsReturnsTrueIfFound()
    {
        $connection = $this->prophesize(Connection::class);
        $connection->beginTransaction()->shouldBeCalled();
        $connection->delete(Argument::any(), Argument::any())
            ->willReturn(1);
        $connection->commit()->shouldBeCalled();
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);

        $this->assertTrue(
            $mapper->deleteUrls("foo"),
            "deleteUrls() must return true when an existing record is deleted"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::deleteUrls
     */
    public function testDeleteUrlsReturnsFalseIfNotFound()
    {
        $connection = $this->prophesize(Connection::class);
        $connection->beginTransaction()->shouldBeCalled();
        $connection->delete(Argument::any(), Argument::any())
            ->willReturn(0);
        $connection->commit()->shouldBeCalled();
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);

        $this->assertFalse(
            $mapper->deleteUrls("foo"),
            "deleteUrls() must return false when no record is found."
        );
    }

    /**
     * @covers ::__construct
     * @covers ::deleteUrls
     */
    public function testDeleteUrlsRollsBackOnException()
    {
        $connection = $this->prophesize(Connection::class);
        $connection->beginTransaction()->shouldBeCalled();
        $connection->delete(Argument::any(), Argument::any())
            ->willThrow(new \Exception(""));
        $connection->rollBack()->shouldBeCalled();
        $connection = $connection->reveal();

        $mapper = new UrlMapper($connection);
        $this->expectException(\Exception::class);
        $mapper->deleteUrls("foo");
    }

    /**
     * @covers ::findUrls
     */
    public function testFindUrlsReturnsUnmodifiedIfNotConfigured()
    {
        // Simulate a record being returned.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
          ->willReturn(['uri' => 'foo']);
        $connection = $connection->reveal();
        $mapper = new UrlMapper($connection);
        $results = $mapper->findUrls("abc");
        $this->assertTrue(
            $results['uri'] == 'foo',
            "getUrls() modified connection results.  Actual: ${results['uri']}. Expected: foo"
        );
        // Simulate when no record is found.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
          ->willReturn([]);
        $connection = $connection->reveal();
        $mapper = new UrlMapper($connection);
        $results = $mapper->findUrls("abc");
        $this->assertTrue(
            empty($results),
            "getUrls() modified connection results.  Expected empty array, received " . json_encode($results)
        );
    }

    /**
     * @covers ::findUrls
     */
    public function testFindUrlsReturnsModifiedIfConfigured()
    {
        // Simulate a record being returned for Fedora.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
          ->willReturn(['fedora_uri' => 'http://example.org/foo']);
        $connection = $connection->reveal();
        $mapper = new UrlMapper($connection, 'drupal.example.org', 'fcrepo.example.org');
        $results = $mapper->findUrls("abc");
        $this->assertTrue(
            $results['uri'] == 'http://fcrepo.example.org/foo',
            "getUrls() did not modify connection results.  Actual: ${results['uri']}. Expected: " .
            "http://fcrepo.example.org/foo"
        );

        // Simulate a record being returned for Drupal.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
          ->willReturn(['drupal_uri' => 'http://example.org/bar']);
        $connection = $connection->reveal();
        $mapper = new UrlMapper($connection, 'drupal.example.org', 'fcrepo.example.org');
        $results = $mapper->findUrls("abc");
        $this->assertTrue(
            $results['uri'] == 'http://drupal.example.org/bar',
            "getUrls() did not modify connection results.  Actual: ${results['uri']}. Expected: " .
            "http://drupal.example.org/bar"
        );

        // Simulate when no record is found.
        $connection = $this->prophesize(Connection::class);
        $connection->fetchAssoc(Argument::any(), Argument::any())
          ->willReturn([]);
        $connection = $connection->reveal();
        $mapper = new UrlMapper($connection, 'drupal.example.org', 'fcrepo.example.org');
        $results = $mapper->findUrls("abc");
        $this->assertTrue(
            empty($results),
            "getUrls() modified connection results.  Expected empty array, received " . json_encode($results)
        );
    }

  /**
   * @covers ::replaceDomain
   */
    public function testReplaceDomain()
    {
        $params = [
            'path' => __DIR__ . '../../../resources/testdb.sqlite3',
            'driver' => 'pdo_sqlite'
        ];
        $class = new \ReflectionClass('\Islandora\Gemini\UrlMapper\UrlMapper');
        $methodCall = $class->getMethod('replaceDomain');
        $methodCall->setAccessible(true);
        $domain = "something.new";
        $url = "https://something.old/my/file";
        $expected = "https://something.new/my/file";
        $connection = \Doctrine\DBAL\DriverManager::getConnection($params);
        $mapper = new UrlMapper($connection, "drupal.domain", "fedora.domain");
        $newUrl = $methodCall->invokeArgs($mapper, [$url, $domain]);
        $this->assertEquals($expected, $newUrl);
    }
}
