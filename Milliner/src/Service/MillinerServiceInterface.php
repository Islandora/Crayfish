<?php

namespace Islandora\Milliner\Service;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

interface MillinerServiceInterface
{
    public function create(ResponseInterface $drupal_entity, Request $request);

    public function update(ResponseInterface $drupal_entity, Request $request);

    public function delete($path, Request $request);
}