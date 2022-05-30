<?php

namespace Littlebug\Repository\Tests;

use PHPUnit\Framework\TestCase;
use Littlebug\Repository\Tests\Stubs\UserRepository;

class RepositoryTest extends TestCase
{

    public function testParseColumnRelations()
    {
        list($relations, $columns) = UserRepository::instance()->parseColumnRelations([
            'user_id',
            'posts_count',
            'posts' => ['username', 'age'],
        ], 'users', ['user_id', 'username']);

        $this->assertArrayHasKey('posts', $relations);
        $this->assertTrue(in_array('user_id', $columns));
    }
}
