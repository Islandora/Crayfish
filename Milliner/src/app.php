<?php

require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\UrlMapper\UrlMapper;
use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Milliner\Controller\MillinerController;
use Islandora\Milliner\Middleware\AS2Middleware;
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
            new UrlMapper($app['db']),
            new UrlMinter($app['crayfish.fedora_base_url']),
            $app['monolog']
        ),
        $app['monolog']
    );
};
$app['milliner.middleware'] = function () use ($app) {
    return new AS2Middleware(
        new Client(),
        $app['monolog']
    );
};

$app->post('/save/rdf', "milliner.controller:saveRdf")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    })
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->getrdf($request);
    });
$app->post('/delete/rdf', "milliner.controller:deleteRdf")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    });

$app->post('/save/nonrdf', "milliner.controller:saveNonRdf")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    })
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->getRdf($request);
    })
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->getNonRdf($request);
    });
$app->post('/delete/nonrdf', "milliner.controller:deleteNonRdf")
    ->before(function (Request $request, Application $app) {
        return $app['milliner.middleware']->parseEvent($request);
    });

return $app;
