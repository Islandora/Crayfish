<?php

namespace Islandora\Crayfish\Commons\Syn\tests;

use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Pimple\Container;
use InvalidArgumentException;

class YamlConfigServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function testYamlParsing()
    {
        $dir = vfsStream::setup()->url();
        $file = $dir . DIRECTORY_SEPARATOR . "test_config.yaml";
        $yaml = <<<YAML
---
test:
  this:
    - 1
    - 2
    - 3
  that: wowza
another:
  foo: bar
YAML;
        file_put_contents($file, $yaml);
        $parser = new YamlConfigServiceProvider($file);
        $container = new Container();
        $parser->register($container);
        $this->assertEquals([1, 2, 3], $container['crayfish.test.this']);
        $this->assertEquals('wowza', $container['crayfish.test.that']);
        $this->assertEquals('bar', $container['crayfish.another.foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testYamlNoFile()
    {
        $parser = new YamlConfigServiceProvider('/does/not/exist');
        $container = new Container();
        $parser->register($container);
    }
}
