<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Homarus\Controller\HomarusController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;


$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));

$app['homarus.controller'] = function ($app) {
  return new HomarusController(
    $app['crayfish.cmd_execute_service'],
    $app['crayfish.homarus.mime_types.valid'],
    $app['crayfish.homarus.mime_types.default_video'],
    $app['crayfish.homarus.executable'],
    $app['monolog'],
    $app['crayfish.homarus.mime_to_format']
  );
};

$app->options('/convert', "homarus.controller:convertOptions");
$app->get('/convert', "homarus.controller:convert");

return $app;
