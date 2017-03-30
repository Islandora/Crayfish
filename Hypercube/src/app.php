<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Hypercube\Service\TesseractService;
use Islandora\Hypercube\Controller\HypercubeController;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

$config = require_once(__DIR__ . '/../cfg/cfg.php');

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app['hypercube.controller'] = function () use ($config) {
    return new HypercubeController(
        new TesseractService($config['executable'])
    );
};

$app->post('/', "hypercube.controller:post");

return $app;
