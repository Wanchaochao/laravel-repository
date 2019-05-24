<?php
/**
 *
 * GenerateCommand.php
 *
 * Create: 2018/8/14 15:57
 * Editor: created by PhpStorm
 */

namespace Littlebug\Commands;

use Littlebug\Traits\Command\CommandTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateCommand extends Command
{
    use CommandTrait;

    /**
     * The name and signature of the console command.
     * demo: php artisan core:generate --table=member_accounts --path=Member --controller=Member/MemberAccountController
     *
     * @var string
     */
    protected $signature = 'core:generate {--table=} {--controller=} {--model=} {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成 controller|model|repository|request|views 
    {--table=}      指定表名称 [ 支持指定数据库,例如：log.crontabs ]
    {--controller=} 控制器名称 [ 请使用相对路径 ], 默认使用表名称 [ 大驼峰命名 ]
    {--path=}       指定目录 [ 没有传递绝对路径，否则使用相对对路径 从 app/Models 开始 ]  
    {--model=}      model名称 默认生成使用表名称生成';

    public function handle()
    {
        if (!$table = $this->option('table')) {
            $this->error('请输入表名称');
            return;
        }

        // 控制器名称
        $controller = $this->option('controller') ?: Str::studly($table);
        if (Str::startsWith($controller, '/')) {
            $this->error('请使用相对路径');
            return;
        }

        if (!$this->findTableExist($table)) {
            $this->error('表不存在');
            return;
        }

        // 生成model 和 repository
        $this->call('core:model', filter_array([
            '--table' => $table,
            '--name'  => $this->option('model'),
            '--path'  => $this->option('path')
        ]));

        // 生成控制器
        $this->call('core:controller', [
            '--name'  => $controller,
            '--r'     => $this->getRepositoryName($table),
            '--pk'    => $this->findPrimaryKey($table),
            '--table' => $table
        ]);

        // 生成request
        $this->call('core:request', [
            '--table' => $table,
            '--path'  => str_replace(['Controller', '\\'], ['', '/'], $controller)
        ]);
    }

    /**
     * 获取 Repository 名称
     *
     * @param string $table
     *
     * @return string
     */
    protected function getRepositoryName($table)
    {
        $table      = explode('.', Str::replaceLast('s', '', $table));
        $table      = array_pop($table);
        $repository = ltrim($this->option('model') ?: Str::studly($table), '/');
        if ($path = $this->option('path')) {
            if (Str::startsWith($path, '/')) {
                $repositories = explode('/', $path);
                $position     = array_search('Models', $repositories);
                $repositories = array_slice($repositories, $position + 1);
                $path         = implode('/', $repositories);
            }

            $repository = rtrim($path, '/') . '/' . $repository;
        }

        $repositories = explode('/', $repository);
        array_studly_case($repositories);
        return implode('/', $repositories);
    }
}
