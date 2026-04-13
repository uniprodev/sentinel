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
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\Attributes\Test;
use Cartalyst\Sentinel\Users\EloquentUser;
use Cartalyst\Sentinel\Roles\RoleInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EloquentUserTest extends TestCase
{
    protected EloquentUser $user;

    protected function setUp(): void
    {
        $this->user = new EloquentUser;
    }

    protected function tearDown(): void
    {
        m::close();
    }

    #[Test]
    public function it_can_get_the_user_permissions_from_the_accessor(): void
    {
        $this->user->slug = 'foo';

        $permissions = ['foo' => true];

        $this->user->permissions = $permissions;

        $this->assertSame($permissions, $this->user->permissions);
    }

    #[Test]
    public function it_can_set_and_get_the_persistable_key(): void
    {
        $this->user->setPersistableKey('foo_id');

        $this->assertSame('foo_id', $this->user->getPersistableKey());
    }

    #[Test]
    public function it_can_set_and_get_the_persistable_relationship(): void
    {
        $this->user->setPersistableRelationship('foo_persistences');

        $this->assertSame('foo_persistences', $this->user->getPersistableRelationship());
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     */
    public function it_can_set_and_get_the_roles_model(): void
    {
        $this->user->setRolesModel('RoleMock');

        $this->assertSame('RoleMock', $this->user->getRolesModel());
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     */
    public function it_can_set_and_get_the_persistences_model(): void
    {
        $this->user->setPersistencesModel('PersistenceMock');

        $this->assertSame('PersistenceMock', $this->user->getPersistencesModel());
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     */
    public function it_can_set_and_get_the_activations_model(): void
    {
        $this->user->setActivationsModel('ActivationMock');

        $this->assertSame('ActivationMock', $this->user->getActivationsModel());
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     */
    public function it_can_set_and_get_the_reminders_model(): void
    {
        $this->user->setRemindersModel('ReminderMock');

        $this->assertSame('ReminderMock', $this->user->getRemindersModel());
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     */
    public function it_can_set_and_get_the_throttling_model(): void
    {
        $this->user->setThrottlingModel('ThrottleMock');

        $this->assertSame('ThrottleMock', $this->user->getThrottlingModel());
    }

    #[Test]
    public function it_can_get_the_user_id(): void
    {
        $this->addMockConnection($this->user);

        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileInsertGetId');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processInsertGetId')->andReturn(1);

        $this->user->save();

        $this->assertSame(1, $this->user->getUserId());
    }

    #[Test]
    public function it_can_get_the_persistable_id(): void
    {
        $this->addMockConnection($this->user);

        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileInsertGetId');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processInsertGetId')->andReturn(1);

        $this->user->save();

        $this->assertSame('1', $this->user->getPersistableId());
    }

    #[Test]
    public function it_can_get_the_user_login(): void
    {
        $this->user->email = 'foo@example.com';

        $this->addMockConnection($this->user);

        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileInsertGetId');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processInsertGetId')->andReturn(1);

        $this->user->save();

        $this->assertSame('foo@example.com', $this->user->getUserLogin());
    }

    #[Test]
    public function it_can_get_the_user_login_names(): void
    {
        $this->assertSame(['email'], $this->user->getLoginNames());
    }

    #[Test]
    public function it_can_get_the_user_login_name(): void
    {
        $this->assertSame('email', $this->user->getUserLoginName());
    }

    #[Test]
    public function it_can_get_the_user_password(): void
    {
        $this->user->password = 'foobar';

        $this->assertSame('foobar', $this->user->getUserPassword());
    }

    #[Test]
    public function it_can_generate_a_persistence_code(): void
    {
        $this->assertSame(32, strlen($this->user->generatePersistenceCode()));
    }

    #[Test]
    public function it_can_get_the_roles_relationship(): void
    {
        $this->addMockConnection($this->user);

        $this->assertInstanceOf(BelongsToMany::class, $this->user->roles());
    }

    #[Test]
    public function it_can_get_the_persistences_relationship(): void
    {
        $this->addMockConnection($this->user);

        $this->assertInstanceOf(HasMany::class, $this->user->persistences());
    }

    #[Test]
    public function it_can_get_the_reminders_relationship(): void
    {
        $this->addMockConnection($this->user);

        $this->assertInstanceOf(HasMany::class, $this->user->reminders());
    }

    #[Test]
    public function it_can_get_the_activations_relationship(): void
    {
        $this->addMockConnection($this->user);

        $this->assertInstanceOf(HasMany::class, $this->user->activations());
    }

    #[Test]
    public function it_can_get_the_throttles_relationship(): void
    {
        $this->addMockConnection($this->user);

        $this->assertInstanceOf(HasMany::class, $this->user->throttle());
    }

    #[Test]
    public function it_can_check_if_user_is_in_role_using_role_slugs(): void
    {
        $this->user->id = 0;

        $this->addMockConnection($this->user);

        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileInsertGetId');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processSelect')->andReturn([
            ['id' => 1, 'slug' => 'role1'],
            ['id' => 2, 'slug' => 'role2'],
            ['id' => 3, 'slug' => 'role3'],
        ]);

        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileSelect');
        $this->user->getConnection()->shouldReceive('select');

        $this->assertTrue($this->user->inRole('role1'));
        $this->assertTrue($this->user->inRole('role2'));
        $this->assertTrue($this->user->inRole('role3'));
        $this->assertFalse($this->user->inRole('role4'));
    }

    #[Test]
    public function it_can_check_if_user_is_in_role_using_an_array_of_role_slugs(): void
    {
        $this->user->id = 0;

        $this->addMockConnection($this->user);

        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileInsertGetId');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processSelect')->andReturn([
            ['id' => 1, 'slug' => 'role1'],
            ['id' => 2, 'slug' => 'role2'],
            ['id' => 3, 'slug' => 'role3'],
        ]);

        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileSelect');
        $this->user->getConnection()->shouldReceive('select');

        $this->assertTrue($this->user->inAnyRole(['role1', 'role2']));
        $this->assertTrue($this->user->inAnyRole(['role3', 'role4']));
        $this->assertFalse($this->user->inAnyRole(['role5', 'role6']));
    }

    #[Test]
    public function it_can_check_if_user_is_in_role_using_role_instances(): void
    {
        $this->user->id = 0;

        $this->addMockConnection($this->user);
        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileInsertGetId');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processSelect')->andReturn([
            ['id' => 1, 'slug' => 'role1'],
            ['id' => 2, 'slug' => 'role2'],
            ['id' => 3, 'slug' => 'role3'],
        ]);

        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileSelect');
        $this->user->getConnection()->shouldReceive('select');

        $role1 = m::mock(RoleInterface::class);
        $role2 = m::mock(RoleInterface::class);
        $role3 = m::mock(RoleInterface::class);
        $role4 = m::mock(RoleInterface::class);

        $role1->shouldReceive('getRoleId')->once()->andReturn(1);
        $role2->shouldReceive('getRoleId')->once()->andReturn(2);
        $role3->shouldReceive('getRoleId')->once()->andReturn(3);
        $role4->shouldReceive('getRoleId')->once()->andReturn(4);

        $this->assertTrue($this->user->inRole($role1));
        $this->assertTrue($this->user->inRole($role2));
        $this->assertTrue($this->user->inRole($role3));
        $this->assertFalse($this->user->inRole($role4));
    }

    #[Test]
    public function it_can_check_if_user_is_in_role_using_an_array_of_role_instances(): void
    {
        $this->user->id = 0;

        $this->addMockConnection($this->user);

        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileInsertGetId');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processSelect')->andReturn([
            ['id' => 1, 'slug' => 'foobar'],
            ['id' => 2, 'slug' => 'foo'],
            ['id' => 3, 'slug' => 'bar'],
        ]);
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileSelect');
        $this->user->getConnection()->shouldReceive('select');

        $foobar = m::mock(RoleInterface::class);
        $foo    = m::mock(RoleInterface::class);
        $bar    = m::mock(RoleInterface::class);
        $baz    = m::mock(RoleInterface::class);
        $abc    = m::mock(RoleInterface::class);
        $def    = m::mock(RoleInterface::class);
        $ghi    = m::mock(RoleInterface::class);

        $foobar->shouldReceive('getRoleId')->once()->andReturn(1);
        $foo->shouldReceive('getRoleId')->once()->andReturn(2);
        $bar->shouldNotReceive('getRoleId');
        $abc->shouldReceive('getRoleId')->once()->andReturn(4);
        $def->shouldReceive('getRoleId')->once()->andReturn(5);
        $ghi->shouldReceive('getRoleId')->once()->andReturn(6);

        $this->assertFalse($this->user->inAnyRole([$abc, $def]));
        $this->assertTrue($this->user->inAnyRole([$ghi, $foobar]));
        $this->assertTrue($this->user->inAnyRole([$foo, $bar]));
    }

    #[Test]
    public function it_can_get_the_roles_of_a_user(): void
    {
        $this->user->id = 0;

        $this->addMockConnection($this->user);

        $this->user->getConnection()->shouldReceive('getName');
        $this->user->getConnection()->shouldReceive('select');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileSelect');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getBitwiseOperators')->andReturn([]);
        $this->user->getConnection()->getPostProcessor()->shouldReceive('processSelect')->andReturn([]);

        $this->assertInstanceOf(Collection::class, $this->user->getRoles());
    }

    #[Test]
    public function it_can_pass_methods_to_parent(): void
    {
        $this->addMockConnection($this->user);
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('wrap');
        $this->user->getConnection()->shouldReceive('raw');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('compileUpdate');
        $this->user->getConnection()->getQueryGrammar()->shouldReceive('prepareBindingsForUpdate')->andReturn([]);
        $this->user->getConnection()->shouldReceive('update')->andReturn(true);

        $this->assertTrue($this->user->increment('test'));
    }

    #[Test]
    public function it_can_pass_methods_to_permissions_instance(): void
    {
        $mockRole = m::mock(EloquentRole::class);
        $mockRole->shouldReceive('permissions')->andReturn([]);

        $permissions = ['foo' => true, 'bar' => false];

        $this->user->roles = [$mockRole];

        $mockRole->shouldReceive('getPermissions')
            ->once()
            ->andReturn($permissions);

        $this->assertTrue($this->user->hasAccess('foo'));
        $this->assertFalse($this->user->hasAccess('bar'));
    }

    #[Test]
    public function it_will_ignore_empty_secondary_permissions(): void
    {
        $mockRole = m::mock(EloquentRole::class);
        $mockRole->shouldReceive('permissions')->andReturn(null);

        $permissions = ['foo' => true, 'bar' => false];

        $this->user->roles = [$mockRole];

        $mockRole->shouldReceive('getPermissions')
            ->once()
            ->andReturn($permissions);

        $this->assertTrue($this->user->hasAccess('foo'));
        $this->assertFalse($this->user->hasAccess('bar'));
    }

    #[Test]
    public function it_can_delete_a_user(): void
    {
        $user         = m::mock('Cartalyst\Sentinel\Users\EloquentUser[roles,persistences,activations,reminders,throttle]');
        $user->exists = true;

        $this->addMockConnection($user);
        $user->getConnection()->getQueryGrammar()->shouldReceive('compileDelete');
        $user->getConnection()->getQueryGrammar()->shouldReceive('prepareBindingsForDelete')->andReturn([]);
        $user->getConnection()->shouldReceive('delete')->once();

        $user->shouldReceive('persistences')->once()->andReturn($persistences = m::mock(HasMany::class));
        $user->shouldReceive('activations')->once()->andReturn($activations = m::mock(HasMany::class));
        $user->shouldReceive('reminders')->once()->andReturn($reminders = m::mock(HasMany::class));
        $user->shouldReceive('throttle')->once()->andReturn($throttle = m::mock(HasMany::class));
        $user->shouldReceive('roles')->once()->andReturn($roles = m::mock(BelongsToMany::class));

        $persistences->shouldReceive('delete')->once();
        $activations->shouldReceive('delete')->once();
        $reminders->shouldReceive('delete')->once();
        $throttle->shouldReceive('delete')->once();
        $roles->shouldReceive('detach')->once();

        $this->assertTrue($user->delete());
    }

    protected function addMockConnection($model): void
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('connection')->andReturn($connection = m::mock(Connection::class));

        $model->setConnectionResolver($resolver);

        $grammar = m::mock(Grammar::class);
        $grammar->shouldReceive('isExpression');

        $model->getConnection()->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $model->getConnection()->shouldReceive('getPostProcessor')->andReturn($processor = m::mock(Processor::class));

        $model->getConnection()->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new Builder($connection, $grammar, $processor);
        });
    }
}
