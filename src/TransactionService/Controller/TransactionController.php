<?php

namespace Islandora\Crayfish\TransactionService\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Chullo\KeyCache\IUuidCache;

class TransactionController
{

    /**
     * @var bool $transformsInstalled
     *   Are the UUID to F4 path transforms installed.
     */
    protected static $transformsInstalled = false;
    /**
     * @var IUuidCache $keyCache
     *   The cache to store UUID -> F4 path mappings for transactions.
     */
    protected $keyCache;
    /**
     * @var string $uuidTransformKey
     *   The key used to install the transform in Fedora.
     */
    public static $uuidTransformKey = 'isl_uuid';

    public function __construct(Application $app, IUuidCache $keyCache)
    {
        $this->keyCache = $keyCache;
        if (TransactionController::$transformsInstalled === false) {
            TransactionController::installUuidTransform($app);
        }
    }

    public function create(Application $app, Request $request)
    {
        try {
            $response = $app['api']->createTransaction();
        } catch (\Exception $e) {
            $app->abort(503, 'Chullo says "Fedora4 Repository Not available"');
        }
        return $response;
    }

    public function extend(Application $app, Request $request, $id)
    {
        try {
            $response = $app['api']->extendTransaction($id);
        } catch (\Exception $e) {
            $app->abort(503, 'Chullo says "Fedora4 Repository Not available"');
        }
        return $response;
    }

    public function commit(Application $app, Request $request, $id)
    {
        try {
            $response = $app['api']->commitTransaction($id);
        } catch (\Exception $e) {
            $app->abort(503, 'Chullo says "Fedora4 Repository Not available"');
        }
        return $response;
    }

    public function rollback(Application $app, Request $request, $id)
    {
        try {
            $response = $app['api']->rollbackTransaction($id);
        } catch (\Exception $e) {
            $app->abort(503, 'Chullo says "Fedora4 Repository Not available"');
        }
        return $response;
    }

    /**
   * Parse a response to get the transaction ID.
   *
   * @param  $response
   *   Either a Symfony or Guzzle/Psr7 response.
   * @return string
   *   The transaction ID.
   */
    public function getId($response)
    {
        if (get_class($response) == 'Symfony\Component\HttpFoundation\Response') {
            if ($response->headers->has('location')) {
                return $this->parseTransactionId($response->headers->get('location'));
            }
        }
        if (get_class($response) == 'GuzzleHttp\Psr7\Response') {
            if ($response->hasHeader('location')) {
                return $this->parseTransactionId($response->getHeader('location'));
            }
        }
        return null;
    }

    /**
   * Utility function to get the transaction ID from the Header.
   *
   * @param  array|string $header
   *   array of headers or the single string.
   * @return string
   *   the transaction ID.
   */
    private function parseTransactionId($header)
    {
        if (is_array($header)) {
            $header = reset($header);
        }
        $ids = explode('tx:', $header);
        return 'tx:' . $ids[1];
    }

    /**
     * Install the UUID transforms into Fedora
     *
     * @var Application $app
     *     The Silex webapp object.
     */
    protected static function installUuidTransform(Application $app)
    {
        $loadTransform = false;
        if (TransactionController::$transformsInstalled === false) {
            $response = $app['api']->getResourceHeaders(
                "fedora:system/fedora:transform/fedora:ldpath/" . TransactionController::$uuidTransformKey
            );
            if ($response->getStatusCode() == 200) {
                // This variable is reset at server restart, no need to re-upload the transform.
                TransactionController::$transformsInstalled = true;
                return true;
            } else {
                $response = $app['api']->getResourceHeaders("fedora:system/fedora:transform");
                if ($response->getStatusCode() == 404) {
                    $path = $app['UuidGenerator']->generateV4();
                    $response = $app['api']->saveResource("{$path}");
                    if ($response->getStatusCode() == 201) {
                        $response = $app['api']->getResourceHeaders("/{$path}/fcr:transform/default");
                        if ($response->getStatusCode() == 200) {
                            $loadTransform = true;
                        }
                        # Delete the temporary resource we created
                        $response = $app['api']->deleteResource("/{$path}");
                        if ($response->getStatusCode() == 204 || $response->getStatusCode() == 410) {
                            $response = $app['api']->deleteResource("/{$path}/fcr:tombstone");
                        }
                    }
                } else {
                    $loadTransform = true;
                }
                if ($loadTransform) {
                    $ldpath_content = file_get_contents(__DIR__ . '/../resources/islandora_uuid.txt');
                    if ($ldpath_content !== false) {
                        $url = '/fedora:system/fedora:transform/fedora:ldpath/' .
                        TransactionController::$uuidTransformKey . '/fedora:Resource';
                        $response = $app['api']->saveResource(
                            $url,
                            $ldpath_content,
                            array('Content-type' => 'application/rdf+ldpath')
                        );
                        if ($response->getStatusCode() == 201) {
                            TransactionController::$transformsInstalled = true;
                            return true;
                        }
                    }
                }
                error_log("Unable to load transaction transforms into Fedora.");
                return false;
            }
        }
        return true;
    }
}
