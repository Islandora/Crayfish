<?php

namespace App\Controller;

use Islandora\Chullo\IFedoraApi;
use App\Service\FitsGenerator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
   * @param LoggerInterface $loggerger
   * @return StreamedResponse | Response;
   */
  public function generate_fits(Request $request) {
    $logger = new Logger('islandora_fits');
    $logger->pushHandler(new StreamHandler('/var/log/islandora/fits.log', Logger::DEBUG));
    $token = $request->headers->get('Authorization');
    $file_uri = $request->headers->get('Apix-Ldp-Resource');
    // If no file has been passed it probably because someone is testing the url from their browser.
    if (!$file_uri) {
      return new Response("<h2>The Fits microservice is up and running.</h2>");
    }
    try {
      $context = stream_context_create([
        "http" => [
          "header" => "Authorization:  $token",
        ],
      ]);
      $body = file_get_contents($file_uri, FALSE, $context);
    }
    catch (\Exception $e) {
      $logger->addError('ERROR', [$e->getMessage()]);
    }
    
    $response = $this->client->post('examine', [
      'multipart' => [
        [
          'name' => 'datafile',
          'filename' => $file_uri,
          'contents' => $body,
        ],
      ],
    ]);
    $logger->addInfo('Response Status', ["Status" => $response->getStatusCode(), "URI" => $file_uri]);
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
