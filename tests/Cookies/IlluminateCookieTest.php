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

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Cookie\CookieJar;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Cookie;
use Cartalyst\Sentinel\Cookies\IlluminateCookie;

class IlluminateCookieTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    #[Test]
    public function it_can_put_a_cookie(): void
    {
        $jar = new CookieJar;

        $request = m::mock(Request::class);
        $request->shouldReceive('cookie')->with('foo')->once()->andReturn('bar');

        $illuminateCookie = new IlluminateCookie($request, $jar, 'foo');

        $illuminateCookie->put('bar');

        $this->assertSame('bar', $illuminateCookie->get());
    }

    #[Test]
    public function it_can_get_a_cookie(): void
    {
        $jar = m::mock(CookieJar::class);
        $jar->shouldReceive('getQueuedCookies')->once()->andReturn([]);

        $request = m::mock(Request::class);
        $request->shouldReceive('cookie')->with('foo')->once()->andReturn('bar');

        $illuminateCookie = new IlluminateCookie($request, $jar, 'foo');

        $this->assertSame('bar', $illuminateCookie->get());
    }

    #[Test]
    public function it_can_get_a_queued_cookie(): void
    {
        $cookie = m::mock(Cookie::class);
        $cookie->shouldReceive('getValue')->andReturn('bar');

        $jar = m::mock(CookieJar::class);
        $jar->shouldReceive('getQueuedCookies')->once()->andReturn(['foo' => $cookie]);

        $request = m::mock(Request::class);

        $illuminateCookie = new IlluminateCookie($request, $jar, 'foo');

        $this->assertSame('bar', $illuminateCookie->get());
    }

    #[Test]
    public function it_can_forget_a_cookie(): void
    {
        $jar = new CookieJar;

        $request = m::mock(Request::class);
        $request->shouldReceive('cookie')->with('foo')->once()->andReturn(null);

        $illuminateCookie = new IlluminateCookie($request, $jar, 'foo');

        $illuminateCookie->put('bar');

        $illuminateCookie->forget();

        $this->assertNull($illuminateCookie->get());
    }
}
