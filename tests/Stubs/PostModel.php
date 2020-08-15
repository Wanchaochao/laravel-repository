<?php

namespace Littlebug\Repository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class PostModel extends Model
{
    /**
     * 定义表名称
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * 定义主键
     *
     * @var string
     */
    protected $primaryKey = 'post_id';

    /**
     * 定义表字段信息
     *
     * @var array
     */
    public $columns = [
        'post_id',
        'user_id',
        'parent_id',
        'name',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = ['post_id'];

    protected $casts = [
        'user_id'   => 'integer',
        'status'    => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * 用户信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(UserModel::class, 'user_id', 'user_id');
    }
}
