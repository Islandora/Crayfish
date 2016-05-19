<?php

namespace Islandora\Crayfish;

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Psr\Http\Message\ResponseInterface;
use Silex\Provider\TwigServiceProvider;
use Islandora\Crayfish\Provider\CrayfishProvider;
use Islandora\Crayfish\ResourceService\Provider\UUIDServiceProvider;

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
$app->register(new UUIDServiceProvider());

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

$app->error(
    function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $code) use ($app) {
        if ($app['debug']) {
            return;
        }
        return new Response(
            sprintf(
                'Islandora Resource Service exception: %s / HTTP %d response',
                $e->getMessage(),
                $code
            ),
            $code
        );
    }
);

$app->error(
    function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $code) use ($app) {
        if ($app['debug']) {
            return;
        }
        //Not sure what the best "verbose" message is
        return new Response(
            sprintf(
                'Islandora Resource Service exception: %s / HTTP %d response',
                $e->getMessage(),
                $code
            ),
            $code
        );
    }
);

$app->error(
    function (\Exception $e, $code) use ($app) {
        if ($app['debug']) {
            return;
        }
        return new Response(
            sprintf(
                'Islandora Resource Service uncatched exception: %s %d response',
                $e->getMessage(),
                $code
            ),
            $code
        );
    }
);


$app->run();
