<?php

namespace Islandora\Hypercube\Service;

interface HypercubeServiceInterface
{

    /**
     * Runs the OCR command, streaming input to STDIN
     *
     * @param $args
     * @param $image
     *
     * @throws \RuntimeException
     *
     * @return \Closure
     *   Closure that streams the output of the command.
     */
    public function execute($args, $image);
}
