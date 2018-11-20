<?php
namespace Islandora\Homarus\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Islandora\Crayfish\Commons\ApixFedoraResourceRetriever;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HomarusController
 * @package Islandora\Homarus\Controller
 * @param $log
 */
class HomarusController {

  /**
   * @var \Islandora\Crayfish\Commons\CmdExecuteService
   */
  protected $cmd;


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
      $log,
      $mime_to_format
  ) {
      $this->cmd = $cmd;
      $this->formats = $formats;
      $this->default_format = $default_format;
      $this->executable = $executable;
      $this->log = $log;
      $this->mime_to_format = $mime_to_format;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
   */
  public function convert(Request $request) {
    $this->log->info('Ffmpeg Convert request.');

    // Short circuit if there's no Apix-Ldp-Resource header.
    if (!$request->headers->has("Apix-Ldp-Resource"))
    {
      $this->log->debug("Malformed request, no Apix-Ldp-Resource header present");
      return new Response(
        "Malformed request, no Apix-Ldp-Resource header present",
        400
      );
    } else {
      $source = $request->headers->get('Apix-Ldp-Resource');
    }

    // Find the format
    $content_types = $request->getAcceptableContentTypes();
    $content_type = $this->get_content_type($content_types);
    $format = $this->get_ffmpeg_format($content_type);

    $cmd_params = "";
    if($format == "mp4") {
      $cmd_params = " -vcodec libx264 -preset medium -acodec aac -strict -2 -ab 128k -ac 2 -async 1 -movflags frag_keyframe+empty_moov ";
    }

    // Arguments to ffmpeg command are sent as a custom header
    $args = $request->headers->get('X-Islandora-Args');
    $this->log->debug("X-Islandora-Args:", ['args' => $args]);

    $cmd_string = "$this->executable -i $source $cmd_params -f $format -";
    $this->log->info('Ffempg Command:', ['cmd' => $cmd_string]);

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


  private function get_content_type($content_types) {
    $content_type = null;
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
    return $content_type;
  }

  private function get_ffmpeg_format($content_type){
    foreach ($this->mime_to_format as $format) {
      if (strpos($format, $content_type) !== false) {
        $this->log->info("does it get here");
        $format_info = explode("_", $format);
        break;
      }
    }
    return $format_info[1];
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

}
