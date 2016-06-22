<?php

namespace Islandora\Crayfish\ResourceService\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Islandora\Crayfish\TransactionService\Controller\TransactionController;

class ResourceController
{
    /**
     * Resource GET controller takes $id (valid UUID or empty) as first value to match, optional a child resource path
     * takes 'rx' and/or 'metadata' as optional query arguments
     *
     * @see
     * https://wiki.duraspace.org/display/FEDORA40/RESTful+HTTP+API#RESTfulHTTPAPI-GETRetrievethecontentoftheresource
     */
    public function get(Application $app, Request $request, $id, $child)
    {
        $tx = $request->query->get('tx', "");
        $metadata = $request->query->get('metadata', false) ? '/fcr:metadata' : "";
        try {
            $response = $app['api']->getResource(
                $app->escape($id) . '/' . $child . $metadata,
                $request->headers->all(),
                $tx
            );
        } catch (\Exception $e) {
            $app->abort(503, 'Chullo says "Fedora4 Repository Not available"');
        }
        return $response;
    }

    /**
     * Resource POST route controller. takes $id (valid UUID or empty) for the parent resource as first value to match
     * takes 'rx' and/or 'checksum' as optional query arguments
     *
     * @see https://wiki.duraspace.org/display/FEDORA4x/RESTful+HTTP+API (Create new resources within a LDP container)
     */
    public function post(Application $app, Request $request, $id)
    {
        $tx = $request->query->get('tx', "");
        $checksum = $request->query->get('checksum', "");
        try {
            $response = $app['api']->createResource(
                $app->escape($id),
                $request->getContent(),
                $request->headers->all(),
                $tx,
                $checksum
            );
        } catch (\Exception $e) {
            $app->abort(
                503,
                '"Chullo says Fedora4 Repository is Not available"'
            );
        }
        if (!empty($tx)) {
            // If we are in a transaction store the UUID -> path.
            $headers = $response->getHeader('Location');
            $returnID = is_array($headers) ? reset($headers) : $headers;
            if ($returnID !== false) {
                $this->storeUuid($app, $returnID, $tx);
            }
        }
        return $response;
    }

    /**
     * Resource PUT route. takes $id (valid UUID or empty) for the resource to be update/created
     * as first value to match, optional a Child resource relative path
     * takes 'rx' and/or 'checksum' as optional query arguments
     *
     * @see https://wiki.duraspace.org/display/FEDORA4x/RESTful+HTTP+API (Create a resource with a specified path...)
     */
    public function put(Application $app, Request $request, $id, $child)
    {
        $tx = $request->query->get('tx', "");
        $checksum = $request->query->get('checksum', "");
        try {
            $response = $app['api']->saveResource(
                $app->escape($id) . '/' . $child,
                $request->getContent(),
                $request->headers->all(),
                $tx,
                $checksum
            );
        } catch (\Exception $e) {
            $app->abort(503, '"Chullo says Fedora4 Repository is Not available"');
        }
        if (!empty($tx)) {
            // If we are in a transaction store the UUID -> path.
            $this->storeUuid($app, $id . '/' . $child, $tx);
        }
        return $response;
    }


    public function patch(Application $app, Request $request, $id, $child)
    {
        $tx = $request->query->get('tx', "");
        try {
            $response = $app['api']->modifyResource(
                $app->escape($id) . '/' . $child,
                $request->getContent(),
                $request->headers->all(),
                $tx
            );
        } catch (\Exception $e) {
            $app->abort(
                503,
                '"Chullo says Fedora4 Repository is Not available"'
            );
        }
        return $response;
    }

    /**
     * Resource DELETE route controller. takes $id (valid UUID) for the parent resource as first value to match
     * takes 'rx' and/or 'checksum' as optional query arguments
     * @see https://wiki.duraspace.org/display/FEDORA40/RESTful+HTTP+API#RESTfulHTTPAPI-RedDELETEDeletearesource
     * @todo check for transaction and create one if empty.
     * @todo test with the force.
     */
    public function delete(Application $app, Request $request, $id, $child)
    {
        $tx = $request->query->get('tx', "");
        $force = $request->query->get('force', false);

        $delete_queue = array($app->escape($id) . '/' . $child);
        $sparql_query = $app['twig']->render('findAllOreProxy.sparql', array(
            'resource' => $id,
        ));
        try {
            $sparql_result = $app['triplestore']->query($sparql_query);
        } catch (\Exception $e) {
            $app->abort(503, 'Chullo says "Triple Store Not available"');
        }
        if (count($sparql_result) > 0) {
            foreach ($sparql_result as $ore_proxy) {
                $delete_queue[] = $ore_proxy->obj->getUri();
            }
        }
        $response = '';
        try {
            foreach ($delete_queue as $object_uri) {
                $response = $app['api']->deleteResource($object_uri, $tx);
                $status = $response->getStatusCode();
                // Abort if we do not get a success (codes 204 or 410) from Fedora
                if (204 != $status && 410 != $status) {
                    $app->abort(503, 'Could not delete resource or proxy at ' .
                        $object_uri);
                }
                // Remove fcr:tombstone if 'force' is true.
                if ($force) {
                    $response = $app['api']->deleteResource($object_uri .
                        '/fcr:tombstone', $tx);
                }
            }
        } catch (\Exception $e) {
            $app->abort(503, '"Chullo says Fedora4 Repository is Not available"');
        }
        // Return the last response since, in theory, if we've come this far we've removed everything.
        // If we don't get this far its because we never got a 204/410 or Fedora is down.
        return $response;
    }

    /**
     * Store the UUID -> Fedora Path for this transaction.
     *
     * @var Application $app
     *     The silex application.
     * @var string $id
     *     The Fedora Path of the new object.
     * @var string $txId
     *     The transaction ID.
     */
    private function storeUuid(Application $app, $id, $txId)
    {
        if (isset($app['islandora.keyCache'])) {
            try {
                $transform = $id . '/fcr:transform/' . TransactionController::$uuidTransformKey;
                $response = $app['api']->getResource(
                    $app->escape($transform),
                    array('Accept' => 'application/json'),
                    $txId
                );
                if ($response->getStatusCode() == 200) {
                    $json_response = json_decode($response->getBody(), true);
                    if (count($json_response) > 0) {
                        foreach ($json_response as $entry) {
                            $path = reset($entry['id']);
                            $uuid = reset($entry['uuid']);
                            if (isset($path) && $path !== false && isset($uuid) && $uuid !== false) {
                                $response = $app['islandora.keyCache']->set($txId, $uuid, $path);
                                if ($response === false) {
                                    error_log("Got FALSE back from Redis");
                                }
                            } else {
                                error_log("Can't store UUID and path in keyCache");
                            }
                        }
                    }
                } else {
                    error_log("Failed to get transform for $transform : " . $response->getStatusCode());
                }
            } catch (\Exception $e) {
                $app->abort(503, "Error storing to keyCache: " . $e->getMessage());
            }
        }
    }
}
