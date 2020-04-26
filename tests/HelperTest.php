<?php

namespace Littlebug\Repository\Tests;

use Littlebug\Repository\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{

    public function testIsAssociative()
    {
        $this->assertEquals(true, Helper::isAssociative(['username' => 123, 'age' => 456]));
        $this->assertEquals(false, Helper::isAssociative(['username' => 123, 'age' => 456, 1]));
        $this->assertEquals(true, Helper::isAssociative(['username' => 123, 'age' => 456, 1], false));
    }

    public function testFilterArray()
    {
        $this->assertCount(1, Helper::filterArray(['', null, ' ', '', [], 1]));
        $this->assertCount(2, Helper::filterArray(['0', null, ' ', '', [], 1]));
    }

    public function testArrayStudlyCase()
    {
        $params = ['username', 'user-age', 'my-name', 'TopName'];
        Helper::arrayStudlyCase($params);
        $this->assertEquals('Username', $params[0]);
        $this->assertEquals('UserAge', $params[1]);
        $this->assertEquals('MyName', $params[2]);
        $this->assertEquals('TopName', $params[3]);
    }

    public function testIsEmpty()
    {
        $this->assertEquals(true, Helper::isEmpty(''));
        $this->assertEquals(true, Helper::isEmpty(' '));
        $this->assertEquals(true, Helper::isEmpty([]));
        $this->assertEquals(true, Helper::isEmpty(null));
        $this->assertEquals(false, Helper::isEmpty(0));
        $this->assertEquals(false, Helper::isEmpty('0'));
        $this->assertEquals(false, Helper::isEmpty([1, 2, 3]));
    }
}
