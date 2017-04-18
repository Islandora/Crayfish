<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Houdini\Controller\HoudiniController;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['houdini.controller'] = function ($app) {
    return new HoudiniController(
        $app['crayfish.cmd_execute_service'],
        $app['crayfish.houdini.formats.valid'],
        $app['crayfish.houdini.formats.default'],
        $app['crayfish.houdini.executable'],
        $app['monolog']
    );
};

$app->get('/convert/{fedora_resource}', "houdini.controller:convert")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'crayfish.fedora_resource:convert');

$app->get('/identify/{fedora_resource}', "houdini.controller:identify")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'crayfish.fedora_resource:convert');

return $app;
