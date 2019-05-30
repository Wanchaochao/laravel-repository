<?php

namespace Littlebug\Helpers;

use Illuminate\Support\Str;

class Helper
{
    public static function isCli()
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
    public static function isEmpty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }

    /**
     * 将数组元素转为大驼峰法
     *
     * 例如：get-user-info GetUserInfo
     *
     * @param $params
     */
    public static function arrayStudlyCase(array &$params)
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
    public static function filterArray($array)
    {
        return array_filter($array, function ($value) {
            return !static::isEmpty($value);
        });
    }

    /**
     * 判断数组是否为关联数组
     *
     * @param array $array      需要判断的数组
     * @param bool  $allStrings whether the array keys must be all strings in order for
     *                          the array to be treated as associative.
     *
     * @return bool 是关联数组返回true
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}