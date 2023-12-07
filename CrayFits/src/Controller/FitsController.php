<?php

namespace App\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use App\Service\FitsGenerator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class FitsController {

  protected $client;

  /**
   * FitsController constructor.
   */
  public function __construct(FitsGenerator $fitsGenerator) {
    $options = ['base_uri' => $_ENV['FITS_WEBSERVICE_URI']];
    $this->client = new Client($options);
  }

  /**
   * @param Request $request
   * @return StreamedResponse | Response;
   */
  public function generate_fits(Request $request) {
    set_time_limit(0);
    $token = $request->headers->get('Authorization');
    $file_uri = $request->headers->get('Apix-Ldp-Resource');

    // If no file has been passed it probably because someone is testing the url from their browser.
    if (!$file_uri) {
      return new Response("<h2>The Fits microservice is up and running.</h2>");
    }

    try {
      $fedora_resource = $request->attributes->get('fedora_resource');
      $body = StreamWrapper::getResource($fedora_resource->getBody());

      $response = $this->client->post('examine', [
        'multipart' => [
          [
            'name' => 'datafile',
            'filename' => $file_uri,
            'contents' => $body,
          ],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new Response("Failed to receive FITS XML", Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    $fits_xml = $response->getBody()->getContents();
    $encoding = mb_detect_encoding($fits_xml, 'UTF-8', TRUE);
    if ($encoding != 'UTF-8') {
      $fits_xml = utf8_encode($fits_xml);
    }
    $response = new StreamedResponse();
    $response->headers->set('Content-Type', 'application/xml');
    $response->setCallback(function () use ($fits_xml) {
      echo($fits_xml);
    });
    return $response;
  }
}
