<?php

namespace App\Islandora\Milliner\Tests;

use App\Islandora\Milliner\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KernelBootTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testKernelBootsWithSymfonyConfiguration(): void
    {
        self::bootKernel();

        $this->assertSame('test', self::$kernel->getEnvironment());
    }
}
