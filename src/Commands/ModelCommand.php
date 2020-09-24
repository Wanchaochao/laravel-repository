<?php

namespace Littlebug\Repository\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Littlebug\Repository\Traits\CommandTrait;

/**
 * Class Model 用来生成model
 *
 * @package Littlebug\Commands
 */
class ModelCommand extends BaseCommand
{
    use CommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:model {--table=} {--path=} {--r=} {--name=} {--fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model
     {--table=} Specify the table name [Support the specified database, for example: log.crontabs ]
     {--name}   Specify the name, do not specify the use of the table name [You can specify the relative directory: Admin/User or Admin\\\\User]
     {--path=}  Specify directory [Do not pass absolute path, otherwise use relative pair path from app/Models]
     {--r=}     Specifies whether Repositories need to be generated by default [ --r=false or --r=true ]
     {--fix}    Update database table field information';

    /**
     * @var string 生成目录
     */
    protected $basePath = 'app/Models';

    public function handle()
    {
        if (!$table = $this->option('table')) {
            $this->error('Please enter a table name');
            return;
        }

        if (!$this->findTableExist($table)) {
            $this->error('Table does not exist' . $table);
            return;
        }

        $array = $this->getTableAndConnection($table);

        $table_name = Arr::get($array, 'table');
        if ($connection = Arr::get($array, 'connection')) {
            $connection = <<<html

    /**
     * Defines the database connection to use
     *
     * @var string
     */
    protected \$connection = '{$connection}';

html;
        }

        $model_name = $this->handleOptionName($table_name);
        list($columns, $primaryKey) = $this->getColumnsAndPrimaryKey($table);

        $file_name = $this->getPath($model_name . '.php');
        list($namespace, $class_name) = $this->getNamespaceAndClassName($file_name, 'Models');

        // 文件存在、并且修复文件
        if (file_exists($file_name) && $this->option('fix')) {
            $content = file_get_contents($file_name);
            $content = preg_replace_callback('/public\s+\$columns\s+=\s+(\[.*\])/Uis', function () use ($columns) {
                return <<<CODE
public \$columns = {$columns}
CODE;
            }, $content);

            file_put_contents($file_name, $content);
            $this->info("修复文件 [  {$file_name}  ]");
            return;
        }

        // 生成文件
        $this->render($file_name, [
            'table'      => $table_name,
            'primaryKey' => $primaryKey,
            'columns'    => $columns,
            'connection' => $connection ?: '',
            'class_name' => $class_name,
            'namespace'  => $namespace,
        ]);

        // 生成 repository
        if ($this->option('r') != 'false') {
            $model_class = $namespace ? trim(str_replace('\\', '/', $namespace), '/') . '/' : '';
            $model_class .= $class_name;
            $arguments   = [
                '--model' => $model_class,
                '--name'  => $model_class,
            ];

            if (($path = $this->option('path')) && Str::startsWith($path, '/')) {
                $array = explode('/', $path);
                if ($position = array_search('Models', $array)) {
                    $array = array_slice($array, 0, $position);
                    array_push($array, 'Repositories');
                }

                $arguments['--path'] = implode('/', $array);
            }

            $this->call('core:repository', $arguments);
        }
    }

    /**
     * 获取主键和 columns 信息
     *
     * @param string $table
     *
     * @return array
     */
    protected function getColumnsAndPrimaryKey($table)
    {
        $structure  = $this->findTableStructure($table);
        $primaryKey = 'id';
        $columns    = "[\n";
        foreach ($structure as $column) {
            $field = Arr::get($column, 'Field');
            if (Arr::get($column, 'Key') === 'PRI') {
                $primaryKey = $field;
            }

            $columns .= "\t\t'{$field}',\n";
        }

        $columns .= "\t]";
        return [$columns, $primaryKey];
    }

    public function getRenderHtml()
    {
        return <<<html
<?php

namespace App\Models{namespace};

use Illuminate\Database\Eloquent\Model;

class {class_name} extends Model
{
    /**
     * Define table name
     *
     * @var string
     */
    protected \$table = '{table}';

    /**
     * Define a primary key
     *
     * @var string
     */
    protected \$primaryKey = '{primaryKey}';
{connection}
    /**
     * Define the table field information
     *
     * @var array
     */
    public \$columns = {columns};

    /**
     * A property that cannot be batched.
     *
     * @var array
     */
    protected \$guarded = ['{primaryKey}'];
}
html;
    }
}
