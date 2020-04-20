<?php
/**
 *
 * AfterTrait.php
 *
 * Author: jinxing.liu
 * Create: 2019-07-22 10:16
 * Editor: created by PhpStorm
 */

namespace Littlebug\Repository\Traits;

/**
 * Trait AfterTrait 在修改和删除数据之后 清除缓存 需要自定义 clearCache 方法
 * @package Littlebug\Traits
 */
trait AfterTrait
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
     * 修改之后的事件函数
     *
     * @param array $conditions 修改数据的查询条件
     *
     * @return mixed
     */
    public function afterUpdate($conditions)
    {
        return $this->clearCache($conditions);
    }

    /**
     * 删除之后的事件缓存
     *
     * @param array $conditions
     *
     * @return mixed
     */
    public function afterDelete($conditions)
    {
        return $this->clearCache($conditions);
    }
}