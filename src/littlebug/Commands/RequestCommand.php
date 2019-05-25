<?php
/**
 *
 * Model.php
 *
 * Author: jinxing.liu@verystar.cn
 * Create: 2018/6/27 14:13
 * Editor: created by PhpStorm
 */

namespace Littlebug\Commands;

use Littlebug\Traits\Command\CommandTrait;
use Illuminate\Support\Arr;

/**
 * Class RequestCommand 生成 Request 文件
 * @package Littlebug\Commands
 */
class RequestCommand extends CoreCommand
{
    use CommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:request {--table=} {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成 request {--table=} 指定表 {--path=} 指定目录 [ 没有传递绝对路径，否则使用相对对路径 从 app/Http/Requests 开始 ]';

    /**
     * @var string 生成目录
     */
    protected $basePath = 'app/Http/Requests';

    /**
     * @var string 命名空间
     */
    private $namespace = '';

    public function handle()
    {
        if (!$table = $this->option('table')) {
            $this->error('请输入表名称');
            return;
        }

        if (!$this->findTableExist($table)) {
            $this->error('表不存在');
            return;
        }

        // 查询表结构
        $structure = $this->findTableStructure($table);
        list($rules, $primary_key) = $this->rules($structure, $table);
        $file_name = $this->getPath('UpdateRequest.php');
        list($this->namespace) = $this->getNamespaceAndClassName($file_name, 'Requests');

        // 编辑
        $this->renderRequest('UpdateRequest.php', $this->getRules($rules));
        $id_rules = Arr::pull($rules, $primary_key);
        // 删除和新增验证
        $this->renderRequest('DestroyRequest.php', "['{$primary_key}' => '{$id_rules}']");
        $this->renderRequest('StoreRequest.php', $this->getRules($rules));
    }

    /**
     * 获取 rules 字符串
     *
     * @param array $rules
     *
     * @return string
     */
    private function getRules($rules)
    {
        $str_rules = "[\n";
        foreach ($rules as $column_name => $rule) {
            $str_rules .= "\t\t\t'{$column_name}' => '{$rule}',\n";
        }

        return $str_rules . "\t\t]";
    }

    /**
     * 渲染Request
     *
     * @param string $class_file 类文件名称
     * @param string $rules      规则
     */
    private function renderRequest($class_file, $rules)
    {
        $file_name  = $this->getPath($class_file);
        $class_name = str_replace('.php', '', $class_file);
        $namespace  = $this->namespace;
        $this->render($file_name, compact('namespace', 'rules', 'class_name'));
    }

    /**
     * 获取路由规则
     *
     * @param array  $array
     * @param string $table
     *
     * @return array
     */
    private function rules($array, $table)
    {
        $rules       = [];
        $primary_key = null;
        foreach ($array as $row) {
            $field = Arr::get($row, 'Field');
            if (in_array($field, ['created_at', 'updated_at'])) {
                continue;
            }

            $tmp_rules = [];
            // 不能为空
            if (Arr::get($row, 'Null') == 'NO') {
                $tmp_rules[] = 'required';
            }

            // 类型处理
            $type = Arr::get($row, 'Type');
            if ($this->isInt($type)) {
                $tmp_rules[] = 'integer';
            } elseif ($string = $this->isString($type)) {
                $tmp_rules[] = 'string';
                if ($min = Arr::get($string, 'min', 2)) {
                    $tmp_rules[] = 'min:' . $min;
                }

                if ($max = Arr::get($string, 'max')) {
                    $tmp_rules[] = 'max:' . $max;
                }
            }

            // 主键
            if (Arr::get($row, 'Key') == 'PRI') {
                $tmp_rules[] = 'min:1|exists:' . $table;
                $primary_key = $field;
            }

            $rules[$field] = implode('|', $tmp_rules);
        }

        return [$rules, $primary_key];
    }

    /**
     * 获取渲染模板
     *
     * @return mixed|string
     */
    public function getRenderHtml()
    {
        return <<<html
<?php

namespace App\Http\Requests{namespace};

use Littlebug\Requests;

class {class_name} extends Request
{
    public function authorize()
    {
        return true;
    }
    
    public function rules()
    {
        return {rules};
    }
}
html;

    }
}
