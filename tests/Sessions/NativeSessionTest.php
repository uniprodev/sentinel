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

namespace Cartalyst\Sentinel\Tests\Sessions;

use stdClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Cartalyst\Sentinel\Sessions\NativeSession;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class NativeSessionTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    public function it_can_start_the_session(): void
    {
        $session = new NativeSession('__sentinel');

        $this->assertInstanceOf(NativeSession::class, $session);
    }

    #[Test]
    public function it_can_put_a_value_on_session(): void
    {
        $session = new NativeSession('__sentinel');

        $class      = new stdClass;
        $class->foo = 'bar';

        $session->put($class);

        $this->assertSame(serialize($class), $_SESSION['__sentinel']);

        unset($_SESSION['__sentinel']);
    }

    #[Test]
    public function it_can_get_a_value_from_session(): void
    {
        $session = new NativeSession('__sentinel');

        $this->assertNull($session->get());

        $class      = new stdClass;
        $class->foo = 'bar';

        $_SESSION['__sentinel'] = serialize($class);

        $this->assertNotNull($session->get());

        unset($_SESSION['__sentinel']);
    }

    #[Test]
    public function it_can_forget_a_value_from_the_session(): void
    {
        $session = new NativeSession('__sentinel');

        $_SESSION['__sentinel'] = 'bar';

        $this->assertSame('bar', $_SESSION['__sentinel']);

        $session->forget();

        $this->assertFalse(isset($_SESSION['__sentinel']));
    }
}
