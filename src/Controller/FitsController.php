<?php
// src/Controller/FitsController.php
namespace App\Controller;

use Islandora\Chullo\IFedoraApi;
use App\Service\FitsGenerator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class FitsController {

    protected $fitsGenerator;
    protected $client;


    /**
     * FitsController constructor.
     * @param IFedoraApi $api
     * @param FitsGenerator $fitsGenerator
     */

    public function __construct(FitsGenerator $fitsGenerator) {
        $this->fitsGenerator = $fitsGenerator;
        $options = ['base_uri' => $_ENV['FITS_WEBSERVICE_URI']];
        $this->client = new Client($options);
    }


    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface|StreamedResponse
     */
    public function generate_fits(Request $request) {
        $file_uri = $request->headers->get('Apix-Ldp-Resource');
        // Pass along auth headers if present.
        $headers = [];
        if ($request->headers->has("Authorization")) {
            $headers['Authorization'] = $request->headers->get("Authorization");
        }
        $response = $this->client->post('examine', [
            'headers' => $headers,
            'multipart' => [
                [
                    'name' => 'datafile',
                    'filename' => $file_uri,
                    'contents' => file_get_contents($file_uri),
                ],
            ]
        ]);
        $fits_xml = $response->getBody()->getContents();
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/html');
        $response->setCallback(function () use($fits_xml){
            echo($fits_xml);
        });
        return $response;
    }
}
