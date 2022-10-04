<?php

namespace App\Islandora\Homarus\Controller;

use Islandora\Crayfish\Commons\CmdExecuteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HomarusController
 * @param $log
 * @package Islandora\Homarus\Controller
 */
class HomarusController
{

    /**
     * @var \Islandora\Crayfish\Commons\CmdExecuteService
     */
    protected $cmd;

    /**
     * @var \Monolog\Logger
     */
    protected $log;

    /**
     * Array of associative arrays with keys mimetype and format.
     *
     * @var array
     */
    private $formats;

    /**
     * The default format and mimetype.
     *
     * @var array
     */
    private $defaults;

    /**
     * The executable.
     *
     * @var string
     */
    private $executable;

    /**
     * Controller constructor.
     *
     * @param \Islandora\Crayfish\Commons\CmdExecuteService $cmd
     *   The command execute service.
     * @param array $formats
     *   The various valid mimetypes to format mapping.
     * @param array $defaults
     *   The default mimetype and format.
     * @param string $executable
     *   The path to the programs executable.
     * @param \Psr\Log\LoggerInterface $log
     *   The logger.
     */
    public function __construct(
        CmdExecuteService $cmd,
        array $formats,
        array $defaults,
        string $executable,
        LoggerInterface $log
    ) {
        $this->cmd = $cmd;
        $this->formats = $formats;
        $this->defaults = $defaults;
        $this->executable = $executable;
        $this->log = $log;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
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
                "faststart -y";
        }

        $temp_file_path = __DIR__ . "/../../static/" . basename($source) . "." . $format;
        $this->log->debug('Tempfile: ' . $temp_file_path);

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
        $cmd_string = "$this->executable -headers $headers -i $source  $args $cmd_params -f $format $temp_file_path";
        $this->log->debug('Ffmpeg Command:', ['cmd' => $cmd_string]);

        // Return response.
        return $this->generateDerivativeResponse($cmd_string, $source, $temp_file_path, $content_type);
    }

  /**
   * @param string $cmd_string
   * @param string $source
   * @param string $path
   * @param string $content_type
   * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function generateDerivativeResponse(string $cmd_string, string $source, string $path, string $content_type)
  {
    try {
      $this->cmd->execute($cmd_string, $source);
      return (new BinaryFileResponse(
        $path,
        200,
        ['Content-Type' => $content_type]
      ))->deleteFileAfterSend(true);
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
    private function getFfmpegFormat(array $content_types): array
    {
        foreach ($content_types as $type) {
            $key = array_search(
                $type,
                array_column($this->formats, "mimetype")
            );
            if ($key !== false) {
                $format = $this->formats[$key]['format'];
                return [$type, $format];
            }
        }

        $this->log->info('No matching content-type, falling back to default.');
        return [$this->defaults["mimetype"], $this->defaults["format"]];
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function convertOptions(): BinaryFileResponse
    {
        return new BinaryFileResponse(
            __DIR__ . "/../../public/static/convert.ttl",
            200,
            ['Content-Type' => 'text/turtle']
        );
    }
}
