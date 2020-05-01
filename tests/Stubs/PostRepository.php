<?php

namespace Littlebug\Repository\Tests\Stubs;

use Littlebug\Repository\Repository;

class PostRepository extends Repository
{
    public function __construct(PostModel $model)
    {
        parent::__construct($model);
    }
}