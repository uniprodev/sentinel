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

namespace Cartalyst\Sentinel\Reminders;

use Illuminate\Database\Eloquent\Model;
use Cartalyst\Sentinel\Users\UserInterface;

interface ReminderRepositoryInterface
{
    /**
     * Create a new reminder record and code.
     *
     *
     * @return Model
     */
    public function create(UserInterface $user);

    /**
     * Gets the reminder for the given user.
     *
     *
     * @return Model|null
     */
    public function get(UserInterface $user, ?string $code = null);

    /**
     * Check if a valid reminder exists.
     */
    public function exists(UserInterface $user, ?string $code = null): bool;

    /**
     * Complete reminder for the given user.
     */
    public function complete(UserInterface $user, string $code, string $password): bool;

    /**
     * Remove expired reminder codes.
     */
    public function removeExpired(): bool;
}
