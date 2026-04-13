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

namespace Cartalyst\Sentinel\Tests\Permissions;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Cartalyst\Sentinel\Permissions\PermissionsTrait;
use Cartalyst\Sentinel\Permissions\PermissionsInterface;

class PermissionsTraitTest extends TestCase
{
    #[Test]
    public function it_can_set_the_secondary_permissions_at_runtime(): void
    {
        $secondaryPermissions = [
            [
                'test1' => true,
            ],
            [
                'test1' => false,
            ],
        ];

        $permissions = new PermissionsStub([
            'test'  => true,
            'test2' => false,
        ], $secondaryPermissions);

        $secondaryPermissions[] = [
            'test2' => true,
        ];

        $permissions->setSecondaryPermissions($secondaryPermissions);

        $this->assertTrue($permissions->hasAccess('test'));
        $this->assertFalse($permissions->hasAccess('test', 'test1'));
        $this->assertFalse($permissions->hasAccess(['test', 'test1']));

        $this->assertTrue($permissions->hasAnyAccess('test'));
        $this->assertTrue($permissions->hasAnyAccess('test', 'test1'));
        $this->assertTrue($permissions->hasAnyAccess(['test', 'test1']));
        $this->assertFalse($permissions->hasAnyAccess(['test4', 'test5']));

        $this->assertSame($secondaryPermissions, $permissions->getSecondaryPermissions());
    }

    #[Test]
    public function permissions_with_wildcards_can_be_used(): void
    {
        $permissions = new PermissionsStub([
            'user.add'    => true,
            'user.remove' => false,
        ]);

        $this->assertTrue($permissions->hasAccess('user.*'));
    }

    #[Test]
    public function personal_permissions_take_priority_over_pattern_match(): void
    {
        $permissions = new PermissionsStub([
            'user.*'      => true,
            'user.delete' => false,
        ]);

        $this->assertTrue($permissions->hasAccess('user.*'));
        $this->assertTrue($permissions->hasAccess('user.test'));
        $this->assertFalse($permissions->hasAccess('user.delete'));
    }

    #[Test]
    public function permissions_as_class_names_can_be_used(): void
    {
        $permissions = new PermissionsStub([
            'Foo\Bar\Baz@add,view'      => true,
            'Foo\Bar\Baz@update,remove' => false,
        ]);

        $this->assertTrue($permissions->hasAccess('Foo\Bar\Baz@add'));
        $this->assertTrue($permissions->hasAccess('Foo\Bar\Baz@view'));

        $this->assertFalse($permissions->hasAccess('Foo\Bar\Baz@update'));
        $this->assertFalse($permissions->hasAccess('Foo\Bar\Baz@remove'));
    }
}

class PermissionsStub implements PermissionsInterface
{
    use PermissionsTrait;

    protected function createPreparedPermissions(): array
    {
        $prepared = [];

        if (! empty($this->getSecondaryPermissions())) {
            foreach ($this->getSecondaryPermissions() as $permissions) {
                $this->preparePermissions($prepared, $permissions);
            }
        }

        if (! empty($this->getPermissions())) {
            $permissions = [];
            $this->preparePermissions($permissions, $this->getPermissions());
            $prepared = array_merge($prepared, $permissions);
        }

        return $prepared;
    }
}
