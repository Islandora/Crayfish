<?php

namespace Islandora\Gemini\Controller;

use Islandora\Crayfish\Commons\IdMapper\IdMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeminiController
 * @package Islandora\Gemini\Controller
 */
class GeminiController
{

    /**
     * @var \Islandora\Crayfish\Commons\IdMapper\IdMapperInterface
     */
    protected $idMapper;

    /**
     * GeminiController constructor.
     * @param \Islandora\Crayfish\Commons\IdMapper\IdMapperInterface
     */
    public function __construct(IdMapperInterface $idMapper)
    {
        $this->idMapper = $idMapper;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getMetadataId(Request $request)
    {
        $drupal = $request->query->get('drupal', null);
        if (!$drupal) {
            return new Response("Missing 'drupal' query param", 400);
        }

        try {
            if (!$result = $this->idMapper->getMetadataId($drupal)) {
                return new Response(null, 404);
            }

            return new Response($result, 200);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getBinaryId(Request $request)
    {
        $drupal = $request->query->get('drupal', null);
        if (!$drupal) {
            return new Response("Missing 'drupal' query param", 400);
        }

        try {
            if (!$result = $this->idMapper->getBinaryId($drupal)) {
                return new Response(null, 404);
            }

            return new Response($result, 200);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveMetadataId(Request $request)
    {
        $drupal = $request->query->get('drupal', null);
        if (!$drupal) {
            return new Response("Missing 'drupal' query param", 400);
        }

        $fedora = $request->query->get('fedora', null);
        if (!$fedora) {
            return new Response("Missing 'fedora' query param", 400);
        }

        try {
            $this->idMapper->saveMetadataId(
                $drupal,
                $fedora
            );
             return new Response(null, 201);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveBinaryId(Request $request)
    {
        $drupal = $request->query->get('drupal', null);
        if (!$drupal) {
            return new Response("Missing 'drupal' query param", 400);
        }

        $fedora = $request->query->get('fedora', null);
        if (!$fedora) {
            return new Response("Missing 'fedora' query param", 400);
        }

        $describedby = $request->query->get('describedby', null);
        if (!$describedby) {
            return new Response("Missing 'describedby' query param", 400);
        }

        try {
            $this->idMapper->saveBinaryId(
                $drupal,
                $fedora,
                $describedby
            );
            return new Response(null, 201);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteMetadataId(Request $request)
    {
        $drupal = $request->query->get('drupal', null);
        if (!$drupal) {
            return new Response("Missing 'drupal' query param", 400);
        }

        try {
            if (!$result = $this->idMapper->deleteMetadataId($drupal)) {
                return new Response("Not Found", 404);
            }

            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteBinaryId(Request $request)
    {
        $describedby = $request->query->get('describedby', null);
        if (!$describedby) {
            return new Response("Missing 'describedby' query param", 400);
        }

        try {
            if (!$result = $this->idMapper->deleteBinaryId($describedby)) {
                return new Response("Not Found", 404);
            }

            return new Response(null, 204);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}
