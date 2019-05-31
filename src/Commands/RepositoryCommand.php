<?php

namespace Littlebug\Commands;

/**
 * Class RepositoryCommand 生成 repository 信息
 * @package Littlebug\Commands
 */
class RepositoryCommand extends CoreCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:repository {--name=} {--path=} {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成 repository 
    {--name=}  定义名称 [ --name=Admin\\\\AdminUserRepository 或者 --name=Admin/AdminUserRepository ]
    {--path=}  指定目录 [ 没有传递绝对路径，否则使用相对对路径 从 app/Repositories 开始 ]
    {--model=} 指定的model 使用命名空间 [ --model=Admin\\\\AdminUser 或者 --model=Admin/AdminUser ]';

    /**
     * @var string 定义使用目录
     */
    protected $basePath = 'app/Repositories';

    public function handle()
    {
        if (!$name = $this->option('name')) {
            $this->error('需要指定名称');
            return;
        }

        if (!$model = $this->option('model')) {
            $this->error('需要指定model');
            return;
        }

        $arr_model       = explode('/', str_replace('\\', '/', $model));
        $model_name      = array_pop($arr_model);
        $model_namespace = $arr_model ? '\\' . implode('\\', $arr_model) : '';
        $file_name       = $this->getPath($this->handleOptionName($name, 'Repository') . '.php');
        list($namespace, $class_name) = $this->getNamespaceAndClassName($file_name, 'Repositories');
        $use_base        = 'use Littlebug\Repository\Repository;';
        $this->render($file_name, compact('namespace', 'class_name', 'model_namespace', 'model_name', 'use_base'));
    }

    public function getRenderHtml()
    {
        return <<<html
<?php

namespace App\Repositories{namespace};

{use_base}
use App\Models{model_namespace}\{model_name};

class {class_name} extends Repository
{
    public function __construct({model_name} \$model)
    {
        parent::__construct(\$model);
    }
}
html;
    }

}
