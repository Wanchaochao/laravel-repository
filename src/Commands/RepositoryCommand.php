<?php

namespace Littlebug\Repository\Commands;

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
    protected $description = 'Generate repository
     {--name=}  Define the name [ --name=Admin\\\\AdminUserRepository or --name=Admin/AdminUserRepository ]
     {--path=}  Specify directory [Do not pass absolute path, otherwise use relative pair path from app/Repositories]
     {--model=} The specified model uses the namespace [ --model=Admin\\\\AdminUser or --model=Admin/AdminUser ]';

    /**
     * @var string 定义使用目录
     */
    protected $basePath = 'app/Repositories';

    public function handle()
    {
        if (!$name = $this->option('name')) {
            $this->error('Need to specify a name');
            return;
        }

        if (!$model = $this->option('model')) {
            $this->error('Need to specify model');
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

use App\Models{model_namespace}\{model_name};
{use_base}

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
