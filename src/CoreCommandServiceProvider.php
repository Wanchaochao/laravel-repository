<?php

namespace Littlebug\Repository;

use Illuminate\Support\ServiceProvider;
use Littlebug\Repository\Commands\ModelCommand;
use Littlebug\Repository\Commands\RequestCommand;
use Littlebug\Repository\Commands\RepositoryCommand;

/**
 * Class CoreCommandServiceProvider 命令行服务提供者
 *
 * @package Littlebug\Repository
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
            $this->commands([ModelCommand::class, RepositoryCommand::class, RequestCommand::class]);
        }
    }
}