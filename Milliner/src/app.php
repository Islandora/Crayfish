<?php

require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\IdMapper\IdMapper;
use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Converter\MillinerMiddleware;
use Islandora\Milliner\Service\MillinerService;
use Islandora\Milliner\Service\UrlMinter;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['milliner.controller'] = function () use ($app) {
    return new MillinerController(
        new MillinerService(
            FedoraApi::create($app['crayfish.fedora_base_url']),
            new IdMapper($app['db']),
            new UrlMinter($app['crayfish.fedora_base_url']),
            $app['monolog']
        ),
        $app['monolog']
    );
};
$app['milliner.middleware'] = function () use ($app) {
    return new MillinerMiddleware(
        new Client(),
        $app['monolog']
    );
};

$app->post('/jsonld/save', "milliner.controller:saveJsonld")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    })
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->getDrupalJsonld($request);
    });
$app->post('/jsonld/delete', "milliner.controller:deleteJsonld")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    });

$app->post('/binary/save', "milliner.controller:saveBinary")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    })
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->extractFileUrl($request);
    })
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->getDrupalFile($request);
    });
$app->post('/binary/delete', "milliner.controller:deleteBinary")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    })
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->extractFileUrl($request);
    });

return $app;
