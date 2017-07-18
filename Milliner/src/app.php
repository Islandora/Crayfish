<?php

require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Milliner\Client\GeminiClient;
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

$app->post('/content', "milliner.controller:saveContent");
$app->post('/media', "milliner.controller:saveMedia");
$app->post('/file', "milliner.controller:saveFile");
$app->delete('/resource/{uuid}', "milliner.controller:delete");

return $app;
