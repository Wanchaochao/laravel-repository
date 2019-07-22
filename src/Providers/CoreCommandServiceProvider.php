<?php

namespace Littlebug\Providers;

use Littlebug\Commands\ModelCommand;
use Littlebug\Commands\RequestCommand;
use Illuminate\Support\ServiceProvider;
use Littlebug\Commands\RepositoryCommand;

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
                ModelCommand::class,
                RepositoryCommand::class,
                RequestCommand::class,
            ]);
        }
    }
}