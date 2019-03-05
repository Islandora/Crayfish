<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Islandora\Recast\Controller\RecastController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$gc = GeminiClient::create(
    $app['crayfish.gemini_base_url'],
    $app['monolog']
);

$test = new RecastController(
    $gc,
    $app['monolog']
);

$app['recast.controller'] = $test;

$app->options('/', 'recast.controller:recastOptions');
$app->get('/{operation}', "recast.controller:recast")
  ->before(function (Request $request, Application $app) {
    return $app['crayfish.apix_middleware']->before($request);
  })
  ->value('operation', 'add');

return $app;
