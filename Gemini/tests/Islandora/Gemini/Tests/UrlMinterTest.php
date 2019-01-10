<?php

namespace Islandora\Gemini\Tests;

use Islandora\Gemini\UrlMinter\UrlMinter;

/**
 * Class UrlMinterTest
 * @package Islandora\Gemini\Tests
 * @coversDefaultClass \Islandora\Gemini\UrlMinter\UrlMinter
 */
class UrlMinterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::mint
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnBlankString()
    {
        $minter = new UrlMinter("http://localhost:8080/fcrepo/rest");
        $minter->mint("");
    }

    /**
     * @covers ::__construct
     * @covers ::mint
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnShortUUID()
    {
        $minter = new UrlMinter("http://localhost:8080/fcrepo/rest");
        $minter->mint("abcd");
    }

    /**
     * @covers ::__construct
     * @covers ::mint
     */
    public function testHandlesMissingTrailingSlashInBaseUrl()
    {
        $missing_slash = new UrlMinter("http://localhost:8080/fcrepo/rest");
        $first = $missing_slash->mint("5d150b3a-9d1b-437f-87a9-104b8cf15859");

        $with_slash = new UrlMinter("http://localhost:8080/fcrepo/rest/");
        $second = $with_slash->mint("5d150b3a-9d1b-437f-87a9-104b8cf15859");

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
        $actual = $minter->mint("5d150b3a-9d1b-437f-87a9-104b8cf15859");

        $this->assertTrue(
            strcmp($actual, $expected) == 0,
            "Generated URL was not of he correct format.  Actual: $actual. Expected: $expected"
        );
    }
}
