<?php

require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\PathMapper\PathMapper;
use Islandora\Crayfish\Commons\IslandoraServiceProvider;
use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Converter\DrupalEntityConverter;
use Islandora\Milliner\Service\MillinerService;
use Silex\Application;

$config = require_once(__DIR__ . '/../cfg/cfg.php');

$app = new Application();

$app->register(new IslandoraServiceProvider($config));

$app['milliner.controller'] = function () use ($config, $app) {
    return new MillinerController(
        new MillinerService(
            FedoraApi::create($config['fedora base url']),
            new PathMapper($app['db']),
            $app['monolog']
        ),
        $app['monolog']
    );
};
$app['drupal_entity.converter'] = function () use ($config, $app) {
    return new DrupalEntityConverter(
        new Client(['base_uri' => $config['drupal base url']]),
        $app['monolog']
    );
};

$app->post('/metadata/{drupal_entity}', "milliner.controller:create")
    ->assert('drupal_entity', '.+')
    ->convert('drupal_entity', 'drupal_entity.converter:convert');

$app->put('/metadata/{drupal_entity}', "milliner.controller:update")
    ->assert('drupal_entity', '.+')
    ->convert('drupal_entity', 'drupal_entity.converter:convert');

$app->delete('/metadata/{drupal_path}', "milliner.controller:delete")
    ->assert('drupal_path', '.+');

return $app;
