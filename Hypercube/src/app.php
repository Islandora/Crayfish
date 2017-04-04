<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\FedoraResourceConverter;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Hypercube\Controller\HypercubeController;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

$config = require_once(__DIR__ . '/../cfg/cfg.php');

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app['hypercube.controller'] = function () use ($config) {
    return new HypercubeController(
        new CmdExecuteService(),
        $config['executable']
    );
};
$app['fedora_resource.converter'] = function () use ($config) {
    return new FedoraResourceConverter(
        FedoraApi::create($config['fedora base url'])
    );
};

$app->get('/{fedora_resource}', "hypercube.controller:get")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'fedora_resource.converter:convert');

return $app;
