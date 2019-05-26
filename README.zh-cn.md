### laravel-repository

<p align="center">
	<a href="https:www.littlebug.vip">
		<img src="http://littlebug.oss-cn-beijing.aliyuncs.com/www.littlebug.vip/favicon.ico" width="75">
	</a>
</p>

[change to English](/README.md)

### 清晰的目录结构

* App
    * Http
        * Controller
            * Admin
                * IndexController
                * UserController
                * ConfigController
                * ...
        * Request
            * Admin
                * Index
                    * StoreRequest
                    * UpdateRequest
                    * DestroyRequest
                * User
                    * ...
                * Config
                    * ...
                * Request.php
    * Models (继承BaseModel)
        * User
            * User.php    
            * UserExt.php
            * UserMessage.php
        * Config
            * Config.php
            * ...
        * BaseModel.php
    * Repositories (目录结构应与model一致,结构清晰)
        * User
            * UserRepository.php
            * UserExtRepository.php
            * UserMessageRepository.php
        * ...
            
            
### 安装并使用

```bash
composer require littlebug/laravel-repository

mkdir app/Http/Requests

# 创建属于你自己的Request验证基类

# 就像下面这个文件
```

[Request.php](https://github.com/Wanchaochao/laravel-repository/blob/master/src/littlebug/Request/Request.php)

```php
# 注册commands命令

# 找到 app\Console\Kernel.php 文件

# 添加如下代码

use Littlebug\Commands\ControllerCommand;
use Littlebug\Commands\GenerateCommand;
use Littlebug\Commands\ModelCommand;
use Littlebug\Commands\RepositoryCommand;
use Littlebug\Commands\RequestCommand;
use Littlebug\Commands\ViewCommand;

# 找到属性 $commands = []

# 添加下面的代码至数组末尾
    [
        # your commands
        ...
        
        # litttlebug\commands
        ControllerCommand::class,
        GenerateCommand::class,
        ModelCommand::class,
        RepositoryCommand::class,
        RequestCommand::class,
        ViewCommand::class
    ]
    
```

### 关于一键生成代码

```bash

# 在将命令注入到你的laravel 项目以后

# 输入

# php artisan list

# 如果你看到下面这些提示，那么可以开始快速生成代码了!~
```

![commands of generate code](/core-commands.jpg 'core of commands')

```bash
# 让我们来试一下

# 在commands帮助文档的提示下生成代码

# 如果你的项目用到了数据库前缀，不要忘了去database.php中添加，否则会找不到table

# 举个栗子,以member_message表为例

php artisan core:generate --table=member_message --path=Member --controller=Member/MemberMessageController

# 在终端中你可以看到下面的结果

文件 [ /Users/wanchao/www/lara-test/app/Models/Member/MemberMessage.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Repositories/Member/MemberMessageRepository.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Controllers/Member/MemberMessageController.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/UpdateRequest.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/DestroyRequest.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/StoreRequest.php ] 生成成功

# 添加路由 routes/web.php

Route::group(['namespace' => 'Member','prefix' => 'member'], function ($route) {
    $route->get('index', 'MemberController@indexAction');
    $route->get('message', 'MemberMessageController@indexAction');
});

# 修改MemberMessageController

# 在MemberMessageController中dd打印数据

public function indexAction()
{
    $filters = Helper::filter_array(request()->all());
    $filters['order'] = 'id desc';
    $list = $this->memberMessageRepository->getList($filters);
    dd($list);
    return view('member.member_message.index', compact('list', 'filters'));
}



# 终端

php artisan serve

vist localhost:8001/member/message

# 你应该尝试一些你的数据库中存在的表，而不是机械的去复制粘贴我的栗子
 
```

![member message 的数据](/data-list.jpg 'member message 的数据')


### 自定义
```bash

# 也许你想自定义自己的Repository

# 创建一个 Repository.php 在 app\Repository

# 它也可以继承 Littlebug\Repository, 或许你不想继承，由你自己来决定

```


##### 如果这个仓库帮助到了你，给我一个star来鼓励我~ ✨,我会坚持继续维护这个仓库