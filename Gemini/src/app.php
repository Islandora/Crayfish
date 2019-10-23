<?php

require_once __DIR__.'/../vendor/autoload.php';

use Islandora\Crayfish\Commons\Provider\IslandoraServiceProvider;
use Islandora\Gemini\UrlMapper\UrlMapper;
use Islandora\Gemini\UrlMinter\UrlMinter;
use Islandora\Crayfish\Commons\Provider\YamlConfigServiceProvider;
use Islandora\Gemini\Controller\GeminiController;
use Silex\Application;

$app = new Application();

$app->register(new IslandoraServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../cfg/config.yaml'));
$app['debug'] = $app['crayfish.debug'];
$app['gemini.mapper'] = function ($app) {
    return new UrlMapper($app['db']);
};
$app['gemini.minter'] = function ($app) {
    return new UrlMinter();
};
$app['gemini.controller'] = function ($app) {
    return new GeminiController(
        $app['gemini.mapper'],
        $app['gemini.minter'],
        $app['url_generator']
    );
};

$app->get('/by_uri', "gemini.controller:getByUri");

$app->get('/{uuid}', "gemini.controller:get");

$app->post('/', "gemini.controller:post");

$app->put('/{uuid}', "gemini.controller:put");

$app->delete('/{uuid}', "gemini.controller:delete");

return $app;
