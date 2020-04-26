<?php

namespace Littlebug\Repository\Tests;

use Littlebug\Repository\Repository;

class TestRepository extends Repository
{
    public function __construct(TestModel $model)
    {
        parent::__construct($model);
    }
}