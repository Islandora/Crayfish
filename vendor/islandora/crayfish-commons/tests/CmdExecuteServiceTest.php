<?php

namespace Islandora\Crayfish\Commons\tests;

use Islandora\Crayfish\Commons\CmdExecuteService;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class CmdExecuteServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testExecuteWithResource()
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());
        $service = new CmdExecuteService($logger);

        $string = "apple\npear\nbanana";
        $data = fopen('php://memory', 'r+');
        fwrite($data, $string);
        rewind($data);

        $command = 'sort -';

        $callback = $service->execute($command, $data);

        $this->assertTrue(is_callable($callback), "execute() must return a callable.");

        $output = $service->getOutputStream();
        rewind($output);
        $actual = stream_get_contents($output);

        $this->assertTrue(
            $actual == "apple\nbanana\npear\n",
            "Output stream should have sorted the list, received $actual"
        );

        // Call the callback just to close the streams/process.
        $callback();
    }

    public function testExecuteWithoutResource()
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());
        $service = new CmdExecuteService($logger);

        $command = 'echo "derp"';
        $callback = $service->execute($command, "");

        $this->assertTrue(is_callable($callback), "execute() must return a callable.");

        $output = $service->getOutputStream();
        rewind($output);
        $actual = stream_get_contents($output);

        $this->assertTrue($actual == "derp\n", "Output stream should contain 'derp', received $actual");

        // Call the callback just to close the streams/process.
        $callback();
    }
}
