<?php

namespace Littlebug\Repository\Tests;

use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function testCreate()
    {

    }

    public function testPaginate()
    {

    }

    public function testFirst()
    {

    }

    public function testCount()
    {

    }

    public function testFindAllBy()
    {

    }

    public function testDelete()
    {

    }

    public function testSum()
    {

    }

    public function testFind()
    {

    }

    public function testFindAll()
    {

    }

    public function testUpdate()
    {

    }

    public function testFindWhere()
    {

    }

    public function testMin()
    {

    }

    public function testGet()
    {

    }

    public function testGetPrimaryKeyCondition()
    {

    }

    public function testAvg()
    {

    }

    public function testInsertGetId()
    {

    }

    public function testGetValidColumns()
    {

    }

    public function testFirstField()
    {

    }

    public function testGetWhereQuery()
    {

    }

    public function testInsert()
    {

    }

    public function testFirstKey()
    {

    }

    public function testIsNotSelectAll()
    {
        $response = new TestRepository(TestModel::class);
        $columns = TestRepository::instance()->isNotSelectAll(['*'], 'users');
        $this->assertEquals(true, $columns);
    }

    public function testFindBy()
    {

    }

    public function testMax()
    {

    }

    public function testPluck()
    {

    }
}
