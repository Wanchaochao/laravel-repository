<?php
/**
 *
 * Model.php
 *
 * Create: 2018/6/27 14:13
 * Editor: created by PhpStorm
 */

namespace Littlebug\Commands;

use Illuminate\Support\Str;

/**
 * Class ControllerCommand 生成 Controller 信息
 * @package Littlebug\Commands
 */
class ControllerCommand extends CoreCommand
{
    /**
     * @var string 基础目录
     */
    protected $basePath = 'app/Http/Controllers';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:controller {--name=} {--r=} {--request=} {--pk=} {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成 Controller 
     {--table=}   指定表名称 [ 指定该参数会通过表生成视图文件 ]
     {--name=}    指定名称 可以带命名空间 [ --name=Home/IndexController 或者 Home\\\\IndexController ]
     {--r=}       指定 Repository 需要从 Repositories 目录开始; 默认使用控制器同名 Repository
     {--request=} 指定 request 目录; 需要从 Requests 目录开始; 默认使用控制器命名空间
     {--pk=}      指定主键名称，默认id';

    public function handle()
    {
        if (!$this->option('name')) {
            $this->error('请输入名称');
            return;
        }

        $pk        = $this->option('pk') ?: 'id';
        $name      = $this->handleOptionName('IndexController', 'Controller');
        $file_name = $this->getPath($name . '.php', false);
        list($namespace, $class_name) = $this->getNamespaceAndClassName($file_name, 'Controllers');
        // repository 命名空间和类名称
        list($repository_namespace, $repository) = $this->getRepositoryNamespaceAndClass($namespace, $class_name);
        $view = $this->getView($namespace, $class_name);

        $this->render($file_name, [
            'namespace'            => $namespace,
            'request_namespace'    => $this->getRequestNamespace($namespace, $class_name),
            'class_name'           => $class_name,
            'repository'           => $repository,
            'repository_name'      => Str::camel($repository),
            'repository_namespace' => $repository_namespace,
            'primary_key'          => $pk,
            'view'                 => $view,
        ]);
    }

    /**
     * 获取request 的命名空间
     *
     * @param $namespace
     * @param $class_name
     *
     * @return array|string
     */
    private function getRequestNamespace($namespace, $class_name)
    {
        // 请求 request 目录
        if (!$request = $this->option('request')) {
            if ($request = $namespace) {
                $request .= '/';
            }

            $request .= $class_name;
        }

        return '\\' . ltrim(str_replace(['/', 'Controller'], ['\\', ''], $request), '\\');
    }

    /**
     * 获取 Repository 的命名空间和类名称
     *
     * @param $namespace
     * @param $class_name
     *
     * @return array
     */
    private function getRepositoryNamespaceAndClass($namespace, $class_name)
    {
        // repository
        if ($repository = trim(str_replace('\\', '/', $this->option('r')), '/')) {
            $arr_repository       = explode('/', $repository);
            $repository           = array_pop($arr_repository);
            $repository_namespace = $arr_repository ? '\\' . implode('\\', $arr_repository) : '';
        } else {
            $repository           = str_replace('Controller', '', $class_name);
            $repository_namespace = $namespace;
        }

        // 是否Repository 结尾，不是加上
        if (!Str::endsWith($repository, 'Repository')) {
            $repository .= 'Repository';
        }

        return [$repository_namespace, $repository];
    }

    private function getView($namespace, $class_name)
    {
        $view = str_replace(
            ['\\', '/', 'Controller', '-controller', '-'],
            ['.', '.', '', '', '_'],
            $namespace . '/' . Str::kebab($class_name)
        );
        return strtolower(trim($view, '.'));
    }

    public function getRenderHtml()
    {
        return <<<html
<?php
     
namespace App\Http\Controllers{namespace};

use App\Http\Controllers\Controller;
use App\Http\Requests{request_namespace}\DestroyRequest;
use App\Http\Requests{request_namespace}\StoreRequest;
use App\Http\Requests{request_namespace}\UpdateRequest;
use App\Repositories{repository_namespace}\{repository};
use Littlebug\Helpers\Helper;

class {class_name} extends Controller
{
    /**
     * @var {repository}
     */
    private \${repository_name};
    
    public function __construct({repository} \${repository_name})
    {
        \$this->{repository_name} = \${repository_name};
    }

    /**
     * 首页显示
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAction()
    {
        \$filters = Helper::filter_array(request()->all());
        \$filters['order'] = '{primary_key} desc';
        \$list = \$this->{repository_name}->getList(\$filters);
        return view('{view}.index', compact('list', 'filters'));
    }
    
    /**
     * 添加数据显示
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function createAction()
    {
        return \$this->sendSuccess(view('{view}.create')->render());
    }

    /**
     * 添加数据执行
     *
     * @param StoreRequest \$request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAction(StoreRequest \$request)
    {
        return \$this->send(\$this->{repository_name}->create(\$request->all()));
    }

    /**
     * 修改数据显示
     * @param  DestroyRequest \$request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function editAction(DestroyRequest \$request)
    {
        \$info = \$this->{repository_name}->get(['{primary_key}' => \$request->get('{primary_key}')]);
        return \$this->sendSuccess(view('{view}.edit', compact('info'))->render());
    }
    
    /**
     * 修改数据执行
     *
     * @param UpdateRequest \$request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAction(UpdateRequest \$request)
    {
        return \$this->send(\$this->{repository_name}->update(\$request->input('{primary_key}'), \$request->all()));
    }

    /**
     * 删除数据
     *
     * @param DestroyRequest \$request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAction(DestroyRequest \$request)
    {
        return \$this->send(\$this->{repository_name}->delete(\$request->input('{primary_key}')));
    }
}
html;
    }
}
