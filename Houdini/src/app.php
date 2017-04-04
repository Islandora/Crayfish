<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\FedoraResourceConverter;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Houdini\Controller\HoudiniController;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\MonologServiceProvider;

$config = require_once(__DIR__ . '/../cfg/cfg.php');

$app = new Application();

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => $config['logfile'],
    'monolog.level' => $config['loglevel'],
    'monolog.name' => 'Houdini',
]);

$app->register(new ServiceControllerServiceProvider());

$app['houdini.controller'] = function () use ($config, $app) {
    return new HoudiniController(
        new CmdExecuteService($app['monolog']),
        $config['valid formats'],
        $config['default format'],
        $config['executable'],
        $app['monolog']
    );
};
$app['fedora_resource.converter'] = function () use ($config) {
    return new FedoraResourceConverter(
        FedoraApi::create($config['fedora base url'])
    );
};

$app->get('/convert/{fedora_resource}', "houdini.controller:convert")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'fedora_resource.converter:convert');

$app->get('/identify/{fedora_resource}', "houdini.controller:identify")
  ->assert('fedora_resource', '.+')
  ->convert('fedora_resource', 'fedora_resource.converter:convert');

return $app;
