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

namespace Cartalyst\Sentinel\Tests\Users;

use Mockery as m;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Builder;
use Cartalyst\Sentinel\Users\EloquentUser;
use Cartalyst\Sentinel\Hashing\NativeHasher;
use Cartalyst\Sentinel\Hashing\HasherInterface;

class IlluminateUserRepositoryTest extends TestCase
{
    protected $hasher;

    protected $query;

    protected $model;

    protected $users;

    protected function setUp(): void
    {
        $this->hasher = m::mock(NativeHasher::class);

        $this->query = m::mock(Builder::class);

        $this->model = m::mock('Cartalyst\Sentinel\Users\EloquentUser');
        $this->model->shouldReceive('newQuery')->andReturn($this->query);

        $this->users = m::mock('Cartalyst\Sentinel\Users\IlluminateUserRepository[createModel]', [
            $this->hasher,
        ]);
        $this->users->shouldReceive('createModel')->andReturn($this->model);
    }

    protected function tearDown(): void
    {
        $this->hasher = null;
        $this->query  = null;
        $this->model  = null;
        $this->users  = null;

        m::close();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $users = m::mock('Cartalyst\Sentinel\Users\IlluminateUserRepository[createModel,findById]', [
            $this->hasher, null, 'UserMock',
        ]);

        $this->assertSame('UserMock', $users->getModel());
    }

    #[Test]
    public function it_can_find_a_user_by_its_id(): void
    {
        $this->query->shouldReceive('find')->with(1)->once()->andReturn($this->model);

        $user = $this->users->findById(1);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_find_a_user_by_its_credentials_1(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $this->query->shouldReceive('where')->with('email', 'foo@example.com')->once()->andReturn($this->model);
        $this->query->shouldReceive('first')->once()->andReturn($this->model);

        $user = $this->users->findByCredentials([
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_find_a_user_by_its_credentials_2(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email', 'username']);

        $this->query->shouldReceive('whereNested')->with(m::on(function ($argument) {
            $this->query->shouldReceive('where')->with('email', 'foo@example.com');
            $this->query->shouldReceive('orWhere')->with('username', 'foo@example.com');

            return $argument($this->query) === null;
        }))->andReturn($this->model);
        $this->query->shouldReceive('first')->once()->andReturn($this->model);

        $user = $this->users->findByCredentials([
            'login'    => 'foo@example.com',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_find_a_user_by_its_credentials_3(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $this->query->shouldReceive('whereNested')->with(m::on(function ($argument) {
            $this->query->shouldReceive('where')->with('email', 'foo@example.com');

            return $argument($this->query) === null;
        }))->andReturn($this->model);
        $this->query->shouldReceive('first')->once()->andReturn($this->model);

        $user = $this->users->findByCredentials([
            'login' => 'foo@example.com',
        ]);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_cannot_find_a_user_by_invalid_credentials_1(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $user = $this->users->findByCredentials([
            'password' => 'secret',
        ]);

        $this->assertNull($user);
    }

    #[Test]
    public function it_cannot_find_a_user_by_invalid_credentials_2(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $user = $this->users->findByCredentials([
            'username' => 'foo',
        ]);

        $this->assertNull($user);
    }

    #[Test]
    public function it_cannot_find_a_user_by_invalid_credentials_3(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $this->assertNull($this->users->findByCredentials([]));
    }

    #[Test]
    public function it_can_find_a_user_by_its_persistence_code(): void
    {
        $this->query->shouldReceive('whereHas')->with('persistences', m::on(function ($argument) {
            $this->query->shouldReceive('where')->with('code', 'foobar');

            return $argument($this->query) === null;
        }))->andReturn($this->model);
        $this->model->shouldReceive('first')->once()->andReturn($this->model);

        $user = $this->users->findByPersistenceCode('foobar');

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_record_the_login(): void
    {
        $this->model->shouldReceive('setAttribute');
        $this->model->shouldReceive('save')->once()->andReturn(true);

        $this->assertTrue($this->users->recordLogin($this->model));
    }

    #[Test]
    public function it_can_record_the_logout(): void
    {
        $this->model->shouldReceive('save')->once()->andReturn(true);

        $this->assertTrue($this->users->recordLogout($this->model));
    }

    #[Test]
    public function it_can_validate_the_credentials(): void
    {
        $this->model->shouldReceive('getAttribute')->andReturn('secret');

        $this->hasher->shouldReceive('check')->with('secret', 'secret')->once()->andReturn(true);

        $valid = $this->users->validateCredentials($this->model, [
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ]);

        $this->assertTrue($valid);
    }

    #[Test]
    public function it_can_check_if_the_user_is_valid_for_being_created(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $credentials = [
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $valid = $this->users->validForCreation($credentials);

        $this->assertTrue($valid);
    }

    #[Test]
    public function it_can_check_if_the_user_is_valid_for_being_updated(): void
    {
        $user = $this->fakeUser();

        $user->shouldReceive('getUserId')->once()->andReturn(1);

        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $credentials = [
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ];

        $valid = $this->users->validForUpdate($user, $credentials);

        $this->assertTrue($valid);
    }

    #[Test]
    public function it_can_create_a_user_using_login(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);
        $this->model->shouldReceive('fill');
        $this->model->shouldReceive('save')->once();

        $this->hasher->shouldReceive('hash')->once()->with('secret')->andReturn(password_hash('secret', PASSWORD_DEFAULT));

        $user = $this->users->create([
            'login'    => 'foo@example.com',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_create_a_user(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);
        $this->model->shouldReceive('fill');
        $this->model->shouldReceive('save')->once();

        $this->hasher->shouldReceive('hash')->once()->with('secret')->andReturn(password_hash('secret', PASSWORD_DEFAULT));

        $user = $this->users->create([
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_create_a_user_with_valid_callback(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);
        $this->model->shouldReceive('fill');
        $this->model->shouldReceive('save')->once();

        $this->hasher->shouldReceive('hash')->once()->with('secret')->andReturn(password_hash('secret', PASSWORD_DEFAULT));

        $user = $this->users->create([
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ], function ($user) {
            return true;
        });

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_will_not_create_a_user_with_an_invalid_callback(): void
    {
        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);
        $this->model->shouldReceive('fill');

        $this->hasher->shouldReceive('hash')->once()->with('secret')->andReturn(password_hash('secret', PASSWORD_DEFAULT));

        $user = $this->users->create([
            'email'    => 'foo@example.com',
            'password' => 'secret',
        ], function ($user) {
            return false;
        });

        $this->assertNull($user);
    }

    #[Test]
    public function it_can_update_a_user_by_instance(): void
    {
        $user = $this->fakeUser();
        $user->shouldReceive('getLoginNames')->andReturn(['email']);
        $user->shouldReceive('fill');
        $user->shouldReceive('save')->once();

        $user = $this->users->update($user, [
            'email' => 'foo1@example.com',
        ]);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_update_a_user_by_id(): void
    {
        $user = $this->fakeUser();
        $user->shouldReceive('getLoginNames')->andReturn(['email']);
        $user->shouldReceive('fill');
        $user->shouldReceive('save')->once();

        $this->query->shouldReceive('find')->once()->with(1)->andReturn($user);

        $user = $this->users->update(1, [
            'email' => 'foo1@example.com',
        ]);

        $this->assertInstanceOf(EloquentUser::class, $user);
    }

    #[Test]
    public function it_can_set_and_get_the_hashing_strategy(): void
    {
        $this->assertInstanceOf(NativeHasher::class, $this->users->getHasher());

        $this->users->setHasher(m::mock(HasherInterface::class));

        $this->assertInstanceOf(HasherInterface::class, $this->users->getHasher());
    }

    #[Test]
    public function an_exception_will_be_thrown_when_checking_if_the_user_is_valid_for_being_created_without_a_login(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No [login] credential was passed.');

        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $credentials = [
            'password' => 'secret',
        ];

        $this->users->validForCreation($credentials);
    }

    #[Test]
    public function an_exception_will_be_thrown_when_checking_if_the_user_is_valid_for_being_created_without_a_password(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You have not passed a [password].');

        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $credentials = [
            'email' => 'foo@example.com',
        ];

        $this->users->validForCreation($credentials);
    }

    #[Test]
    public function an_exception_will_be_thrown_when_checking_if_the_user_is_valid_for_being_created_with_an_empty_password(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You have not passed a [password].');

        $this->model->shouldReceive('getLoginNames')->andReturn(['email']);

        $credentials = [
            'email'    => 'foo@example.com',
            'password' => null,
        ];

        $this->users->validForCreation($credentials);
    }

    protected function fakeUser()
    {
        return m::mock('Cartalyst\Sentinel\Users\EloquentUser');
    }
}
