<?php

namespace Littlebug\Repository\Commands;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Littlebug\Repository\Helper;

/**
 * Class CoreCommand core 基础命令
 *
 * @package Littlebug\Commands
 */
abstract class BaseCommand extends Command
{
    /**
     * @var string 定义相对路径开始位置
     */
    protected $basePath = '';

    /**
     * 获取目录
     *
     * @param string $file_name   文件名称
     *
     * @param bool   $use_path    是否使用 option path
     *
     * @param bool   $studly_case 是否转大写
     *
     * @return string
     */
    protected function getPath($file_name, $use_path = true, $studly_case = true)
    {
        // 处理路径
        $path = $use_path ? $this->option('path') : '';
        $path = str_replace('\\', '/', $path);
        if (Helper::isEmpty($path) || !Str::startsWith($path, '/')) {
            $array = explode('/', $path);
            if ($studly_case) {
                Helper::arrayStudlyCase($array);
            }

            $path = base_path(rtrim($this->basePath, '/') . '/' . implode('/', $array));
        }

        return rtrim($path, '/') . '/' . ltrim($file_name, '/');
    }

    /**
     * 通过文件名称获取命名空间
     *
     * @param string $file_name 文件名称
     *
     * @param string $type_name 指定查询的字符串
     *
     * @return array
     */
    protected function getNamespaceAndClassName($file_name, $type_name)
    {
        $arr_path   = explode('/', str_replace('\\', '/', $file_name));
        $class_name = str_replace('.php', '', array_pop($arr_path));
        $position   = array_search($type_name, $arr_path);
        if ($position !== false && ($namespace = implode('\\', array_slice($arr_path, $position + 1)))) {
            $namespace = '\\' . $namespace;
        } else {
            $namespace = '';
        }

        return [$namespace, $class_name];
    }

    /**
     * 处理名称
     *
     * @param string $default_name 默认名称
     *
     * @param string $suffix       检测后缀
     *
     * @param string $option       获取配置的名称
     *
     * @return string
     */
    protected function handleOptionName($default_name, $suffix = '', $option = 'name')
    {
        $input_name = str_replace('\\', '/', $this->option($option) ?: $default_name);
        $array      = explode('/', $input_name);
        Helper::arrayStudlyCase($array);
        $name = implode('/', $array);
        if ($suffix && !Str::endsWith($name, $suffix)) {
            $name .= $suffix;
        }

        return $name;
    }

    /**
     * 是否写文件
     *
     * @param $file_name
     *
     * @return bool
     */
    protected function isWrite($file_name)
    {
        return !(file_exists($file_name) && !$this->confirm('文件 [ ' . $file_name . ' ] 已经存在是否覆盖？'));
    }

    /**
     * 渲染生成文件
     *
     * @param string $file_name   生成文件名称
     * @param array  $params      需要的参数
     * @param string $render_view 是否需要通过视图生成
     */
    protected function render($file_name, $params, $render_view = '')
    {
        $dir_name = dirname($file_name);
        if (!function_exists($dir_name)) {
            @mkdir($dir_name, 0755, true);
        }

        if ($this->isWrite($file_name)) {
            // 通过视图渲染
            if ($render_view) {
                try {
                    $content = view($render_view, $params)->render();
                } catch (Throwable $e) {
                    $content = '';
                    $this->error($e->getMessage());
                }
            } else {
                // 字符串替换
                $html   = $this->getRenderHtml();
                $search = array_map(function ($value) {
                    return '{' . $value . '}';
                }, array_keys($params));

                $content = str_replace($search, array_values($params), $html);
            }

            file_put_contents($file_name, $content);
            $this->info('文件 [ ' . $file_name . ' ] 生成成功');
        } else {
            $this->info('文件 [ ' . $file_name . ' ] 存在，不处理');
        }
    }

    /**
     * @return mixed 获取渲染的模板html
     */
    abstract function getRenderHtml();
}
