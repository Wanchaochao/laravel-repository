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
 * Trait AfterClearCacheTrait 在修改和删除数据之后 清除缓存 需要自定义 clearCache 方法
 *
 * @package Littlebug\Repository\Traits
 */
trait AfterClearCacheTrait
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
     */
    public function afterUpdate()
    {
        list($conditions, , $row) = func_get_args();
        if ($row > 0) {
            $this->clearCache($conditions);
        }
    }

    /**
     * 删除之后的事件缓存
     *
     * @param array $conditions
     * @param int   $row
     */
    public function afterDelete($conditions, $row)
    {
        if ($row > 0) {
            $this->clearCache($conditions);
        }
    }
}