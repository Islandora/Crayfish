<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Test;

use PHPUnit\Framework\TestCase;

// Auto-adapt to PHPUnit 8 that added a `void` return-type to the tearDown method

if ((new \ReflectionMethod(TestCase::class, 'tearDown'))->hasReturnType()) {
    /**
     * @internal
     */
    trait KernelShutdownOnTearDownTrait
    {
        protected function tearDown(): void
        {
            static::ensureKernelShutdown();
        }
    }
} else {
    /**
     * @internal
     */
    trait KernelShutdownOnTearDownTrait
    {
        /**
         * @return void
         */
        protected function tearDown()
        {
            static::ensureKernelShutdown();
        }
    }
}
