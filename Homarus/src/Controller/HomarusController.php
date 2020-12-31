<?php
namespace App\Islandora\Homarus\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Crayfish\Commons\ApixFedoraResourceRetriever;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HomarusController
 * @package App\Islandora\Homarus\Controller
 * @param $log
 */
class HomarusController
{

    /**
     * @var \Islandora\Crayfish\Commons\CmdExecuteService
     */
    protected $cmd;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Stores array of valid mimetypes
     *
     * @var array
     */
    private $mimetypes;

    /**
     * Default FFmpeg format
     *
     * @var string
     */
    private $default_mimetype;

    /**
     * Mapping of mime-type to ffmpeg formats.
     *
     * @var array
     */
    private $mime_to_format;

    /**
     * The default format.
     *
     * @var string
     */
    private $default_format;

    /**
     * The executable.
     *
     * @var string
     */
    private $executable;

    /**
     * Controller constructor.
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     *   The command execute service.
     * @param array $mimetypes
     *   Array with keys 'valid' -> array of valid mimetypes and 'default' as
     *   the default mimetype.
     * @param string $executable
     *   Path to the executable to use.
     * @param array $mime_to_format
     *   Array with keys 'valid' -> associative array of mimetype -> formats
     *   for use with FFmpeg and 'default' with the default format.
     * @param LoggerInterface $log
     *   A logger interface
     */
    public function __construct(
        CmdExecuteService $cmd,
        $mimetypes,
        $executable,
        $mime_to_format,
        LoggerInterface $log
    ) {
        $this->cmd = $cmd;
        $this->mimetypes = $mimetypes['valid'];
        $this->default_mimetype = $mimetypes['default'];
        $this->executable = $executable;
        $this->log = $log;
        $this->mime_to_format = $mime_to_format['valid'];
        $this->default_format = $mime_to_format['default'];
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function convert(Request $request)
    {
        $this->log->info('Ffmpeg Convert request.');

        // Short circuit if there's no Apix-Ldp-Resource header.
        if (!$request->headers->has("Apix-Ldp-Resource")) {
            $this->log->error("Malformed request, no Apix-Ldp-Resource header present");
            return new Response(
                "Malformed request, no Apix-Ldp-Resource header present",
                400
            );
        } else {
            $source = $request->headers->get('Apix-Ldp-Resource');
        }

        // Find the format
        $content_types = $request->getAcceptableContentTypes();
        list($content_type, $format) = $this->getFfmpegFormat($content_types);

        $cmd_params = "";
        if ($format == "mp4") {
            $cmd_params = " -vcodec libx264 -preset medium -acodec aac " .
              "-strict -2 -ab 128k -ac 2 -async 1 -movflags " .
              "frag_keyframe+empty_moov ";
        }

        // Arguments to ffmpeg command are sent as a custom header.
        $args = $request->headers->get('X-Islandora-Args');

        // Reject messages that try to set loglevel. We have to force
        // it to be '-loglevel error'. Anything more verbose caues
        // issues with large files.
        if (strpos($args, '-loglevel') !== false) {
            $this->log->error("Malformed request, don't try to set loglevel in X-Islandora-Args");
            return new Response(
                "Malformed request, don't try to set loglevel in X-Islandora-Args",
                400
            );
        }

        // Add -loglevel error so large files can be processed.
        $args .= ' -loglevel error';
        $this->log->debug("X-Islandora-Args:", ['args' => $args]);
        $token = $request->headers->get('Authorization');
        $headers = "'Authorization:  $token'";
        $cmd_string = "$this->executable -headers $headers -i $source  $args $cmd_params -f $format -";
        $this->log->debug('Ffmpeg Command:', ['cmd' => $cmd_string]);

        // Return response.
        try {
            return new StreamedResponse(
                $this->cmd->execute($cmd_string, $source),
                200,
                ['Content-Type' => $content_type]
            );
        } catch (\RuntimeException $e) {
            $this->log->error("RuntimeException:", ['exception' => $e]);
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * Filters through an array of acceptable content-types and returns a FFmpeg format.
     *
     * @param array $content_types
     *   The Accept content-types.
     *
     * @return array
     *   Array with [ $content-type, $format ], falls back to defaults.
     */
    private function getFfmpegFormat(array $content_types)
    {
        $content_type = null;
        foreach ($content_types as $type) {
            if (in_array($type, $this->mimetypes)) {
                $content_type = $type;
                break;
            }
        }

        if ($content_type === null) {
            $this->log->info('No matching content-type, falling back to default.');
            return [$this->default_mimetype, $this->default_format];
        }

        foreach ($this->mime_to_format as $mime_format => $format) {
            if ($mime_format == $content_type) {
                return [$content_type, $format];
            }
        }
        $this->log->info('No matching content-type to format mapping, falling back to default.');
        return [$this->default_mimetype, $this->default_format];
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
}
