<?php

namespace Islandora\Crayfish\Commons;

use Psr\Log\LoggerInterface;

/**
 * Runs a command streaming data in on stdin and out on stdout.
 *
 * @package Islandora\Crayfish\Commons
 */
class CmdExecuteService
{

    /**
     * @var null|\Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * @var resource
     */
    protected $output;

    /**
     * Executor constructor.
     * @param LoggerInterface $log
     */
    public function __construct(LoggerInterface $log = null)
    {
        $this->log = $log;
    }

    /**
     * $output getter.
     *
     * @return resource;
     */
    public function getOutputStream()
    {
        return $this->output;
    }

    /**
     * Runs the command
     *
     * @param $cmd
     * @param $data
     *
     * @throws \RuntimeException
     *
     * @return \Closure
     *   Closure that streams the output of the command.
     */
    public function execute($cmd, $data)
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

        // Start process, telling it to use STDIN for input and STDOUT for output.
        $cmd = escapeshellcmd($cmd);
        $process = proc_open($cmd, $descr, $pipes);

        // Get the data into pipe only if data is resource
        if (gettype($data) == "resource") {
            // Stream input to STDIN
            while (!feof($data)) {
                fwrite($pipes[0], fread($data, 1024));
            }

            // Close STDIN and the source data.
            fclose($pipes[0]);
            fclose($data);
        }

        // Wait for process to finish while reading STDOUT to a temp stream.
        // Otherwise the process can block indefinitely if STODUT gets bigger
        // than 4kb.
        $this->output = fopen("php://temp", 'w+');
        $exit_code = null;
        while ($exit_code === null) {
            $status = proc_get_status($process);
            if ($status['running'] === false) {
                $exit_code = $status['exitcode'];
            }

            $chunk = stream_get_contents($pipes[1]);
            if ($chunk !== false) {
                fwrite($this->output, $chunk);
            }
        }

        // Close STDOUT
        fclose($pipes[1]);

        // On error, extract message from STDERR and throw an exception.
        if ($exit_code != 0) {
            $msg = stream_get_contents($pipes[2]);
            $this->cleanup($pipes, $this->output, $process);
            if ($this->log) {
                $this->log->error('Process exited with non-zero code.', [
                  'exit_code' => $exit_code,
                  'stderr' => $msg,
                ]);
            }
            throw new \RuntimeException($msg, 500);
        }

        // Return a function that streams the output.
        return function () use ($pipes, $process) {
            rewind($this->output);
            while (!feof($this->output)) {
                echo fread($this->output, 1024);
                ob_flush();
                flush();
            }
            $this->cleanup($pipes, $this->output, $process);
        };
    }

    protected function cleanup($pipes, $output, $process)
    {
        // Close STDERR
        fclose($pipes[2]);

        // Close the temp output stream.
        fclose($this->output);

        // Close the process
        proc_close($process);
    }
}
