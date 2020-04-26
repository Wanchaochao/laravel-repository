<?php

namespace Littlebug\Repository\Tests\Stubs;

use Littlebug\Repository\Repository;

class UserRepository extends Repository
{
    public function __construct(UserModel $model)
    {
        parent::__construct($model);
    }
}