<?php

namespace Islandora\Gemini\Tests;

use Doctrine\DBAL\Driver\DriverException;
use Islandora\Gemini\UrlMapper\UrlMapper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Prophecy\Argument;

/**
 * Class UrlMapperTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\UrlMapper\UrlMapper
 */
class UrlMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getUrls
     */
    public function testGetUrlsReturnsUnmodifiedResults()
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
     * @expectedException \Exception
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
     * @expectedException \Exception
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
        $mapper->deleteUrls("foo");
    }
}
