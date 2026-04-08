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

namespace Cartalyst\Sentinel\Users;

use Closure;

interface UserRepositoryInterface
{
    /**
     * Finds a user by the given primary key.
     */
    public function findById(int $id): ?UserInterface;

    /**
     * Finds a user by the given credentials.
     */
    public function findByCredentials(array $credentials): ?UserInterface;

    /**
     * Finds a user by the given persistence code.
     */
    public function findByPersistenceCode(string $code): ?UserInterface;

    /**
     * Records a login for the given user.
     */
    public function recordLogin(UserInterface $user): bool;

    /**
     * Records a logout for the given user.
     */
    public function recordLogout(UserInterface $user): bool;

    /**
     * Validate the password of the given user.
     */
    public function validateCredentials(UserInterface $user, array $credentials): bool;

    /**
     * Validate if the given user is valid for creation.
     */
    public function validForCreation(array $credentials): bool;

    /**
     * Validate if the given user is valid for updating.
     *
     * @param UserInterface|int $user
     */
    public function validForUpdate($user, array $credentials): bool;

    /**
     * Creates a user.
     */
    public function create(array $credentials, ?Closure $callback = null): ?UserInterface;

    /**
     * Updates a user.
     *
     * @param UserInterface|int $user
     */
    public function update($user, array $credentials): UserInterface;
}
