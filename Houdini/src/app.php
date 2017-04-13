<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\IslandoraServiceProvider;
use Islandora\Houdini\Controller\HoudiniController;
use Silex\Application;

$config = require_once(__DIR__ . '/../cfg/cfg.php');
$app = new Application();
$app->register(new IslandoraServiceProvider($config));

$app['houdini.controller'] = function ($app) use ($config) {
    return new HoudiniController(
        $app['crayfish.cmd_execute_service'],
        $config['valid formats'],
        $config['default format'],
        $config['executable'],
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
