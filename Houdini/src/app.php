<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Chullo\FedoraApi;
use Islandora\Crayfish\Commons\FedoraResourceConverter;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Crayfish\Commons\Syn\SettingsParser;
use Islandora\Crayfish\Commons\Syn\JwtAuthenticator;
use Islandora\Crayfish\Commons\Syn\JwtFactory;
use Islandora\Houdini\Controller\HoudiniController;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

$config = require_once(__DIR__ . '/../cfg/cfg.php');

$app = new Application();

// Only use logger if configured
if (strtolower($config['loglevel']) === 'none') {
    $app['monolog'] = function () {
        return new Logger('null', [new NullHandler()]);
    };
} else {
    $app->register(new MonologServiceProvider(), [
        'monolog.logfile' => $config['logfile'],
        'monolog.level' => $config['loglevel'],
        'monolog.name' => 'Houdini',
    ]);
}

$app->register(new ServiceControllerServiceProvider());

$app['houdini.controller'] = function ($app) use ($config) {
    return new HoudiniController(
        new CmdExecuteService($app['monolog']->withName('CmdExecuteService')),
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

$app['syn.settings_parser'] = function ($app) use ($config) {
    $xml = file_get_contents($config['users config']);
    return new SettingsParser(
        $xml,
        $app['monolog']->withName('syn.settings_parser')
    );
};

$app['syn.jwt_authentication'] = function ($app) {
    return new JwtAuthenticator(
        $app['syn.settings_parser'],
        new JwtFactory(),
        $app['monolog']->withName('syn.jwt_authentication')
    );
};

if ($config['security enabled']) {
    $app->register(new SecurityServiceProvider());
    $app['security.firewalls'] = [
      'default' => [
        'stateless' => true,
        'anonymous' => false,
        'guard' => [
          'authenticators' => [
            'syn.jwt_authentication'
          ],
        ],
      ],
    ];
}

$app->get('/convert/{fedora_resource}', "houdini.controller:convert")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'fedora_resource.converter:convert');

$app->get('/identify/{fedora_resource}', "houdini.controller:identify")
    ->assert('fedora_resource', '.+')
    ->convert('fedora_resource', 'fedora_resource.converter:convert');

return $app;
