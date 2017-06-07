<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Hypercube\Controller\HypercubeController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();
$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['hypercube.controller'] = function ($app) {
    return new HypercubeController(
        $app['crayfish.cmd_execute_service'],
        $app['crayfish.hypercube.executable']
    );
};

$app->get('/', "hypercube.controller:get")
    ->before(function (Request $request, Application $app) {
        return $app['crayfish.apix_middleware']->before($request);
    });

return $app;
