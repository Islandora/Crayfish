<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\IslandoraServiceProvider;
use Islandora\Hypercube\Controller\HypercubeController;
use Silex\Application;

$config = require_once(__DIR__ . '/../cfg/cfg.php');
$app = new Application();
$app->register(new IslandoraServiceProvider($config));

$app['hypercube.controller'] = function () use ($app, $config) {
    return new HypercubeController(
        $app['crayfish.cmd_execute_service'],
        $config['executable']
    );
};

$app->get('/{fedora_resource}', "hypercube.controller:get")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'crayfish.fedora_resource:convert');

return $app;
