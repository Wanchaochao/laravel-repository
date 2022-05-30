<?php

namespace Littlebug\Repository\Tests;

use Illuminate\Pagination\LengthAwarePaginator;
use Littlebug\Repository\Tests\Stubs\UserModel;
use Littlebug\Repository\Tests\Stubs\UserRepository;

class UserRepositoryTest extends AbstractRepositoryTest
{
    /**
     * 测试创建数据
     *
     * @throws \Littlebug\Repository\Exception
     */
    public function testCreate()
    {
        $user = UserRepository::instance()->create([
            'name'   => '测试数据',
            'email'  => 'mytest@qq.com',
            'age'    => 27,
            'status' => 1,
            'config' => '{}',
        ]);
        dump($user);
        $this->assertArrayHasKey('user_id', $user);
    }

    /**
     * 测试修改数据
     *
     * @throws \Littlebug\Repository\Exception
     */
    public function testUpdate()
    {
        $row = UserRepository::instance()->update(4, [
            'status' => 2,
            'name'   => 'test2',
        ]);

        $user = UserRepository::instance()->find(4);
        dump($row, $user);

        $this->assertEquals(1, $row);
        $this->assertEquals(2, $user['status']);
        $this->assertEquals('test2', $user['name']);
    }

    /**
     * 测试删除数据
     *
     * @throws \Littlebug\Repository\Exception|\Exception
     */
    public function testDelete()
    {
        $row  = UserRepository::instance()->delete(4);
        $user = UserRepository::instance()->find(4);
        dump($row, $user);
        $this->assertEquals(1, $row);
        $this->assertEquals(null, $user);
    }

    public function testFind()
    {
        $user = UserRepository::instance()->find([
            'status'  => 1,
            'user_id' => 1,
        ]);

        dump($user);
        $this->assertNotEquals(null, $user);

        $user = UserRepository::instance()->find([
            'status'  => 1,
            'user_id' => 2,
        ], ['name', 'age', 'posts_count', 'posts' => ['*']]);
        dump($user);
        $this->assertNotEquals(null, $user);
        $this->assertCount(2, $user['posts']);
        $this->assertEquals(2, $user['posts_count']);
    }

    public function testFindBy()
    {
        $name = UserRepository::instance()->findBy([
            'status'  => 1,
            'user_id' => 1,
        ], 'name');
        dump($name);
        $this->assertNotEquals(null, $name);
    }

    public function testFindAll()
    {
        $users = UserRepository::instance()->findAll([
            'status:eq'     => 1,
            'created_at:gt' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        dump($users);

        $this->assertCount(4, $users);
    }

    public function testFindAllBy()
    {
        $names = UserRepository::instance()->findAllBy([
            'status:eq'     => 1,
            'created_at:gt' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ], 'name');

        dump($names);

        $this->assertCount(4, $names);
    }

    public function testPaginate()
    {
        $pagination = UserRepository::instance()->paginate();
        $this->assertEquals(true, $pagination instanceof LengthAwarePaginator);
        $this->assertCount(4, $pagination->items());
    }

    public function testFilterCondition()
    {
        $conditions = UserRepository::instance()->filterCondition([
            'name'      => '',
            'name:like' => 'test',
            'age'       => null,
            'age:eq'    => 20,
            'or'        => [
                'name'      => '',
                'age'       => ' ',
                'name:like' => '123',
                'and'       => [
                    'status'    => null,
                    'age'       => 1,
                    'status:in' => [1, 2, 3],
                ],
            ],
        ]);

        dump($conditions);
        $this->assertArrayNotHasKey('name', $conditions);
        $this->assertArrayNotHasKey('age', $conditions);
        $this->assertArrayHasKey('name:like', $conditions);
        $this->assertArrayHasKey('age:eq', $conditions);
    }

    public function testGetTableColumns()
    {
        $columns = UserRepository::instance()->getTableColumns();
        dump($columns);
        $this->assertArrayHasKey('user_id', $columns);
    }

    public function testGetPrimaryKeyCondition()
    {
        // int 转 主键查询
        $conditions = UserRepository::instance()->getPrimaryKeyCondition(1);
        dump($conditions);
        $this->assertArrayHasKey('user_id', $conditions);

        // 布尔值 转 主键查询
        $conditions = UserRepository::instance()->getPrimaryKeyCondition(true);
        dump($conditions);
        $this->assertArrayHasKey('user_id', $conditions);
        $this->assertEquals(1, $conditions['user_id']);

        // 索引数组 转 主键查询
        $conditions = UserRepository::instance()->getPrimaryKeyCondition([1, 2, 3, 4]);
        dump($conditions);
        $this->assertArrayHasKey('user_id', $conditions);
    }

    public function testGetValidColumns()
    {
        $data = UserRepository::instance()->getValidColumns([
            'status'      => 2,
            'status_name' => 'test',
            'age'         => 21,
            'age_name'    => 21,
            'user_id'     => 1,
            'name'        => '456',
            'user_name'   => 789,
            'email'       => 123,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        dump($data);
        $this->assertArrayHasKey('age', $data);
        $this->assertArrayNotHasKey('user_id', $data);
        $this->assertCount(6, $data);
    }

    public function testGetFieldArray()
    {
        $columns = UserRepository::instance()->getFieldArray('posts.user.name');
        dump($columns);
        $this->assertEquals(true, is_array($columns));
        $this->assertArrayHasKey('posts', $columns);
    }

    public function testNotSelectAll()
    {
        $this->assertEquals(true, UserRepository::instance()->notSelectAll(['user', 'name'], 'users'));
        $this->assertEquals(false, UserRepository::instance()->notSelectAll(['*'], 'users'));
    }

    public function testFirst()
    {
        $user = UserRepository::instance()->first(['user_id' => 1]);
        dump($user);
        $this->assertEquals(true, $user instanceof UserModel);
        $this->assertEquals(1, $user['user_id']);
    }

    public function testGet()
    {
        $users = UserRepository::instance()->get(['status' => 1]);
        $this->assertCount(4, $users);
    }

    public function testCount()
    {
        $count = UserRepository::instance()->count(['status' => 1]);
        $this->assertEquals(4, $count);
    }

    public function testSum()
    {
        $sum = UserRepository::instance()->sum(['status' => 1], 'user_id');
        dump($sum);
        $this->assertEquals(10, $sum);
    }

    public function testMax()
    {
        $max = UserRepository::instance()->max(['status' => 1], 'user_id');
        dump($max);
        $this->assertEquals(4, $max);
    }

    public function testMin()
    {
        $min = UserRepository::instance()->min(['status' => 1], 'user_id');
        dump($min);
        $this->assertEquals(1, $min);
    }

    public function testAvg()
    {
        $avg = UserRepository::instance()->avg(['status' => 1], 'user_id');
        dump($avg);
        $this->assertEquals(2.5, $avg);
    }

    public function testInsertGetId()
    {
        $user_id = UserRepository::instance()->insertGetId([
            'name'       => 'test123',
            'email'      => 'test123@gamil.com',
            'age'        => 1,
            'status'     => 1,
            'config'     => '{}',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        dump($user_id);
        $this->assertEquals(5, $user_id);
    }

    public function testInsert()
    {
        $ok = UserRepository::instance()->insertGetId([
            'name'       => 'test123',
            'email'      => 'test123@gamil.com',
            'age'        => 1,
            'status'     => 1,
            'config'     => '{}',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        dump($ok);
        $this->assertEquals(true, $ok);
    }

    public function testPluck()
    {
        $users = UserRepository::instance()->pluck(['status' => 1], 'name');
        dump($users);
        $this->assertCount(4, $users);
    }

    public function testParseConditionRelations()
    {
        list($relations, $conditions) = UserRepository::instance()->parseConditionRelations([
            'status'           => 1,
            'rel.posts.status' => 1,
            'rel.posts.limit'  => 1,
            'joinWith'         => 'posts',
            'posts.status'     => 1,
        ]);

        dump($relations, $conditions);
        $this->assertArrayHasKey('posts', $relations);
        $this->assertArrayHasKey('status', $conditions);

        dump(UserRepository::instance()->toSql([
            'status'           => 1,
            'rel.posts.status' => 1,
            'rel.posts.limit'  => 1,
            'joinWith'         => 'posts',
            'posts.status'     => 1,
        ]));
    }

    public function testOrAndFind()
    {
        $sql = UserRepository::instance()->toSql([
            'or' => [
                'status'  => 1,
                'age:gte' => 26,
                'and'     => [
                    'status'  => 1,
                    'age:neq' => 24,
                ],
            ],
        ]);

        dump($sql);
        $this->assertStringStartsWith("select", $sql);
    }

    public function testFindExpectColumns()
    {
        $sql = UserRepository::instance()->toSql([], [
            // 排除的字段
            'except' => ['status', 'age', 'name'],
            'posts'  => ['*'],
        ]);

        dump($sql);
        $this->assertStringStartsWith("select", $sql);
    }

    public function testFindExpect()
    {
        $array = UserRepository::instance()->find([], [
            'except' => ['status', 'age', 'name', 'user_id'], // 排除的字段(user_id 字段不能排除，因为posts关联需要)
            'posts'  => [
                'except' => ['status', 'created_at', 'updated_at'], // 字段排除
            ],
        ]);

        dump($array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testFindJson()
    {
        $sql = UserRepository::instance()->toSql([
            'config->merchant' => true,
            'config->age:gt'   => 10,
        ]);

        dump($sql);
        $this->assertStringStartsWith("select", $sql);
    }

    public function testOrAndFind2()
    {
        $sql = UserRepository::instance()->toSql([
            'status' => 1,
            'or'     => [
                [
                    'status' => 2,
                    'age:gt' => 24,
                ],
                'and' => [
                    'status'  => 1,
                    'age:neq' => 24,
                ],
            ],
        ]);

        $this->assertEquals('select "users".* from "users" where "users"."status" = ? and (("users"."status" = ? and "users"."age" > ?) or ("users"."status" = ? and "users"."age" != ?))', $sql);
        $this->assertStringStartsWith("select", $sql);

        $sql2 = UserRepository::instance()->toSql([
            'status' => 1,
            'and'    => [
                [
                    'status' => 2,
                    'age:gt' => 24,
                ],
                'or' => [
                    'status'  => 1,
                    'age:neq' => 24,
                    ['age' => 1, 'status' => 2],
                ],
            ],
        ]);

        dump($sql2);
        $this->assertEquals('select "users".* from "users" where "users"."status" = ? and (("users"."status" = ? and "users"."age" > ?) and ("users"."status" = ? or "users"."age" != ? or ("users"."age" = ? and "users"."status" = ?)))', $sql2);
    }
}
