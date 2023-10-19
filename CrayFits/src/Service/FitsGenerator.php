<?php

namespace App\Service;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FitsGenerator
 * @package App\Service
 */
class FitsGenerator {
    /**
     * @param $input_filename
     * @return mixed
     */


    // used for testing.  Will delete prerelease.
    public function getFits($input_filename) {
        $options = [
            'base_uri' => $_ENV['FITS_WEBSERVICE_URI'],
        ];

        $client = new Client($options);
        $response = $client->post('examine', [
            'multipart' => [
                [
                    'name' => 'datafile',
                    'filename' => $input_filename,
                    'contents' => file_get_contents($input_filename),
                ],
            ]
        ]);
        return $response->getBody()->getContents();
    }
}
