<?php

namespace App\Islandora\Houdini\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HoudiniController
 * @package App\Islandora\Houdini\Controller
 */
class HoudiniController
{

    /**
     * @var App\Islandora\Crayfish\Commons\CmdExecuteService
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
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Controller constructor.
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     * @param array $formats
     * @param string $default_format
     * @param string $executable
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(
        CmdExecuteService $cmd,
        $formats,
        $default_format,
        $executable,
        LoggerInterface $log
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
            __DIR__ . "/../../public/static/convert.ttl",
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

        $fedora_resource = $request->attributes->get('fedora_resource');

        // Get image as a resource.
        $body = StreamWrapper::getResource($fedora_resource->getBody());

        // Arguments to image convert command are sent as a custom header
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
        $cmd_string = "$this->executable - $args $format:-";
        $this->log->info('Imagemagick Command:', ['cmd' => $cmd_string]);

        // Return response.
        try {
            return new StreamedResponse(
                $this->cmd->execute($cmd_string, $body),
                200,
                ['Content-Type' => $content_type]
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
            __DIR__ . "/../../public/static/identify.ttl",
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
