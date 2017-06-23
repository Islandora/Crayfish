<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\PathMapper\PathMapper;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Gemini\Controller\GeminiController;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['gemini.controller'] = function ($app) {
    return new GeminiController(
        new PathMapper($app['db'])
    );
};

$app->get('/fedora', "gemini.controller:getDrupalId");
$app->get('/drupal', "gemini.controller:getFedoraId");

$app->put('/fedora', "gemini.controller:upsertDrupalId");
$app->put('/drupal', "gemini.controller:upsertFedoraId");

$app->delete('/fedora', "gemini.controller:deleteFromDrupalId");
$app->delete('/drupal', "gemini.controller:deleteFromFedoraId");

return $app;
