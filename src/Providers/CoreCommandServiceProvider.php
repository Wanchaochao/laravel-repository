<?php

namespace Littlebug\Providers;

use Illuminate\Support\ServiceProvider;
use Littlebug\Commands\GenerateCommand;
use Littlebug\Commands\ModelCommand;
use Littlebug\Commands\RepositoryCommand;
use Littlebug\Commands\RequestCommand;

/**
 * Class CoreCommandServiceProvider 命令行服务提供者
 *
 * @package App\Providers
 */
class CoreCommandServiceProvider extends ServiceProvider
{
    /**
     * 在注册后进行服务的启动
     *
     * @return void
     */
    public function boot()
    {
        // 添加命名行
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
                ModelCommand::class,
                RepositoryCommand::class,
                RequestCommand::class,
            ]);
        }
    }
}