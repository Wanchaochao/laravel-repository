<?php
/**
 * @Author Little Bug
 * @CreatedAt 2019-05-27 10:14
 * @CreatedBy Phpstrom
 */


use Illuminate\Support\Str;
use \Illuminate\Support\Arr;

if (!function_exists('is_cli')) {
    function is_cli()
    {
        return PHP_SAPI == 'cli' && empty($_SERVER['REMOTE_ADDR']);
    }
}


if (!function_exists('t')) {
    /**
     * @param $key
     * @param string $file
     * @return array|mixed|string|null
     * @throws Exception
     */
    function t($key, $file = 'pay')
    {
        $value = trans($file . '.' . $key);

        if ($file . '.' . $key === $value) {
            $arr   = explode('.', $key);
            $tmp   = implode('.', $arr);
            $value = array_pop($arr);

            $file_path = base_path('resources/lang/zh-CN/transfer.php');
            if (!is_cli() && file_exists($file_path)) {
                $langs = cache('langs');
                if (!$langs) {
                    $langs = include $file_path;
                }
                Arr::set($langs, $tmp, $value);
                cache(['langs' => $langs], 3600);
            }
            return $value;
        }

        return $value;
    }

}

if (!function_exists('is_empty')) {
    /**
     * 判断是否为空 0 值不算
     *
     * @param mixed $value 判断的值
     *
     * @return boolean 是空返回 true
     */
    function is_empty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }
}


if (!function_exists('array_studly_case')) {
    /**
     * 将数组元素转为大驼峰法
     * 例如：get-user-info GetUserInfo
     *
     * @param $params
     */
    function array_studly_case(array &$params)
    {
        foreach ($params as &$value) {
            $value = Str::studly($value);
        }

        unset($value);
    }
}


if (!function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }
}

if (!function_exists('filter_array')) {
    /**
     * 过滤数组数据
     *
     * @param array|mixed $array 数组信息
     *
     * @return array
     */
    function filter_array($array)
    {
        return array_filter($array, function ($value) {
            return !is_empty($value);
        });
    }
}
