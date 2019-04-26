<?php

namespace Islandora\Crayfish\Commons\Syn\tests;

use Doctrine\DBAL\Connection;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Crayfish\Commons\ApixMiddleware;
use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Syn\JwtAuthenticator;
use Islandora\Crayfish\Commons\Syn\SettingsParser;
use Monolog\Logger;
use PHPUnit_Framework_TestCase;
use Silex\Application;

class IslandoraServiceProviderTest extends PHPUnit_Framework_TestCase
{
    protected $container;

    public function setup()
    {
        $islandora = new IslandoraServiceProvider();
        $container = new Application();
        $islandora->register($container);
        $this->container = $container;
    }

    public function testMonolog()
    {
        $this->container['crayfish.log.file'] = 'test';
        $this->container['crayfish.log.level'] = 'debug';
        $this->assertInstanceOf(Logger::class, $this->container['monolog']);
    }

    public function testDoctrineOptions()
    {
        $this->container['crayfish.db.options.foo'] = 'bar';
        $this->assertArrayHasKey('foo', $this->container['db.options']);
        $this->assertEquals('bar', $this->container['db.options']['foo']);
    }

    public function testDoctrine()
    {
        // doctrine uses logger
        $this->container['crayfish.log.level'] = 'none';
        $this->assertInstanceOf(Connection::class, $this->container['db']);
    }

    public function testSecurityEnable()
    {
        $this->container['crayfish.syn.enable'] = true;
        $this->assertArrayHasKey('default', $this->container['security.firewalls']);
    }

    public function testSecurityDisable()
    {
        $this->container['crayfish.syn.enable'] = false;
        $this->assertEquals([], $this->container['security.firewalls']);
    }

    public function testCmdExecute()
    {
        // Uses log
        $this->container['crayfish.log.level'] = 'none';
        $this->assertInstanceOf(CmdExecuteService::class, $this->container['crayfish.cmd_execute_service']);
    }

    public function testApixMiddleware()
    {
        $this->container['crayfish.log.level'] = 'none';
        $this->container['crayfish.fedora_resource.base_url'] = 'http://localhost:8080/fcrepo/rest';
        $this->assertInstanceOf(ApixMiddleware::class, $this->container['crayfish.apix_middleware']);
    }

    public function testSyn()
    {
        // Uses log
        $this->container['crayfish.log.level'] = 'none';

        // Syn variables
        $this->container['crayfish.syn.config'] = '';

        $this->assertInstanceOf(SettingsParser::class, $this->container['crayfish.syn.settings_parser']);
        $this->assertInstanceOf(JwtAuthenticator::class, $this->container['crayfish.syn.jwt_authentication']);
    }
}
