<?php

namespace Islandora\Houdini\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Crayfish\Commons\ApixFedoraResourceRetriever;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HoudiniController
 * @package Islandora\Houdini\Controller
 */
class HoudiniController
{

    /**
     * @var \Islandora\Crayfish\Commons\CmdExecuteService
     */
    protected $cmd;

    /**
     * @var array
     */
    protected $formats;

    /**
     * @var string
     */
    protected $default_format;

    /**
     * @var string
     */
    protected $executable;

    /**
     * @var \Monolog\Logger
     */
    protected $log;

    /**
     * Controller constructor.
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     * @param array $formats
     * @param string $default_format
     * @param string $executable
     * @param $log
     */
    public function __construct(
        CmdExecuteService $cmd,
        $formats,
        $default_format,
        $executable,
        $log
    ) {
        $this->cmd = $cmd;
        $this->formats = $formats;
        $this->default_format = $default_format;
        $this->executable = $executable;
        $this->log = $log;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function convertOptions()
    {
        return new BinaryFileResponse(
            __DIR__ . "/../../static/convert.ttl",
            200,
            ['Content-Type' => 'text/turtle']
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function convert(Request $request)
    {
        $this->log->info('Convert request.');

        // Short circuit if there's no Apix-Ldp-Resource header.
        if (!$request->headers->has("Apix-Ldp-Resource")) {
            $this->log->debug("Malformed request, no Apix-Ldp-Resource header present");
            return new Response(
                "Malformed request, no Apix-Ldp-Resource header present",
                400
            );
        }
        $url = $request->headers->get("Apix-Ldp-Resource");
        $this->log->debug("Apix-Ldp-Resource:", ['url' => $url]);

        // Get optional Auth header.
        if ($request->headers->has("Authorization")) {
            $auth = $request->headers->get("Authorization");
            $this->log->debug("Authorization:", ['auth' => $auth]);
        }

        // Get optonal arguments header.
        if ($request->headers->has("Authorization")) {
            $auth = $request->headers->get("Authorization");
            $this->log->debug("Authorization:", ['auth' => $auth]);
        }
        $args = $request->headers->get('X-Islandora-Args');
        $this->log->debug("X-Islandora-Args:", ['args' => $args]);

        // Find the correct image type to return
        $content_type = null;
        $content_types = $request->getAcceptableContentTypes();
        $this->log->debug('Content Types:', is_array($args) ? $args : []);
        foreach ($content_types as $type) {
            if (in_array($type, $this->formats)) {
                $content_type = $type;
                break;
            }
        }
        if ($content_type === null) {
            $content_type = $this->default_format;
            $this->log->info('Falling back to default content type');
        }
        $this->log->debug('Content Type Chosen:', ['type' => $content_type]);

        // Build arguments
        $exploded = explode('/', $content_type, 2);
        $format = count($exploded) == 2 ? $exploded[1] : $exploded[0];

        // Build up the command string and escape it.
        if (isset($auth)) {
          $cmd_string = "curl -H \"Authorization: $auth\" \"$url\" | $this->executable - $args $format:- 2>&1";
        }
        else {
          $cmd_string = "curl \"$url\" | $this->executable - $args $format:- 2>&1";
        }

        //$cmd_string = escapeshellcmd($cmd_string);
        $this->log->info('Imagemagick Command:', ['cmd' => $cmd_string]);

        $stdout = popen($cmd_string, 'r');

        // Write to a temp stream.
        $temp = fopen("php://temp", 'w+');
        stream_copy_to_stream($stdout, $temp);

        // Close the process and get the return code.
        $return_code = pclose($stdout);

        // Return response.
        try {
            return new StreamedResponse(
                function () use ($temp) {
                    rewind($temp);
                    while (!feof($temp)) {
                      echo fread($temp, 1024);
                      ob_flush();
                      flush();
                    }
                    fclose($temp);
                },
                $return_code === 0 ? 200 : 500,
                ['Content-Type' => $return_code === 0 ? $content_type : 'text/plain']
            );
        } catch (\RuntimeException $e) {
            $this->log->error("RuntimeException:", ['exception' => $e]);
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function identifyOptions()
    {
        return new BinaryFileResponse(
            __DIR__ . "/../../static/identify.ttl",
            200,
            ['Content-Type' => 'text/turtle']
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function identify(Request $request)
    {
        $this->log->info('Identify request.');

        $fedora_resource = $request->attributes->get('fedora_resource');

        // Get image as a resource.
        $body = StreamWrapper::getResource($fedora_resource->getBody());

        // Arguments to image convert command are sent as a custom header
        $args = $request->headers->get('X-Islandora-Args');
        $this->log->debug("X-Islandora-Args:", ['args' => $args]);

        // Build arguments
        $cmd_string = "$this->executable - $args json:-";
        $this->log->info('Imagemagick Command:', ['cmd' => $cmd_string]);

        // Return response.
        try {
            return new StreamedResponse(
                $this->cmd->execute($cmd_string, $body),
                200,
                ['Content-Type' => 'application/json']
            );
        } catch (\RuntimeException $e) {
            $this->log->error("RuntimeException:", ['exception' => $e]);
            return new Response($e->getMessage(), 500);
        }
    }
}
