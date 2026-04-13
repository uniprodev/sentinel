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

namespace Cartalyst\Sentinel\Tests\Cookies;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Cartalyst\Sentinel\Cookies\NullCookie;

class NullCookieTest extends TestCase
{
    protected NullCookie $cookie;

    protected function setUp(): void
    {
        $this->cookie = new NullCookie;
    }

    #[Test]
    public function it_can_put_a_cookie(): void
    {
        $this->assertNull($this->cookie->put('cookie'));
    }

    #[Test]
    public function it_can_get_a_cookie(): void
    {
        $this->assertNull($this->cookie->get());
    }

    #[Test]
    public function it_can_forget_a_cookie(): void
    {
        $this->assertNull($this->cookie->forget());
    }
}
