<?php

namespace Littlebug\Repository\Tests;

use Littlebug\Repository\Tests\Stubs\PostRepository;

class PostRepositoryTest extends AbstractRepositoryTest
{
    /**
     * 测试创建数据
     *
     */
    public function testCreate()
    {
        $posts = PostRepository::instance()->create([
            'user_id'   => 1,
            'parent_id' => 0,
            'name'      => '测试文章01',
            'status'    => 1,
        ]);
        dump($posts);
        $this->assertArrayHasKey('post_id', $posts);
    }

    /**
     * 测试修改数据
     *
     * @throws Exception
     */
    public function testUpdate()
    {
        $row = PostRepository::instance()->update(4, [
            'status' => 2,
            'name'   => 'test2',
        ]);

        $posts = PostRepository::instance()->find(4);
        dump($row, $posts);

        $this->assertEquals(1, $row);
        $this->assertEquals(2, $posts['status']);
        $this->assertEquals('test2', $posts['name']);
    }

    /**
     * 测试删除数据
     *
     * @throws Exception
     */
    public function testDelete()
    {
        $row   = PostRepository::instance()->delete(4);
        $posts = PostRepository::instance()->find(4);
        dump($row, $posts);
        $this->assertEquals(1, $row);
        $this->assertEquals(null, $posts);
    }

    public function testFilterFind()
    {
        $posts = PostRepository::instance()->filterFind([
            'status'    => null,
            'status:eq' => 1,
            'name'      => 'second post',
        ]);

        dump($posts);

        $this->assertArrayHasKey('post_id', $posts);
    }

    public function testFilterFindBy()
    {
        $name = PostRepository::instance()->filterFindBy([
            'status'    => null,
            'status:eq' => 1,
            'name'      => 'second post',
        ], 'name');

        dump($name);

        $this->assertEquals('second post', $name);
    }

    public function testFilterFindByRelation()
    {
        $name = PostRepository::instance()->filterFindBy([
            'status'    => null,
            'status:eq' => 1,
            'name'      => 'second post',
        ], 'user.name');

        dump($name);

        $this->assertEquals('evsign', $name);
    }

    public function testFilterFindAll()
    {
        $posts = PostRepository::instance()->filterFindAll([
            'status'    => null,
            'status:eq' => 1,
            'limit'     => 4,
        ], ['name', 'user' => ['*']]);

        dump($posts);

        $this->assertCount(4, $posts);
    }

    public function testFilterFindAllBy()
    {
        $names = PostRepository::instance()->filterFindAllBy([
            'status'    => null,
            'status:eq' => 1,
            'limit'     => 4,
        ], 'name');

        dump($names);

        $this->assertCount(4, $names);
    }

    public function testGetTableColumns()
    {
        $columns = PostRepository::instance()->getTableColumns();
        dump($columns);
        $this->assertArrayHasKey('user_id', $columns);
        $this->assertArrayHasKey('post_id', $columns);
        $this->assertArrayHasKey('parent_id', $columns);
    }

    public function testGetPrimaryKeyCondition()
    {
        // int 转 主键查询
        $conditions = PostRepository::instance()->getPrimaryKeyCondition(1);
        dump($conditions);
        $this->assertArrayHasKey('post_id', $conditions);

        // 布尔值 转 主键查询
        $conditions = PostRepository::instance()->getPrimaryKeyCondition(true);
        dump($conditions);
        $this->assertArrayHasKey('post_id', $conditions);
        $this->assertEquals(1, $conditions['post_id']);

        // 索引数组 转 主键查询
        $conditions = PostRepository::instance()->getPrimaryKeyCondition([1, 2, 3, 4]);
        dump($conditions);
        $this->assertArrayHasKey('post_id', $conditions);
    }

    public function testGetValidColumns()
    {
        $data = PostRepository::instance()->getValidColumns([
            'post_id'     => 1,
            'status'      => 2,
            'status_name' => 'test',
            'age'         => 21,
            'age_name'    => 21,
            'user_id'     => 1,
            'name'        => '456',
            'user_name'   => 789,
            'email'       => 123,
            'parent_id'   => 10,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        dump($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayNotHasKey('post_id', $data);
        $this->assertCount(6, $data);
    }
}
