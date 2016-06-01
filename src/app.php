<?php
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Psr\Http\Message\ResponseInterface;
use Silex\Provider\TwigServiceProvider;
use Islandora\Crayfish\Provider\CrayfishProvider;

date_default_timezone_set('UTC');

$app = new Application();

$app['debug'] = true;
$app['islandora.BasePath'] = __DIR__;
$app->register(new \Silex\Provider\ServiceControllerServiceProvider());
// TODO: Not register all template directories right now.
$app->register(new \Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => array(
    __DIR__ . '/ResourceService/templates',
  ),
));

$crayfishProvider = new CrayfishProvider();

$app->register($crayfishProvider);
$app->mount("/islandora", $crayfishProvider);

/**
 * Convert returned Guzzle responses to Symfony responses, type hinted.
 */
$app->view(function (ResponseInterface $psr7) {
    return new Response($psr7->getBody(), $psr7->getStatusCode(), $psr7->getHeaders());
});

$app->after(
    function (Request $request, Response $response, Application $app) {
        // Todo a closing controller, not sure what now but i had an idea.
    }
);

return $app;
