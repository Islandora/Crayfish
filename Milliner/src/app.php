<?php

require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\EntityMapper\EntityMapper;
use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Service\MillinerService;
use Pimple\Exception\UnknownIdentifierException;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['debug'] = $app['crayfish.debug'];

$app['milliner.controller'] = function () use ($app) {
    try {
        $strip_format_jsonld = filter_var(
            $app['crayfish.strip_format_jsonld'],
            FILTER_VALIDATE_BOOLEAN
        );
    } catch (UnknownIdentifierException $e) {
        $strip_format_jsonld = false;
    }

    try {
        $fedora6 = filter_var(
            $app['crayfish.fedora6'],
            FILTER_VALIDATE_BOOLEAN
        );
    } catch (UnknownIdentifierException $e) {
        $fedora6 = false;
    }

    return new MillinerController(
        new MillinerService(
            FedoraApi::create($app['crayfish.fedora_base_url']),
            new Client(),
	    new EntityMapper(),
            $app['monolog'],
            $app['crayfish.modified_date_predicate'],
            $strip_format_jsonld,
            $fedora6
        ),
        $app['monolog']
    );
};

$app->post('/node/{uuid}', "milliner.controller:saveNode");
$app->delete('/node/{uuid}', "milliner.controller:deleteNode");
$app->post('/node/{uuid}/version', "milliner.controller:createNodeVersion");
$app->post('/media/{source_field}', "milliner.controller:saveMedia");
$app->post('/media/{source_field}/version', 'milliner.controller:createMediaVersion');
$app->post('/external/{uuid}', "milliner.controller:saveExternal");

return $app;
