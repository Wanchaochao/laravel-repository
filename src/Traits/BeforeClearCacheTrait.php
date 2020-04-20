<?php
/**
 *
 * BeforeTrait.php
 *
 * Author: jinxing.liu
 * Create: 2019-07-22 10:16
 * Editor: created by PhpStorm
 */

namespace Littlebug\Repository\Traits;

use Illuminate\Support\Arr;

/**
 * Trait BeforeClearCacheTrait 在修改和删除数据之前 清除缓存 需要自定义 clearCache 方法
 * @package Littlebug\Traits
 */
trait BeforeClearCacheTrait
{
    /**
     * 通过查询条件清除缓存
     *
     * @param array $conditions 查询条件
     *
     * @return mixed
     */
    abstract function clearCache($conditions);

    /**
     * 修改之前的事件函数
     *
     * @return mixed
     */
    public function beforeUpdate()
    {
        return $this->clearCache(Arr::get(func_get_args(), 0, []));
    }

    /**
     * 删除之前的事件缓存
     *
     * @param array $conditions
     *
     * @return mixed
     */
    public function beforeDelete($conditions)
    {
        return $this->clearCache($conditions);
    }
}