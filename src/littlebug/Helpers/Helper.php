<?php

namespace Littlebug\Helpers;

use Illuminate\Support\Str;
use \Illuminate\Support\Arr;

class Helper
{
    public static function is_cli()
    {
        return PHP_SAPI == 'cli' && empty($_SERVER['REMOTE_ADDR']);
    }


    /**
     * 判断是否为空 0 值不算
     *
     * @param mixed $value 判断的值
     *
     * @return boolean 是空返回 true
     */
    public static function is_empty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }


    /**
     * 将数组元素转为大驼峰法
     * 例如：get-user-info GetUserInfo
     *
     * @param $params
     */
    public static function array_studly_case(array &$params)
    {
        foreach ($params as &$value) {
            $value = Str::studly($value);
        }
        unset($value);
    }

    /**
     * 过滤数组数据
     *
     * @param array|mixed $array 数组信息
     *
     * @return array
     */
    public static function filter_array($array)
    {
        return array_filter($array, function ($value) {
            return !self::is_empty($value);
        });
    }

}