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

namespace Cartalyst\Sentinel\Tests;

use Mockery as m;
use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Cartalyst\Sentinel\Sentinel;
use PHPUnit\Framework\Attributes\Test;
use Cartalyst\Sentinel\Roles\EloquentRole;
use Cartalyst\Sentinel\Users\EloquentUser;
use Illuminate\Contracts\Events\Dispatcher;
use Cartalyst\Sentinel\Roles\RoleRepositoryInterface;
use Cartalyst\Sentinel\Users\UserRepositoryInterface;
use Cartalyst\Sentinel\Activations\ActivationInterface;
use Cartalyst\Sentinel\Checkpoints\CheckpointInterface;
use Cartalyst\Sentinel\Reminders\ReminderRepositoryInterface;
use Cartalyst\Sentinel\Throttling\ThrottleRepositoryInterface;
use Cartalyst\Sentinel\Activations\ActivationRepositoryInterface;
use Cartalyst\Sentinel\Persistences\PersistenceRepositoryInterface;

class SentinelTest extends TestCase
{
    /**
     * The Illuminate Events Dispatcher instance.
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * The Sentinel instance.
     *
     * @var Sentinel
     */
    protected $sentinel;

    /**
     * The Users repository instance.
     *
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * The Roles repository instance.
     *
     * @var RoleRepositoryInterface
     */
    protected $roles;

    /**
     * The Activations repository instance.
     *
     * @var ActivationRepositoryInterface
     */
    protected $activations;

    /**
     * The Persistences repository instance.
     *
     * @var PersistenceRepositoryInterface
     */
    protected $persistences;

    /**
     * The Eloquent User instance.
     *
     * @var EloquentUser
     */
    protected $user;

    protected function setUp(): void
    {
        $this->user = m::mock(EloquentUser::class);

        $this->persistences = m::mock(PersistenceRepositoryInterface::class);
        $this->users        = m::mock(UserRepositoryInterface::class);
        $this->roles        = m::mock(RoleRepositoryInterface::class);
        $this->activations  = m::mock(ActivationRepositoryInterface::class);
        $this->dispatcher   = m::mock(Dispatcher::class);

        $this->sentinel = new Sentinel(
            $this->persistences,
            $this->users,
            $this->roles,
            $this->activations,
            $this->dispatcher
        );
    }

    protected function tearDown(): void
    {
        $this->user         = null;
        $this->sentinel     = null;
        $this->persistences = null;
        $this->users        = null;
        $this->roles        = null;
        $this->activations  = null;
        $this->dispatcher   = null;
        m::close();
    }

    #[Test]
    public function it_can_register_a_valid_user(): void
    {
        $this->users->shouldReceive('validForCreation')->once()->andReturn(true);
        $this->users->shouldReceive('create')->once()->andReturn($this->user);

        $credentials = [
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.registering', [$credentials]);

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.registered', $this->user);

        $result = $this->sentinel->register($credentials);

        $this->assertSame($result, $this->user);
    }

    #[Test]
    public function it_can_register_and_activate_a_valid_user(): void
    {
        $this->users->shouldReceive('validForCreation')->once()->andReturn(true);
        $this->users->shouldReceive('create')->once()->andReturn($this->user);

        $activation = m::mock(ActivationInterface::class);
        $activation->shouldReceive('getCode')->once()->andReturn('a_random_code');

        $this->activations->shouldReceive('create')->once()->andReturn($activation);
        $this->activations->shouldReceive('complete')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('dispatch')->times(4);

        $result = $this->sentinel->registerAndActivate([
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ]);

        $this->assertSame($result, $this->user);
    }

    #[Test]
    public function it_will_not_register_an_invalid_user(): void
    {
        $this->users->shouldReceive('validForCreation')->once()->andReturn(false);

        $this->dispatcher->shouldReceive('dispatch')->once();

        $result = $this->sentinel->register([
            'email' => 'foo@example.com',
        ]);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_activate_a_user_using_its_id(): void
    {
        $activation = m::mock(ActivationInterface::class);
        $activation->shouldReceive('getCode')->once()->andReturn('a_random_code');

        $this->users->shouldReceive('findById')->with('1')->once()->andReturn($this->user);

        $this->activations->shouldReceive('create')->once()->andReturn($activation);
        $this->activations->shouldReceive('complete')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('dispatch')->twice();

        $this->assertTrue($this->sentinel->activate('1'));
    }

    #[Test]
    public function it_can_activate_a_user_using_its_instance(): void
    {
        $activation = m::mock(ActivationInterface::class);
        $activation->shouldReceive('getCode')->once()->andReturn('a_random_code');

        $this->activations->shouldReceive('create')->once()->andReturn($activation);
        $this->activations->shouldReceive('complete')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('dispatch')->twice();

        $this->assertTrue($this->sentinel->activate($this->user));
    }

    #[Test]
    public function it_can_activate_a_user_using_its_credentials(): void
    {
        $credentials = [
            'login'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $activation = m::mock(ActivationInterface::class);
        $activation->shouldReceive('getCode')->once()->andReturn('a_random_code');

        $this->users->shouldReceive('findByCredentials')->with($credentials)->once()->andReturn($this->user);

        $this->activations->shouldReceive('create')->once()->andReturn($activation);
        $this->activations->shouldReceive('complete')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('dispatch')->twice();

        $this->assertTrue($this->sentinel->activate($credentials));
    }

    #[Test]
    public function it_can_check_if_the_user_is_logged_in(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn($this->user);

        $this->assertSame($this->user, $this->sentinel->check());
    }

    #[Test]
    public function it_can_check_if_the_user_is_logged_in_when_it_is_not(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn(null);

        $this->assertFalse($this->sentinel->check());
    }

    #[Test]
    public function it_can_force_the_check_if_the_user_is_logged_in(): void
    {
        $this->persistences->shouldReceive('check')->once();
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn($this->user);

        $checkpoint = m::mock(CheckpointInterface::class);

        $this->sentinel->addCheckpoint('activation', $checkpoint);

        $valid = $this->sentinel->forceCheck();

        $this->assertFalse($valid);
    }

    public function test_guest1(): void
    {
        $this->persistences->shouldReceive('check')->once();

        $this->assertTrue($this->sentinel->guest());
    }

    public function test_guest2(): void
    {
        $this->sentinel->setUser($this->user);

        $this->assertFalse($this->sentinel->guest());
    }

    #[Test]
    public function it_can_authenticate_a_user_using_its_credentials(): void
    {
        $credentials = [
            'login'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $this->persistences->shouldReceive('persist')->once();

        $this->users->shouldReceive('findByCredentials')->with($credentials)->once()->andReturn($this->user);
        $this->users->shouldReceive('validateCredentials')->once()->andReturn(true);
        $this->users->shouldReceive('recordLogin')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('until')->once()
            ->with('sentinel.authenticating', [$credentials]);

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.logging-in', $this->user);

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.logged-in', $this->user);

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.authenticated', $this->user);

        $this->assertSame($this->user, $this->sentinel->authenticate($credentials));
    }

    #[Test]
    public function it_can_authenticate_a_user_using_its_user_instance(): void
    {
        $this->persistences->shouldReceive('persist')->once();

        $this->users->shouldReceive('recordLogin')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('until')->once()
            ->with('sentinel.authenticating', [$this->user]);

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.logging-in', $this->user);

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.logged-in', $this->user);

        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with('sentinel.authenticated', $this->user);

        $this->assertSame($this->user, $this->sentinel->authenticate($this->user));
    }

    #[Test]
    public function it_will_not_authenticate_a_user_with_invalid_credentials(): void
    {
        $this->users->shouldReceive('findByCredentials')->once();

        $this->dispatcher->shouldReceive('until')->once();

        $this->assertFalse($this->sentinel->authenticate([]));
    }

    #[Test]
    public function it_can_authenticate_and_remember(): void
    {
        $credentials = [
            'login'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $this->persistences->shouldReceive('persist')->once();

        $this->users->shouldReceive('findByCredentials')->with($credentials)->once()->andReturn($this->user);
        $this->users->shouldReceive('validateCredentials')->once()->andReturn(true);
        $this->users->shouldReceive('recordLogin')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('until')->once();
        $this->dispatcher->shouldReceive('dispatch')->times(3);

        $this->assertSame($this->user, $this->sentinel->authenticateAndRemember($credentials));
    }

    #[Test]
    public function it_can_authenticate_when_checkpoints_are_disabled(): void
    {
        $this->sentinel->disableCheckpoints();

        $this->persistences->shouldReceive('persist')->once();
        $this->users->shouldReceive('recordLogin')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('until');
        $this->dispatcher->shouldReceive('dispatch');

        $this->assertSame($this->user, $this->sentinel->authenticate($this->user));
    }

    #[Test]
    public function it_cannot_authenticate_when_firing_an_event_fails(): void
    {
        $credentials = [
            'login'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $this->dispatcher->shouldReceive('until')->once()->andReturn(false);

        $this->assertFalse($this->sentinel->authenticate($credentials));
    }

    #[Test]
    public function it_cannot_authenticate_when_a_checkpoint_fails(): void
    {
        $checkpoint = m::mock(CheckpointInterface::class);
        $checkpoint->shouldReceive('login')->andReturn(false);
        $this->sentinel->addCheckpoint('foobar', $checkpoint);

        $this->dispatcher->shouldReceive('until');
        $this->dispatcher->shouldReceive('dispatch');

        $this->assertFalse($this->sentinel->authenticate($this->user));
    }

    #[Test]
    public function it_cannot_authenticate_when_a_login_fails(): void
    {
        $this->persistences->shouldReceive('persist')->once();

        $this->users->shouldReceive('recordLogin')->once()->andReturn(false);

        $this->dispatcher->shouldReceive('until');
        $this->dispatcher->shouldReceive('dispatch');

        $this->assertFalse($this->sentinel->authenticate($this->user));
    }

    #[Test]
    public function it_can_set_the_user_instance_on_the_sentinel_class(): void
    {
        $this->sentinel->setUser($this->user);

        $this->assertSame($this->user, $this->sentinel->getUser());
    }

    #[Test]
    public function it_can_bypass_all_checkpoints(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn($this->user);

        $activationCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint   = m::mock(CheckpointInterface::class);

        $this->sentinel->addCheckpoint('activation', $activationCheckpoint);
        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->sentinel->bypassCheckpoints(function ($sentinel) {
            $this->assertNotNull($sentinel->check());
        });
    }

    #[Test]
    public function it_can_bypass_a_specific_endpoint(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn($this->user);

        $activationCheckpoint = m::mock(CheckpointInterface::class);

        $throttleCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint->shouldReceive('check')->once();

        $this->sentinel->addCheckpoint('activation', $activationCheckpoint);
        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->sentinel->bypassCheckpoints(function ($s) {
            $this->assertNotNull($s->check());
        }, ['activation']);
    }

    #[Test]
    public function it_can_get_the_checkpoint_status(): void
    {
        $this->sentinel->disableCheckpoints();

        $this->assertFalse($this->sentinel->checkpointsStatus());

        $this->sentinel->enableCheckpoints();

        $this->assertTrue($this->sentinel->checkpointsStatus());
    }

    #[Test]
    public function it_can_disable_all_checkpoints(): void
    {
        $this->assertTrue($this->sentinel->checkpointsStatus());

        $this->sentinel->disableCheckpoints();

        $this->assertFalse($this->sentinel->checkpointsStatus());

        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn($this->user);

        $activationCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint   = m::mock(CheckpointInterface::class);

        $this->sentinel->addCheckpoint('activation', $activationCheckpoint);
        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->assertNotNull($this->sentinel->check());
    }

    #[Test]
    public function it_can_enable_all_checkpoints(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn($this->user);

        $this->assertTrue($this->sentinel->checkpointsStatus());

        $this->sentinel->disableCheckpoints();

        $this->assertFalse($this->sentinel->checkpointsStatus());

        $this->sentinel->enableCheckpoints();

        $this->assertTrue($this->sentinel->checkpointsStatus());

        $activationCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint   = m::mock(CheckpointInterface::class);

        $activationCheckpoint->shouldReceive('check')->once();

        $this->sentinel->addCheckpoint('activation', $activationCheckpoint);
        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->assertNotNull($this->sentinel->check());
    }

    #[Test]
    public function it_can_add_checkpoint_at_runtime(): void
    {
        $activationCheckpoint = m::mock(CheckpointInterface::class);

        $this->sentinel->addCheckpoint('activation', $activationCheckpoint);

        $this->assertCount(1, $this->sentinel->getCheckpoints());
        $this->assertArrayHasKey('activation', $this->sentinel->getCheckpoints());
    }

    #[Test]
    public function it_can_remove_checkpoint_at_runtime(): void
    {
        $activationCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint   = m::mock(CheckpointInterface::class);

        $this->sentinel->addCheckpoint('activation', $activationCheckpoint);
        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->sentinel->removeCheckpoint('activation');

        $this->assertCount(1, $this->sentinel->getCheckpoints());
        $this->assertArrayNotHasKey('activation', $this->sentinel->getCheckpoints());
    }

    #[Test]
    public function it_can_remove_checkpoints_at_runtime(): void
    {
        $activationCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint   = m::mock(CheckpointInterface::class);

        $this->sentinel->addCheckpoint('activation', $activationCheckpoint);
        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->sentinel->removeCheckpoints([
            'activation',
            'throttle',
        ]);

        $this->assertCount(0, $this->sentinel->getCheckpoints());
    }

    #[Test]
    public function the_check_checkpoint_will_be_invoked(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->andReturn($this->user);

        $throttleCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint->shouldReceive('check')->once()->andReturn(false);

        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->assertFalse($this->sentinel->check());
    }

    #[Test]
    public function the_login_checkpoint_will_be_invoked(): void
    {
        $this->dispatcher->shouldReceive('until')->once();

        $throttleCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint->shouldReceive('login')->once()->andReturn(false);

        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->assertFalse($this->sentinel->authenticate($this->user));
    }

    #[Test]
    public function the_fail_checkpoint_will_be_invoked(): void
    {
        $credentials = [
            'login'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $this->dispatcher->shouldReceive('until')->once();

        $this->users->shouldReceive('findByCredentials')->with($credentials)->once();

        $throttleCheckpoint = m::mock(CheckpointInterface::class);
        $throttleCheckpoint->shouldReceive('fail')->once()->andReturn(false);

        $this->sentinel->addCheckpoint('throttle', $throttleCheckpoint);

        $this->assertFalse($this->sentinel->authenticate($credentials));
    }

    #[Test]
    public function it_can_login_with_a_valid_user(): void
    {
        $this->persistences->shouldReceive('persist')->once();

        $this->dispatcher->shouldReceive('dispatch')->twice();

        $this->users->shouldReceive('recordLogin')->once()->andReturn(true);

        $this->assertSame($this->user, $this->sentinel->login($this->user));
    }

    #[Test]
    public function it_will_not_login_with_an_invalid_user(): void
    {
        $this->persistences->shouldReceive('persist')->once();

        $this->dispatcher->shouldReceive('dispatch')->once();

        $this->users->shouldReceive('recordLogin')->once()->andReturn(false);

        $this->assertFalse($this->sentinel->login($this->user));
    }

    public function it_will_ensure_the_user_is_not_defined_when_logging_out(): void
    {
        $this->persistences->shouldReceive('persist')->once();
        $this->persistences->shouldReceive('forget')->once();

        $this->users->shouldReceive('recordLogin')->once();
        $this->users->shouldReceive('recordLogout')->once();

        $this->sentinel->login($this->user);
        $this->sentinel->logout($this->user);

        $this->assertNull($this->sentinel->getUser(false));
    }

    #[Test]
    public function it_can_logout_the_current_user(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->once()->andReturn($this->user);
        $this->persistences->shouldReceive('forget')->once();

        $this->users->shouldReceive('recordLogout')->once()->andReturn(true);

        $this->dispatcher->shouldReceive('dispatch')->twice();

        $this->assertTrue($this->sentinel->logout($this->user));
    }

    #[Test]
    public function it_can_logout_the_user_on_the_other_devices(): void
    {
        $this->persistences->shouldReceive('check')->once()->andReturn('foobar');
        $this->persistences->shouldReceive('findUserByPersistenceCode')->with('foobar')->once()->andReturn($this->user);
        $this->persistences->shouldReceive('flush')->once();

        $this->dispatcher->shouldReceive('dispatch')->twice();

        $this->users->shouldReceive('recordLogout')->once()->andReturn(true);

        $this->assertTrue($this->sentinel->logout($this->user, true));
    }

    #[Test]
    public function it_can_maintain_a_user_session_after_logging_out_another_user(): void
    {
        $currentUser = m::mock(EloquentUser::class);

        $this->persistences->shouldReceive('persist')->once();
        $this->persistences->shouldReceive('flush')->once()->with($this->user, false);

        $this->dispatcher->shouldReceive('dispatch')->times(4);

        $this->users->shouldReceive('recordLogin')->once()->andReturn(true);

        $this->sentinel->login($currentUser);

        $this->sentinel->logout($this->user);

        $this->assertSame($currentUser, $this->sentinel->getUser(false));
    }

    #[Test]
    public function it_can_logout_an_invalid_user(): void
    {
        $user = null;

        $this->persistences->shouldReceive('check')->once();

        $this->dispatcher->shouldReceive('dispatch')->twice();

        $this->assertTrue($this->sentinel->logout($user, true));
    }

    #[Test]
    public function it_can_create_a_basic_response(): void
    {
        $response = json_encode(['response']);

        $this->sentinel->creatingBasicResponse(function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $this->sentinel->getBasicResponse());
    }

    #[Test]
    public function it_can_set_and_get_the_various_repositories(): void
    {
        $this->sentinel->setPersistenceRepository($persistence = m::mock(PersistenceRepositoryInterface::class));
        $this->sentinel->setUserRepository($users = m::mock(UserRepositoryInterface::class));
        $this->sentinel->setRoleRepository($roles = m::mock(RoleRepositoryInterface::class));
        $this->sentinel->setActivationRepository($activations = m::mock(ActivationRepositoryInterface::class));
        $this->sentinel->setReminderRepository($reminders = m::mock(ReminderRepositoryInterface::class));
        $this->sentinel->setThrottleRepository($throttling = m::mock(ThrottleRepositoryInterface::class));

        $this->assertSame($persistence, $this->sentinel->getPersistenceRepository());
        $this->assertSame($users, $this->sentinel->getUserRepository());
        $this->assertSame($roles, $this->sentinel->getRoleRepository());
        $this->assertSame($activations, $this->sentinel->getActivationRepository());
        $this->assertSame($reminders, $this->sentinel->getReminderRepository());
        $this->assertSame($throttling, $this->sentinel->getThrottleRepository());
    }

    #[Test]
    public function it_can_pass_method_calls_to_a_user_repository_directly(): void
    {
        $this->users->shouldReceive('findById')->once()->andReturn(m::mock(EloquentUser::class));

        $user = $this->sentinel->findById(1);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_pass_method_calls_to_a_user_repository_via_find_user_by(): void
    {
        $this->users->shouldReceive('findById')->once()->andReturn(m::mock(EloquentUser::class));

        $user = $this->sentinel->findUserById(1);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_pass_method_calls_to_a_role_repository_via_find_role_by(): void
    {
        $this->roles->shouldReceive('findById')->once()->andReturn(m::mock(EloquentRole::class));

        $user = $this->sentinel->findRoleById(1);

        $this->assertInstanceOf(EloquentRole::class, $user);
    }

    #[Test]
    public function it_can_pass_methods_via_the_user_repository_when_a_user_is_logged_in(): void
    {
        $this->user->shouldReceive('hasAccess')->andReturn(true);

        $this->persistences->shouldReceive('check')->andReturn(true);
        $this->persistences->shouldReceive('findUserByPersistenceCode')->andReturn($this->user);

        $this->assertTrue($this->sentinel->hasAccess());
    }

    #[Test]
    public function an_exception_will_be_thrown_when_activating_an_invalid_user(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No valid user was provided.');

        $this->sentinel->activate(20.00);
    }

    #[Test]
    public function an_exception_will_be_thrown_when_registering_with_an_invalid_closure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You must provide a closure or a boolean.');

        $this->sentinel->register([
            'email' => 'foo@example.com',
        ], 'invalid_closure');
    }

    // #[Test]
    // public function an_exception_will_be_thrown_when_trying_to_get_the_basic_response()
    // {
    //     $this->expectException(RuntimeException::class);
    //     $this->expectExceptionMessage('Attempting basic auth after headers have already been sent.');

    //     $this->sentinel->getBasicResponse();
    // }

    #[Test]
    public function an_exception_will_be_thrown_when_calling_methods_which_are_only_available_when_a_user_is_logged_in(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method Cartalyst\Sentinel\Sentinel::getRoles() can only be called if a user is logged in.');

        $this->persistences->shouldReceive('check')->once()->andReturn(null);

        $this->sentinel->getRoles();
    }

    #[Test]
    public function an_exception_will_be_thrown_when_calling_invalid_methods(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method Cartalyst\Sentinel\Sentinel::methodThatDoesntExist()');

        $this->sentinel->methodThatDoesntExist();
    }
}
