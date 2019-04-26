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
 * Interface for triplestore interaction.
 */
interface ITriplestoreClient
{
    /**
     * Executes a sparql query.
     *
     * @param string    $sparql Sparql query
     *
     * @return EasyRdf_Sparql_Result    Results object
     */
    public function query($sparql);
}
