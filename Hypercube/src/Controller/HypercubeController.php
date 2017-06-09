<?php

namespace Islandora\Hypercube\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HypercubeController
 * @package Islandora\Hypercube\Controller
 */
class HypercubeController
{

    /**
     * @var \Islandora\Crayfish\Commons\CmdExecuteService
     */
    protected $cmd;

    /**
     * @var string
     */
    protected $executable;

    /**
     * HypercubeController constructor.
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     * @param string $executable
     */
    public function __construct(CmdExecuteService $cmd, $executable)
    {
        $this->cmd = $cmd;
        $this->executable = $executable;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function get(Request $request)
    {
        // Hack the fedora resource out of the attributes.
        $fedora_resource = $request->attributes->get('fedora_resource');

        // Get tiff as a resource.
        $body = StreamWrapper::getResource($fedora_resource->getBody());

        // Arguments to OCR command are sent as a custom header
        $args = $request->headers->get('X-Islandora-Args');

        $cmd_string = $this->executable . ' stdin stdout ' . $args;

        // Return response.
        try {
            return new StreamedResponse(
                $this->cmd->execute($cmd_string, $body),
                200,
                ['Content-Type' => 'text/plain']
            );
        } catch (\RuntimeException $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    public function options(Request $request)
    {
        $rdf = <<<EOD
@prefix apix:<http://fedora.info/definitions/v4/api-extension#> .
@prefix owl:<http://www.w3.org/2002/07/owl#> .
@prefix ebucore:<http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> .
@prefix fedora:<http://fedora.info/definitions/v4/repository#> .
@prefix islandora:<http://islandora.ca/CLAW#> .
@prefix rdfs:<http://www.w3.org/2000/01/rdf-schema#> .

<> a apix:Extension;
    rdfs:label "Tesseract OCR Service";
    rdfs:comment "A service that runs OCR on tiffs";
    apix:exposesService islandora:OcrService;
    apix:exposesServiceAt "svc:ocr";
    apix:bindsTo <#class> .

<#class> owl:intersectionOf (
        fedora:Binary
        [ a owl:Restriction; owl:onProperty ebucore:hasMimeType; owl:hasValue "image/tiff" ]
) .
EOD;

        return new Response(
            $rdf,
            200,
            ['Content-Type' => 'text/turtle']
        );
    }

}
