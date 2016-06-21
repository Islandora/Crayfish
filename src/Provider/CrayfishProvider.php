<?php

namespace Islandora\Crayfish\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Islandora\Chullo\Uuid\UuidGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Yaml\Yaml;
use Islandora\Crayfish\ResourceService\Controller\ResourceController;
use Islandora\Crayfish\TransactionService\Controller\TransactionController;
use Islandora\Crayfish\KeyCache\UuidCache;

class CrayfishProvider implements ServiceProviderInterface, ControllerProviderInterface
{
    /**
     * Part of ServiceProviderInterface
     */
    public function register(Application $app)
    {
        //
        // Define controller services
        //
        //This is the base path for the application. Used to change the location
        //of yaml config files when registerd somewhere else
        if (!isset($app['islandora.BasePath'])) {
            $app['islandora.BasePath'] = __DIR__.'/..';
        }
        
        # Register the ResourceService
        $app['islandora.resourcecontroller'] = $app->share(
            function () use ($app) {
                return new ResourceController($app);
            }
        );
        
        # Register the TransactionService
        $app['islandora.transactioncontroller'] = $app->share(
            function () use ($app) {
                return new TransactionController($app, $app['islandora.keyCache']);
            }
        );
        
        # If the Cache has not been defined we can't start.
        if (!isset($app['islandora.keyCache'])) {
            if (!isset($app['cache']) || !is_a($app['cache'], 'Moust\Silex\Cache\AbstractCache') ||
            is_a($app['cache'], 'Moust\Silex\Cache\ArrayCache')) {
                $app->abort(500, "Cache has not been registered or is not an instance of Moust\Silex\Cache\AbstractCache, but not an ArrayCache.");
            } elseif (isset($app['cache'])) {
                // Setup our UuidCache.
                $app['islandora.keyCache'] = new UuidCache($app['cache']);
            }
        }
        
        if (!isset($app['twig'])) {
            $app['twig'] = $app->share(
                $app->extend(
                    'twig',
                    function (
                        $twig,
                        $app
                    ) {
                        return $twig;
                    }
                )
            );
        } else {
            # Add our templates to the existing twig instance.
            $app['twig.loader']->addLoader(new \Twig_Loader_Filesystem(__DIR__ . '/../ResourceService/templates'));
        }
        if (!isset($app['api'])) {
            $app['api'] =  $app->share(
                function () use ($app) {
                    return FedoraApi::create(
                        $app['config']['islandora']['fedoraProtocol'].'://'
                        .$app['config']['islandora']['fedoraHost']
                        .$app['config']['islandora']['fedoraPath']
                    );
                }
            );
        }
        if (!isset($app['triplestore'])) {
            $app['triplestore'] = $app->share(
                function () use ($app) {
                    return TriplestoreClient::create(
                        $app['config']['islandora']['tripleProtocol'].
                        '://'.$app['config']['islandora']['tripleHost'].
                        $app['config']['islandora']['triplePath']
                    );
                }
            );
        }
        # Register a UUID generator
        if (!isset($app['UuidGenerator'])) {
        //made shared, only need to make one instance of the base uuid
            $app['UuidGenerator'] = $app->share(
                function () use ($app) {
                    return new UuidGenerator();
                }
            );
        }
        /**
         * Ultra simplistic YAML settings loader.
         */
        if (!isset($app['config'])) {
            $app['config'] = $app->share(
                function () use ($app) {
                    if ($app['debug']) {
                        $configFile = $app['islandora.BasePath'].'/../config/settings.dev.yml';
                    } else {
                        $configFile = $app['islandora.BasePath'].'/../config/settings.yml';
                    }
                    $settings = Yaml::parse(file_get_contents($configFile));
                    return $settings;
                }
            );
        }
        /**
         * Make our middleware callback functions protected
         */
        /**
         * before middleware to handle browser requests.
         */
        $app['islandora.htmlHeaderToTurtle'] = $app->protect(
            function (Request $request) {
                // In case the request was made by a browser, avoid
                // returning the whole Fedora4 API Rest interface page.
                if (0 === strpos($request->headers->get('Accept'), 'text/html')) {
                    $request->headers->set('Accept', 'text/turtle', true);
                }
            }
        );


        /**
         * Before middleware to normalize host header to same as fedora's running instance.
         */
        $app['islandora.hostHeaderNormalize'] = $app->protect(
            function (Request $request) use ($app) {
                // Normalize Host header to Repo's real location
                $request->headers->set('Host', $app['config']['islandora']['fedoraHost'], true);
            }
        );

        /**
         * Converts request $id (uuid) into a fedora4 resourcePath
         */
        $app['islandora.idToUri'] = $app->protect(
            function ($id, Request $request) use ($app) {
                // Run only if $id given /can also be refering root resource,
                // we accept only UUID V4 or empty
                if (null != $id) {
                    $sparql_query = $app['twig']->render('getResourceByUUIDfromTS.sparql', array('uuid' => $id));
                    try {
                        $sparql_result = $app['triplestore']->query($sparql_query);
                    } catch (\Exception $e) {
                        $app->abort(503, 'Chullo says "Triple Store Not available"');
                    }
                    // We only assign one in case of multiple ones
                    // Will have to check for edge cases?
                    foreach ($sparql_result as $triple) {
                        return $triple->s->getUri();
                    }
                    // If we didn't find the path in the triplestore
                    // and we have a transaction id, we should check in
                    // the UuidKeyCache.
                    $tx = $request->query->get('tx', "");
                    if (isset($tx) && !empty($tx)) {
                        $path = $app['islandora.keyCache']->getByUuid($tx, $id);
                        if (!is_null($path)) {
                            return $path;
                        }
                    }
                    // Abort the routes if we don't get a subject from the tripple.
                    $app->abort(404, sprintf('Failed getting resource Path for "%s" from triple store', $id));
                } else {
                    // If $id is empty then assume we are dealing with fedora base rest endpoint
                    return $app['config']['islandora']['fedoraProtocol']
                      .'://'.$app['config']['islandora']['fedoraHost']
                      .$app['config']['islandora']['fedoraPath'];
                }
            }
        );
  
    }

    /**
     * Inherits from ServiceProviderInterface
     *
     * @codeCoverageIgnore
     */
    public function boot(Application $app)
    {
    }

    /**
     * Part of ControllerProviderInterface
     *
     * @codeCoverageIgnore
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        //
        // Define routing referring to controller services
        //
        $controllers
            ->before($app['islandora.hostHeaderNormalize'])
            ->before($app['islandora.htmlHeaderToTurtle'])
            ->value('id', "");
    
        # ResourceService routes.
        $controllers->get("/resource/{id}/{child}", "islandora.resourcecontroller:get")
            ->convert('id', $app['islandora.idToUri'])
            ->assert('id', $app['config']['islandora']['resourceIdRegex'])
            ->value('child', "")
            ->bind('islandora.resourceGet');
        $controllers->post("/resource/{id}", "islandora.resourcecontroller:post")
            ->convert('id', $app['islandora.idToUri'])
            ->assert('id', $app['config']['islandora']['resourceIdRegex'])
            ->bind('islandora.resourcePost');
        $controllers->put("/resource/{id}/{child}", "islandora.resourcecontroller:put")
            ->convert('id', $app['islandora.idToUri'])
            ->assert('id', $app['config']['islandora']['resourceIdRegex'])
            ->value('child', "")
            ->bind('islandora.resourcePut');
        $controllers->patch("/resource/{id}/{child}", "islandora.resourcecontroller:patch")
            ->convert('id', $app['islandora.idToUri'])
            ->assert('id', $app['config']['islandora']['resourceIdRegex'])
            ->value('child', "")
            ->bind('islandora.resourcePatch');
        $controllers->delete("/resource/{id}/{child}", "islandora.resourcecontroller:delete")
            ->convert('id', $app['islandora.idToUri'])
            ->assert('id', $app['config']['islandora']['resourceIdRegex'])
            ->value('child', "")
            ->bind('islandora.resourceDelete');
        
        # TransactionService routes.
        $controllers->get("/transaction/{id}", "islandora.resourcecontroller:get")
            ->value('id', "")
            ->value('child', "")
            ->before(
                function (Request $request) {
                    if (isset($request->attributes) && $request->attributes->has('id')) {
                        // To get this to work we need to GET /islandora/resource//tx:id
                        // So we move the $id to the $child parameter.
                        $id = $request->attributes->get('id');
                        $request->attributes->set('child', $id);
                        $request->attributes->set('id', '');
                    }
                }
            )
            ->convert('id', $app['islandora.idToUri'])
        ->bind('islandora.transactionGet');

        $controllers->post("/transaction", "islandora.transactioncontroller:create")
            ->bind('islandora.transactionCreate');

        $controllers->post("/transaction/{id}/extend", "islandora.transactioncontroller:extend")
            ->value('id', "")
            ->bind('islandora.transactionExtend');

        $controllers->post("/transaction/{id}/commit", "islandora.transactioncontroller:commit")
            ->value('id', "")
            ->bind('islandora.transactionCommit');

        $controllers->post("/transaction/{id}/rollback", "islandora.transactioncontroller:rollback")
            ->value('id', "")
            ->bind('islandora.transactionRollback');
        
        return $controllers;
    }
}
