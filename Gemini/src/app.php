<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\PathMapper\PathMapper;
use Islandora\Gemini\Controller\GeminiController;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;

$config = require_once(__DIR__ . '/../cfg/cfg.php');

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new DoctrineServiceProvider(), ['db.options' => $config['db.options']]);

$app['gemini.controller'] = function () use ($app) {
    return new GeminiController(
        new PathMapper($app['db'])
    );
};

$app->get('/drupal/{drupal_path}', "gemini.controller:getFedoraPath")
    ->assert("drupal_path", ".+");
$app->get('/fedora/{fedora_path}', "gemini.controller:getDrupalPath")
    ->assert("fedora_path", ".+");
$app->post('/', "gemini.controller:createPair");
$app->delete("/drupal/{drupal_path}", "gemini.controller:deleteFromDrupalPath")
    ->assert("drupal_path", ".+");
$app->delete("/fedora/{fedora_path}", "gemini.controller:deleteFromFedoraPath")
    ->assert("fedora_path", ".+");

return $app;
