<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Test;

use PHPUnit\Framework\TestCase;

// Auto-adapt to PHPUnit 8 that added a `void` return-type to the setUp/tearDown methods

if (method_exists(\ReflectionMethod::class, 'hasReturnType') && (new \ReflectionMethod(TestCase::class, 'tearDown'))->hasReturnType()) {
    eval('
    namespace Symfony\Component\Validator\Test;

    /**
     * @internal
     */
    trait ForwardCompatTestTrait
    {
        private function doSetUp(): void
        {
        }

        private function doTearDown(): void
        {
        }

        protected function setUp(): void
        {
            $this->doSetUp();
        }

        protected function tearDown(): void
        {
            $this->doTearDown();
        }
    }
');
} else {
    /**
     * @internal
     */
    trait ForwardCompatTestTrait
    {
        private function doSetUp(): void
        {
        }

        private function doTearDown(): void
        {
        }

        /**
         * @return void
         */
        protected function setUp()
        {
            $this->doSetUp();
        }

        /**
         * @return void
         */
        protected function tearDown()
        {
            $this->doTearDown();
        }
    }
}
