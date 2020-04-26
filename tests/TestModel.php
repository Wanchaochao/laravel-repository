<?php

namespace Littlebug\Repository\Tests;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    /**
     * 定义表名称
     *
     * @var string
     */
    protected $table = 'test';

    /**
     * 定义主键
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 定义表字段信息
     *
     * @var array
     */
    public $columns = [
        'id',
        'title',
        'views',
        'code_url',
        'status',
        'type',
        'created_at',
        'updated_at',
    ];

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 链接信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(QrCodeItem::class, 'qr_code_id', 'id')
            ->where('status', 1);
    }
}
