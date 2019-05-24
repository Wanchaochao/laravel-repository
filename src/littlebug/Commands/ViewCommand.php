<?php
/**
 *
 * ViewCommand.php
 *
 * Create: 2018/8/15 13:49
 * Editor: created by PhpStorm
 */

namespace LittleBug\Commands;


use App\Traits\Command\CommandTrait;
use Illuminate\Support\Str;

class ViewCommand extends CoreCommand
{
    use CommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:view {--table=} {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成视图文件 index.blade.php|edit.blade.php|create.blade.php
    {--table=} 指定表名称 [ 支持指定数据库,例如：log.crontabs ]
    {--path=}  指定目录 [ 没有传递绝对路径，否则使用相对对路径 从 resources/views 开始 ]';

    protected $basePath = 'resources/views';

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

        if ((!$path = $this->option('path')) || Str::startsWith($path, '/')) {
            $this->error('请输入路径');
            return;
        }

//        $pk        = $this->findPrimaryKey($table);
//        $structure = $this->findTableStructure($table);
//        $path      = str_replace('_', '-', strtolower(trim($path, '/')));
//        $file_name = $this->getPath('index.blade.php', true, false);

//        // 首页
//        $this->render(strtolower($file_name), compact('structure', 'pk', 'path'), 'common::commands.index');
//
//        // 修改页面
//        $file_name = $this->getPath('edit.blade.php',true, false);
//        $this->render(strtolower($file_name), compact('structure', 'pk', 'path'), 'common::commands.edit');
//
//        // 创建页面
//        $file_name = $this->getPath('create.blade.php', true, false);
//        $this->render(strtolower($file_name), compact('structure', 'pk', 'path'), 'common::commands.create');
    }

    public function getRenderHtml()
    {
        return '';
    }
}
