<?php

namespace Islandora\Crayfish\Commons\Syn\tests;

use Islandora\Crayfish\Commons\Syn\SettingsParser;
use org\bovigo\vfs\vfsStream;
use Psr\Log\AbstractLogger;

class SettingsParserSiteTest extends \PHPUnit_Framework_TestCase
{

    public function testInvalidVersion()
    {
        $testXml =  <<<STRING
<config version='2'>
  <site url='http://test.com' algorithm='HS384' encoding='plain'>
    Its always sunny in Charlottetown
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function hmacHelper($algorithm)
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='$algorithm' encoding='plain'>
    Its always sunny in Charlottetown
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(1, count($sites));
        $this->assertTrue(isset($sites['http://test.com']));
        $site = $sites['http://test.com'];
        $this->assertEquals($algorithm, $site['algorithm']);
        $this->assertEquals('Its always sunny in Charlottetown', $site['key']);
    }

    public function testOneSiteAllHmacInlineKey()
    {
        $this->hmacHelper('HS256');
        $this->hmacHelper('HS384');
        $this->hmacHelper('HS512');
    }

    public function testOneSiteHmacBase64()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='HS256' encoding='base64'>
    RG8geW91IHNlZSB0aGF0IGRvb3IgbWFya2VkIHBpcmF0ZT8=
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(1, count($sites));
        $this->assertTrue(isset($sites['http://test.com']));
        $site = $sites['http://test.com'];
        $this->assertEquals('HS256', $site['algorithm']);
        $this->assertEquals('Do you see that door marked pirate?', $site['key']);
    }

    public function testOneSiteHmacInvalidBase64()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='HS256' encoding='base64'>
    I am invalid!
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testOneSiteHmacInvalidEncoding()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='HS256' encoding='greenman'>
    RG8geW91IHNlZSB0aGF0IGRvb3IgbWFya2VkIHBpcmF0ZT8=
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testOneSiteHmacFileKey()
    {
        $dir = vfsStream::setup()->url();
        $file = $dir . DIRECTORY_SEPARATOR . "test";
        file_put_contents($file, 'lulz');

        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='HS256' encoding='plain' path="$file"/>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(1, count($sites));
        $this->assertTrue(isset($sites['http://test.com']));
        $site = $sites['http://test.com'];
        $this->assertEquals('lulz', $site['key']);
    }

    public function testOneSiteHmacInvalidFileKey()
    {
        $file = '/does/not/exist';

        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='HS256' encoding='plain' path="$file"/>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testNoKeyOrPath()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='HS256' encoding='plain'/>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testNoUrl()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site algorithm='HS256' encoding='plain'>
    foo
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testNoUrlDefault()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site algorithm='HS256' encoding='plain' default="true">
    foo
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(1, count($sites));
    }

    public function testNoUrlNotDefault()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site algorithm='HS256' encoding='plain' default="false">
    foo
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function rsaHelper($algorithm)
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='$algorithm' encoding='PEM'>
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDEVO4MNlZG+iGYhoJd/cBpfMd9
YnKsntF+zhQs8lCbBabgY8kNoXVIEeOm4WPJ+W53gLDAIg6BNrZqxk9z1TLD6Dmz
t176OLYkNoTI9LNf6z4wuBenrlQ/H5UnYl6h5QoOdVpNAgEjkDcdTSOE1lqFLIle
KOT4nEF7MBGyOSP3KQIDAQAB
-----END PUBLIC KEY-----
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(1, count($sites));
        $this->assertTrue(isset($sites['http://test.com']));
        $site = $sites['http://test.com'];
        $this->assertEquals($algorithm, $site['algorithm']);
    }

    public function testOneSiteAllRsaInlineKey()
    {
        $this->rsaHelper('RS256');
        $this->rsaHelper('RS384');
        $this->rsaHelper('RS512');
    }

    public function testRsaNotRealKey()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='RS256' encoding='PEM'>
    fake key!
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testRsaBadEncoding()
    {
        $testXml =  <<<STRING
<config version='1'>
  <site url='http://test.com' algorithm='RS256' encoding='DER'>
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDEVO4MNlZG+iGYhoJd/cBpfMd9
YnKsntF+zhQs8lCbBabgY8kNoXVIEeOm4WPJ+W53gLDAIg6BNrZqxk9z1TLD6Dmz
t176OLYkNoTI9LNf6z4wuBenrlQ/H5UnYl6h5QoOdVpNAgEjkDcdTSOE1lqFLIle
KOT4nEF7MBGyOSP3KQIDAQAB
-----END PUBLIC KEY-----
  </site>
</config>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testEmptyString()
    {
        $testXml =  <<<STRING
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }

    public function testIncorrectTags()
    {
        $testXml =  <<<STRING
<foo></foo>
STRING;
        $logger = $this->prophesize(AbstractLogger::class)->reveal();
        $parser = new SettingsParser($testXml, $logger);
        $sites = $parser->getSites();
        $this->assertEquals(0, count($sites));
    }
}
