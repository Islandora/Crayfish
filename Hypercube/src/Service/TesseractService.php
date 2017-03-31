<?php

namespace Islandora\Hypercube\Service;

/**
 * Executes tesseract on the command line, streaming both the input and the
 * result.
 *
 * @package Islandora\Hypercube\Service
 */
class TesseractService implements HypercubeServiceInterface
{

    protected $executable;

    /**
     * TesseractExecutor constructor.
     * @param $executable
     */
    public function __construct($executable)
    {
        $this->executable = $executable;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($args, $image)
    {
        // Use pipes for STDIN, STDOUT, and STDERR
        $descr = array(
            0 => array(
                'pipe',
                'r'
            ) ,
            1 => array(
                'pipe',
                'w'
            ) ,
            2 => array(
                'pipe',
                'w'
            )
        );
        $pipes = [];

        // Start tesseract process, telling it to use STDIN for input and STDOUT for output.
        $cmd = escapeshellcmd($this->executable . ' stdin stdout ' . $args);
        $process = proc_open($cmd, $descr, $pipes);

        // Stream input to STDIN
        stream_copy_to_stream($image, $pipes[0]);

        // Close STDIN
        fclose($pipes[0]);

        // Wait for process to finish and get its exit code
        $exit_code = null;
        while ($exit_code === null) {
            $status = proc_get_status($process);
            if ($status['running'] === false) {
                $exit_code = $status['exitcode'];
            }
        }

        // On error, extract message from STDERR and throw an exception.
        if ($exit_code != 0) {
            $msg = stream_get_contents($pipes[2]);

            $this->cleanup($pipes, $process);

            throw new \RuntimeException($msg, 500);
        }

        // Return a function that streams the output.
        return function () use ($pipes, $process) {
            // Flush output
            while ($chunk = fread($pipes[1], 1024)) {
                echo $chunk;
                ob_flush();
                flush();
            }

            $this->cleanup($pipes, $process);
        };
    }

    protected function cleanup($pipes, $process)
    {
        // Close STDOUT and STDERR
        for ($i = 1; $i < count($pipes); $i++) {
            fclose($pipes[$i]);
        }

        // Close the process;
        proc_close($process);
    }
}
