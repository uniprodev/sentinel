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

namespace Cartalyst\Sentinel\Tests\Checkpoints;

use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Cartalyst\Sentinel\Users\EloquentUser;
use Cartalyst\Sentinel\Checkpoints\ThrottleCheckpoint;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Cartalyst\Sentinel\Throttling\ThrottleRepositoryInterface;
use Cartalyst\Sentinel\Throttling\IlluminateThrottleRepository;

class ThrottleCheckpointTest extends TestCase
{
    protected ThrottleCheckpoint $checkpoint;

    protected EloquentUser $user;

    /**
     * The Users repository instance.
     *
     * @var ThrottleRepositoryInterface
     */
    protected $throttle;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->throttle   = m::mock(IlluminateThrottleRepository::class);
        $this->user       = new EloquentUser;
        $this->checkpoint = new ThrottleCheckpoint($this->throttle, '127.0.0.1');
    }

    #[Test]
    public function can_login_the_user_without_being_throttled(): void
    {
        $this->throttle->shouldReceive('globalDelay')->once()->andReturn(0);
        $this->throttle->shouldReceive('ipDelay')->once();
        $this->throttle->shouldReceive('userDelay')->once()->andReturn(0);

        $status = $this->checkpoint->login($this->user);

        $this->assertTrue($status);
    }

    #[Test]
    public function can_check_if_the_user_is_being_throttled(): void
    {
        $this->throttle->shouldReceive('ipDelay')->once();

        $status = $this->checkpoint->check($this->user);

        $this->assertTrue($status);
    }

    #[Test]
    public function can_log_a_throttling_event(): void
    {
        $this->throttle->shouldReceive('globalDelay')->once();
        $this->throttle->shouldReceive('ipDelay')->once();
        $this->throttle->shouldReceive('userDelay')->once();
        $this->throttle->shouldReceive('log')->once();

        $this->checkpoint->fail($this->user);

        $this->assertTrue(true); // TODO: Add proper assertion later
    }

    #[Test]
    public function with_ip_address(): void
    {
        $this->throttle->shouldReceive('globalDelay')->once();
        $this->throttle->shouldReceive('ipDelay')->once();
        $this->throttle->shouldReceive('userDelay')->once();
        $this->throttle->shouldReceive('log')->once();

        $status = $this->checkpoint->fail($this->user);

        $this->assertTrue(true); // TODO: Add proper assertion later
    }

    #[Test]
    public function failed_login(): void
    {
        $this->expectException(ThrottlingException::class);
        $this->expectExceptionMessage('Too many unsuccessful attempts have been made globally, logins are locked for another [10] second(s).');

        $this->throttle->shouldReceive('globalDelay')->once()->andReturn(10);

        $this->checkpoint->login($this->user);
    }

    #[Test]
    public function throws_exception_with_ip_delay(): void
    {
        $this->expectException(ThrottlingException::class);
        $this->expectExceptionMessage('Suspicious activity has occured on your IP address and you have been denied access for another [10] second(s).');

        $this->throttle->shouldReceive('globalDelay')->once();
        $this->throttle->shouldReceive('ipDelay')->once()->andReturn(10);

        $this->checkpoint->fail($this->user);
    }

    #[Test]
    public function throws_exception_with_user_delay(): void
    {
        $this->expectException(ThrottlingException::class);
        $this->expectExceptionMessage('Too many unsuccessful login attempts have been made against your account. Please try again after another [10] second(s).');

        $this->throttle->shouldReceive('globalDelay')->once();
        $this->throttle->shouldReceive('ipDelay')->once()->andReturn(0);
        $this->throttle->shouldReceive('userDelay')->once()->andReturn(10);

        $this->checkpoint->fail($this->user);
    }

    #[Test]
    public function get_throttling_exception_attributes(): void
    {
        $this->throttle->shouldReceive('globalDelay')->once();
        $this->throttle->shouldReceive('ipDelay')->once()->andReturn(0);
        $this->throttle->shouldReceive('userDelay')->once()->andReturn(10);

        try {
            $this->checkpoint->fail($this->user);
        } catch (ThrottlingException $e) {
            $this->assertSame(10, $e->getDelay());
            $this->assertSame('user', $e->getType());
            $this->assertEqualsWithDelta(Carbon::now()->addSeconds(10), $e->getFree(), 3);
        }
    }
}
