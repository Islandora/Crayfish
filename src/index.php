<?php

namespace Islandora\Crayfish;

require_once __DIR__.'/app.php';

// Stuff not needed for the tests.
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
