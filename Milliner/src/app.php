<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\PathMapper\PathMapper;
use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Converter\DrupalEntityConverter;
use Islandora\Milliner\Service\MillinerService;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['milliner.controller'] = function () use ($app) {
    return new MillinerController(
        new MillinerService(
            FedoraApi::create($app['crayfish.fedora_base_url']),
            new PathMapper($app['db']),
            $app['monolog']
        ),
        $app['monolog']
    );
};
$app['drupal_entity.converter'] = function () use ($app) {
    return new DrupalEntityConverter(
        $app['drupal.client'],
        $app['monolog']
    );
};

$app->post('/rdf/{drupal_entity}', "milliner.controller:createRdf")
    ->assert('drupal_entity', '.+')
    ->convert('drupal_entity', 'drupal_entity.converter:convertJsonld');

$app->put('/rdf/{drupal_entity}', "milliner.controller:updateRdf")
    ->assert('drupal_entity', '.+')
    ->convert('drupal_entity', 'drupal_entity.converter:convertJsonld');

$app->delete('/{drupal_path}', "milliner.controller:delete")
    ->assert('drupal_path', '.+');

$app->post('/binary/{drupal_entity}', "milliner.controller:createBinary")
    ->assert('drupal_entity', '.+')
    ->convert('drupal_entity', 'drupal_entity.converter:convert');

$app->put('/binary/{drupal_entity}', "milliner.controller:updateBinary")
    ->assert('drupal_entity', '.+')
    ->convert('drupal_entity', 'drupal_entity.converter:convert');

return $app;
