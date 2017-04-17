<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Hypercube\Controller\HypercubeController;
use Silex\Application;

$app = new Application();
$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider('../cfg/config.yaml'));

$app['hypercube.controller'] = function ($app) {
    return new HypercubeController(
        $app['crayfish.cmd_execute_service'],
        $app['crayfish.hypercube.executable']
    );
};

$app->get('/{fedora_resource}', "hypercube.controller:get")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'crayfish.fedora_resource:convert');

return $app;
