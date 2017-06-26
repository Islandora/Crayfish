<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\IdMapper\IdMapper;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Gemini\Controller\GeminiController;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['gemini.controller'] = function ($app) {
    return new GeminiController(
        new IdMapper($app['db'])
    );
};

$app->get('/metadata', "gemini.controller:getMetadataId");
$app->get('/binary', "gemini.controller:getBinaryId");

$app->put('/metadata', "gemini.controller:saveMetadataId");
$app->put('/binary', "gemini.controller:saveBinaryId");

$app->delete('/metadata', "gemini.controller:deleteMetadataId");
$app->delete('/binary', "gemini.controller:deleteBinaryId");

return $app;
