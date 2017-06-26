<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\UrlMapper\UrlMapper;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Gemini\Controller\GeminiController;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['gemini.controller'] = function ($app) {
    return new GeminiController(
        new UrlMapper($app['db'])
    );
};

$app->get('/{uuid}', "gemini.controller:get");

$app->put('/{uuid}', "gemini.controller:put");

$app->delete('/{uuid}', "gemini.controller:delete");

return $app;
