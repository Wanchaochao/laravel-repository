<?php

namespace LittleBug\Traits\Command;

use Illuminate\Support\Facades\DB;

/**
 * Trait CommandTrait 命名行 trait
 * @package App\Traits
 */
trait CommandTrait
{
    /**
     * @param $table
     *
     * @return array
     */
    protected function getTableAndConnection($table)
    {
        $array      = explode('.', $table);
        $table      = array_pop($array);
        $connection = array_pop($array);
        return compact('table', 'connection');
    }

    /**
     * 查询表是否存在
     *
     * @param $table
     *
     * @return array
     */
    protected function findTableExist($table)
    {
        $array = $this->getTableAndConnection($table);
        $table = array_get($array, 'table');
        return DB::connection(array_get($array, 'connection'))->select("SHOW TABLES like '{$table}'");
    }

    /**
     * 查询表结构
     *
     * @param string $table 表名称
     *
     * @return array 表结构数组
     */
    protected function findTableStructure($table)
    {
        $array = $this->getTableAndConnection($table);
        $table = array_get($array, 'table');
        return DB::connection(array_get($array, 'connection'))->select('SHOW FULL COLUMNS FROM `' . $table . '`');
    }

    /**
     * 获取主键信息
     *
     * @param string $table   表名称
     *
     * @param string $default 默认主键为ID
     *
     * @return mixed|string
     */
    protected function findPrimaryKey($table, $default = 'id')
    {
        if ($structure = $this->findTableStructure($table)) {
            $default = array_get(array_pluck($structure, 'Field', 'Key'), 'PRI', $default);
        }

        return $default;
    }

    /**
     * 判断是否 int 类型
     *
     * @param string $type
     *
     * @return bool
     */
    protected function isInt($type)
    {
        return $this->isStartWith(['tinyint', 'smallint', 'mediumint', 'int', 'bigint'], $type);
    }

    /**
     * 判断是否string 类型
     *
     * @param string $type
     *
     * @return bool|array
     */
    protected function isString($type)
    {
        if ($this->isStartWith(['char', 'varchar', 'text'], $type)) {
            preg_match('/\d+/', $type, $array);
            $return = ['min' => 2];
            if ($array) {
                $return['max'] = array_get($array, 0);
            }

            return $return;
        }

        return false;
    }

    /**
     * 是否存在数组中数据的开头
     *
     * @param array  $array
     * @param string $type
     *
     * @return bool
     */
    protected function isStartWith($array, $type)
    {
        $is_start_with = false;
        foreach ($array as $start) {
            if (starts_with($type, $start)) {
                $is_start_with = true;
                break;
            }
        }

        return $is_start_with;
    }
}
