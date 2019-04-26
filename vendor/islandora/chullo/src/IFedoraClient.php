<?php

/**
 * This file is part of Islandora.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP Version 5.5.9
 *
 * @category Islandora
 * @package  Islandora
 * @author   Daniel Lamb <dlamb@islandora.ca>
 * @author   Nick Ruest <ruestn@gmail.com>
 * @author   Diego Pino <dpino@metro.org>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://www.islandora.ca
 */

namespace Islandora\Chullo;

/**
 * Interface for Fedora interaction.
 */
interface IFedoraClient extends IFedoraApi
{
    /**
     * Gets RDF metadata from Fedora.
     *
     * @param string    $uri            Resource URI
     * @param array     $headers        HTTP Headers
     *
     * @return EasyRdf_Graph
     */
    public function getGraph(
        $uri = "",
        $headers = []
    );

    /**
     * Saves RDF in Fedora.
     *
     * @param string            $uri            Resource URI
     * @param EasyRdf_Resource  $rdf            RDF to save
     * @param array     $headers        HTTP Headers
     *
     * @return null
     */
    public function saveGraph(
        $uri,
        \EasyRdf_Graph $graph,
        $headers = []
    );
}
