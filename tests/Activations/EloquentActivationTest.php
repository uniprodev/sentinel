<?php

/*
 * Part of the Sentinel package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Sentinel
 * @version    9.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011-2025, Cartalyst LLC
 * @link       https://cartalyst.com
 */

namespace Cartalyst\Sentinel\Tests\Activations;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Cartalyst\Sentinel\Activations\EloquentActivation;

class EloquentActivationTest extends TestCase
{
    protected EloquentActivation $activation;

    protected function setUp(): void
    {
        $this->activation = new EloquentActivation;
    }

    #[Test]
    public function it_can_get_the_completed_attribute_as_a_boolean(): void
    {
        $this->activation->completed = 1;

        $this->assertTrue($this->activation->completed);
    }

    #[Test]
    public function it_can_get_the_activation_code_using_the_getter(): void
    {
        $this->activation->code = 'foo';

        $this->assertSame('foo', $this->activation->getCode());
    }
}
