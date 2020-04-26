<?php

namespace Littlebug\Repository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserModel
 * @package Littlebug\Repository\Tests\Stubs
 * @mixin Model
 * @method static create($array)
 */
class UserModel extends Model
{
    /**
     * 定义表名称
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * 定义主键
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * 定义表字段信息
     *
     * @var array
     */
    public $columns = [
        'user_id',
        'name',
        'email',
        'age',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = ['user_id'];

    /**
     * 关联文章信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(PostModel::class, 'user_id', 'user_id');
    }
}
