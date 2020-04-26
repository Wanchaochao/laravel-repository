<?php

namespace Littlebug\Repository\Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager;
use Littlebug\Repository\Tests\Stubs\UserModel;
use Littlebug\Repository\Tests\Stubs\PostModel;
use Illuminate\Support\Traits\CapsuleManagerTrait;

abstract class AbstractRepositoryTest extends TestCase
{
    use CapsuleManagerTrait;

    /**
     * 初始化设置
     */
    protected function setUp()
    {
        $this->container = new Container();
        $this->setupDatabase(new Manager($this->container));
        $this->migrate();
        $this->seed();
    }

    /**
     * 设置数据库配置信息
     *
     * @param Manager $db
     */
    protected function setupDatabase(Manager $db)
    {
        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
    }

    /**
     * 创建表结构
     */
    protected function migrate()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('user_id');
            $table->string('name');
            $table->string('email');
            $table->integer('age');
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('post_id');
            $table->integer('user_id');
            $table->integer('parent_id')->default(0);
            $table->string('name');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Get Schema Builder.
     *
     * @return Builder
     */
    protected function schema()
    {
        return Model::resolveConnection()->getSchemaBuilder();
    }

    /**
     * 创建基础数据
     */
    protected function seed()
    {
        $evsign = UserModel::create([
            'name'  => 'evsign',
            'email' => 'evsign.alex@gmail.com',
            'age'   => '25',
        ]);

        $omranic = UserModel::create([
            'name'  => 'omranic',
            'email' => 'me@omranic.com',
            'age'   => '26',
        ]);

        $ionut = UserModel::create([
            'name'  => 'ionut',
            'email' => 'ionutz2k@gmail.com',
            'age'   => '24',
        ]);

        $anotherIonut = UserModel::create([
            'name'  => 'ionut',
            'email' => 'ionut@example.com',
            'age'   => '28',
        ]);

        $evsign->posts()->saveMany([
            new PostModel(['name' => 'first post']),
            new PostModel(['name' => 'second post']),
        ]);

        $omranic->posts()->saveMany([
            new PostModel(['name' => 'third post']),
            new PostModel(['name' => 'fourth post']),
        ]);

        $ionut->posts()->saveMany([
            new PostModel(['name' => 'fifth post']),
            new PostModel(['name' => 'sixth post']),
        ]);

        $anotherIonut->posts()->saveMany([
            new PostModel(['name' => 'seventh post']),
            new PostModel(['name' => 'eighth post']),
        ]);
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('posts');
        unset($this->container);
    }
}
