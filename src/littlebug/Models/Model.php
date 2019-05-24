<?php
/**
 * Created by PhpStorm.
 * User: 万超 chao.wan@verystar.cn
 * Date: 2018/6/20 下午3:12
 */

namespace Littlebug\Models;


use Illuminate\Database\Eloquent\Model as LaravelModel;

class Model extends LaravelModel
{

    public $columns = [];

    /****
     *
     * 构造函数：除PK外的所有字段均可以进行批量赋值
     *
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = [];
        if ($this->columns) {
            foreach ($this->columns as $column) {
                if ($column != $this->primaryKey) {
                    $this->fillable[] = $column;
                }
            }
        }
        parent::__construct($attributes);
    }

}
