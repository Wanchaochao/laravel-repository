<?php

namespace Littlebug\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Trait CommandTrait 命名行 trait
 * @package Littlebug\Traits
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
        $table = Arr::get($array, 'table');
        $prefix = config('database.connections.mysql.prefix');
        return DB::connection(Arr::get($array, 'connection'))->select("SHOW TABLES like '{$prefix}{$table}'");
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
        $table = Arr::get($array, 'table');
        $prefix = config('database.connections.mysql.prefix');
        $structure = DB::connection(Arr::get($array, 'connection'))->select('SHOW FULL COLUMNS FROM `'. $prefix . $table . '`');
        return json_decode(json_encode($structure), true);
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
            $default = Arr::get(Arr::pluck($structure, 'Field', 'Key'), 'PRI', $default);
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
                $return['max'] = Arr::get($array, 0);
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
        $startWith = false;
        foreach ($array as $start) {
            if (Str::startsWith($type, $start)) {
                $startWith = true;
                break;
            }
        }

        return $startWith;
    }
}
