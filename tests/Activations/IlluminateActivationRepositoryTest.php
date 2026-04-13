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

namespace Cartalyst\Sentinel\Tests\Activations;

use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Builder;
use Cartalyst\Sentinel\Users\UserInterface;
use Cartalyst\Sentinel\Activations\EloquentActivation;
use Cartalyst\Sentinel\Activations\ActivationRepositoryInterface;
use Cartalyst\Sentinel\Activations\IlluminateActivationRepository;

class IlluminateActivationRepositoryTest extends TestCase
{
    /**
     * The Activations repository instance.
     *
     * @var ActivationRepositoryInterface
     */
    protected $activations;

    /**
     * The Eloquent Activation instance.
     *
     * @var EloquentActivation
     */
    protected $model;

    /**
     * The Builder Instance.
     *
     * @var Builder;
     */
    protected $query;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->query = m::mock(Builder::class);

        $this->model = m::mock(EloquentActivation::class);
        $this->model->shouldReceive('newQuery')->andReturn($this->query);

        $this->activations = m::mock('Cartalyst\Sentinel\Activations\IlluminateActivationRepository[createModel]', ['ActivationModelMock', 259200]);
        $this->activations->shouldReceive('createModel')->andReturn($this->model);
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $activations = new IlluminateActivationRepository('ActivationModelMock', 259200);

        $this->assertSame('ActivationModelMock', $activations->getModel());
    }

    #[Test]
    public function it_can_create_an_activation_code(): void
    {
        $this->model->shouldReceive('fill');
        $this->model->shouldReceive('setAttribute');
        $this->model->shouldReceive('save');

        $user = $this->getUserMock();

        $activation = $this->activations->create($user);

        $this->assertInstanceOf(EloquentActivation::class, $activation);
    }

    #[Test]
    public function it_can_determine_if_an_activation_exists(): void
    {
        $this->query->shouldReceive('where')->with('user_id', '1')->andReturnSelf();
        $this->query->shouldReceive('where')->with('completed', false)->andReturnSelf();
        $this->query->shouldReceive('where')->with('created_at', '>', m::type(Carbon::class))->andReturnSelf();
        $this->query->shouldReceive('when')->with('foo', m::on(function ($argument) {
            $this->query->shouldReceive('where')->with('code', 'bar')->andReturn(true);

            return $argument($this->query, 'bar') == $this->query;
        }))->andReturnSelf();
        $this->query->shouldReceive('first')->once();

        $user = $this->getUserMock();

        $status = $this->activations->exists($user, 'foo');

        $this->assertFalse($status);
    }

    #[Test]
    public function it_can_complete_an_activation(): void
    {
        $activation = m::mock(EloquentActivation::class);
        $activation->shouldReceive('fill')->once();
        $activation->shouldReceive('save')->once();

        $this->query->shouldReceive('where')->with('user_id', '1')->andReturnSelf();
        $this->query->shouldReceive('where')->with('code', 'foobar')->andReturnSelf();
        $this->query->shouldReceive('where')->with('completed', false)->andReturnSelf();
        $this->query->shouldReceive('where')->with('created_at', '>', m::type(Carbon::class))->andReturnSelf();
        $this->query->shouldReceive('first')->once()->andReturn($activation);

        $user = $this->getUserMock();

        $status = $this->activations->complete($user, 'foobar');

        $this->assertTrue($status);
    }

    #[Test]
    public function it_cannot_complete_an_activation_that_has_expired(): void
    {
        $this->query->shouldReceive('where')->with('user_id', '1')->andReturnSelf();
        $this->query->shouldReceive('where')->with('code', 'foobar')->andReturnSelf();
        $this->query->shouldReceive('where')->with('completed', false)->andReturnSelf();
        $this->query->shouldReceive('where')->with('created_at', '>', m::type(Carbon::class))->andReturnSelf();
        $this->query->shouldReceive('first')->once();

        $user = $this->getUserMock();

        $status = $this->activations->complete($user, 'foobar');

        $this->assertFalse($status);
    }

    #[Test]
    public function it_can_determine_if_an_activation_is_completed(): void
    {
        $this->query->shouldReceive('where')->with('user_id', '1')->andReturnSelf();
        $this->query->shouldReceive('where')->with('completed', true)->andReturnSelf();
        $this->query->shouldReceive('exists')->once()->andReturn(true);

        $user = $this->getUserMock();

        $status = $this->activations->completed($user);

        $this->assertTrue($status);
    }

    #[Test]
    public function it_can_determine_if_an_activation_is_not_completed(): void
    {
        $this->query->shouldReceive('where')->with('user_id', '1')->andReturnSelf();
        $this->query->shouldReceive('where')->with('completed', true)->andReturnSelf();
        $this->query->shouldReceive('exists')->once()->andReturn(false);

        $user = $this->getUserMock();

        $status = $this->activations->completed($user);

        $this->assertFalse($status);
    }

    #[Test]
    public function it_can_remove_non_completed_activations(): void
    {
        $this->query->shouldReceive('where')->with('user_id', '1')->andReturnSelf();
        $this->query->shouldReceive('where')->with('completed', true)->andReturnSelf();
        $this->query->shouldReceive('first')->once()->andReturn(false);

        $user = $this->getUserMock();

        $status = $this->activations->remove($user);

        $this->assertFalse($status);
    }

    #[Test]
    public function it_can_remove_completed_activations(): void
    {
        $activation = m::mock(EloquentActivation::class);

        $this->query->shouldReceive('where')->with('user_id', '1')->andReturnSelf();
        $this->query->shouldReceive('where')->with('completed', true)->andReturnSelf();
        $this->query->shouldReceive('first')->once()->andReturn($activation);

        $activation->shouldReceive('delete')->once()->andReturn(true);

        $user = $this->getUserMock();

        $status = $this->activations->remove($user);

        $this->assertTrue($status);
    }

    #[Test]
    public function it_can_remove_expired_activations(): void
    {
        $this->query->shouldReceive('where')->with('completed', false)->andReturnSelf();
        $this->query->shouldReceive('where')->with('created_at', '<', m::type(Carbon::class))->andReturnSelf();

        $this->query->shouldReceive('delete')->once()->andReturn(true);

        $status = $this->activations->removeExpired();

        $this->assertTrue($status);
    }

    protected function getUserMock(): UserInterface
    {
        $user = m::mock(UserInterface::class);

        $user->shouldReceive('getUserId')->once()->andReturn(1);

        return $user;
    }
}
