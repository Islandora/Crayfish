<?php

require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Milliner\Gemini\GeminiClient;
use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Service\MillinerService;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['debug'] = $app['crayfish.debug'];

$app['milliner.controller'] = function () use ($app) {
    return new MillinerController(
        new MillinerService(
            FedoraApi::create($app['crayfish.fedora_base_url']),
            new Client(),
            GeminiClient::create(
                $app['crayfish.gemini_base_url'],
                $app['monolog']
            ),
            $app['monolog'],
            $app['crayfish.modified_date_predicate']
        ),
        $app['monolog']
    );
};

$uuid_regex = "/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i";

$app->post('/node/{uuid}', "milliner.controller:saveNode");
$app->delete('/node/{uuid}', "milliner.controller:deleteNode");
$app->post('/media/{source_field}', "milliner.controller:saveMedia");

return $app;
