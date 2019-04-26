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

use GuzzleHttp\Client;

class TriplestoreClient implements ITriplestoreClient
{

    protected $client;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Static factory function
     *
     * @param string    $sparql_endpoint    Url for sparql endpoint
     *
     * @return TriplestoreClient
     * @codeCoverageIgnore
     */
    public static function create($sparql_endpoint)
    {
        $guzzle = new Client(['base_uri' => $sparql_endpoint]);
        return new static($guzzle);
    }

    /**
     * Executes a sparql query.
     *
     * @param string    $sparql Sparql query
     *
     * @return EasyRdf_Sparql_Result    Results object
     */
    public function query($sparql)
    {
        $response = $this->client->post("", [
            'query' => [
                'format' => 'json',
                'query' => $sparql,
            ],
        ]);

        return new \EasyRdf_Sparql_Result($response->getBody(), 'application/sparql-results+json');
    }
}
