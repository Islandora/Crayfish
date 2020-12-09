<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\UrlMinter\UrlMinter;
use PHPUnit\Framework\TestCase;

/**
 * Class UrlMinterTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\UrlMinter\UrlMinter
 */
class UrlMinterTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::mint
     */
    public function testThrowsExceptionOnBlankString()
    {
        $minter = new UrlMinter("http://localhost:8080/fcrepo/rest");
        $this->expectException(\InvalidArgumentException::class, null, 400);
        $minter->mint("", "");
    }

    /**
     * @covers ::__construct
     * @covers ::mint
     */
    public function testThrowsExceptionOnShortUUID()
    {
        $minter = new UrlMinter("http://localhost:8080/fcrepo/rest");
        $this->expectException(\InvalidArgumentException::class, null, 400);
        $minter->mint("abcd", "http://localhost:8080/fcrepo/rest/");
    }

    /**
     * @covers ::__construct
     * @covers ::mint
     */
    public function testHandlesMissingTrailingSlashInBaseUrl()
    {
        $missing_slash = new UrlMinter("http://localhost:8080/fcrepo/rest");
        $first = $missing_slash->mint("5d150b3a-9d1b-437f-87a9-104b8cf15859", "http://localhost:8080/fcrepo/rest/");

        $with_slash = new UrlMinter("http://localhost:8080/fcrepo/rest/");
        $second = $with_slash->mint("5d150b3a-9d1b-437f-87a9-104b8cf15859", "http://localhost:8080/fcrepo/rest/");

        $this->assertTrue(
            strcmp($first, $second) == 0,
            "Minted URLs must be the same whether or not a trailing slash is in the base url."
        );
    }

    /**
     * @covers ::__construct
     * @covers ::mint
     */
    public function testMintsUrlWithPairTrees()
    {
        $minter = new UrlMinter("http://localhost:8080/fcrepo/rest");
        $expected = "http://localhost:8080/fcrepo/rest/5d/15/0b/3a/5d150b3a-9d1b-437f-87a9-104b8cf15859";
        $actual = $minter->mint("5d150b3a-9d1b-437f-87a9-104b8cf15859", "http://localhost:8080/fcrepo/rest/");

        $this->assertTrue(
            strcmp($actual, $expected) == 0,
            "Generated URL was not of he correct format.  Actual: $actual. Expected: $expected"
        );
    }
}
