<?php

namespace Littlebug\Repository\Commands;

use Illuminate\Support\Arr;
use Littlebug\Repository\Traits\CommandTrait;

/**
 * Class RequestCommand 生成 Request 文件
 * @package Littlebug\Commands
 */
class RequestCommand extends BaseCommand
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
    protected $description = 'Generate request
     {--table=} specify table
     {--path=}  specify directory [no absolute path is passed, otherwise use relative path starting from app/Http/Requests ]';

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
            $this->error('Please enter a table name');
            return;
        }

        if (!$this->findTableExist($table)) {
            $this->error('Table does not exist');
            return;
        }

        // 查询表结构
        $structure = $this->findTableStructure($table);
        list($rules, $primary_key, $columns) = $this->rules($structure, $table);
        $file_name = $this->getPath('UpdateRequest.php');
        list($this->namespace) = $this->getNamespaceAndClassName($file_name, 'Requests');

        // 编辑
        $this->renderRequest('UpdateRequest.php', $this->getRules($rules), $this->getRules($columns));
        $id_rules = Arr::pull($rules, $primary_key);
        Arr::pull($columns, $primary_key);

        // 删除和新增验证
        $this->renderRequest('DestroyRequest.php', "['{$primary_key}' => '{$id_rules}']", "['{$primary_key}' => '主键信息']");
        $this->renderRequest('StoreRequest.php', $this->getRules($rules), $this->getRules($columns));
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
     * @param string $file    类文件名称
     * @param string $rules   规则
     * @param string $columns 字段说明信息
     */
    private function renderRequest($file, $rules, $columns)
    {
        $file_name  = $this->getPath($file);
        $class_name = str_replace('.php', '', $file);
        $namespace  = $this->namespace;
        $this->render($file_name, compact('namespace', 'rules', 'class_name', 'columns'));
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
        $rules       = $columns = [];
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

            $rules[$field]   = implode('|', $tmp_rules);
            $columns[$field] = Arr::get($row, 'Comment');
        }

        return [$rules, $primary_key, $columns];
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

use Illuminate\Foundation\Http\FormRequest;

class {class_name} extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * 定义规则信息
     *
     * @return array
     */
    public function rules()
    {
        return {rules};
    }

    /**
     * 定义字段对应的名称
     *
     * @return array
     */
    public function attributes()
    {
        return {columns};
    }
}
html;

    }
}
