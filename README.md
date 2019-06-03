
### laravel-repository

![Progress](http://progressed.io/bar/100?title=completed)  

<p align="center">
	<a href="https:www.littlebug.vip">
		<img src="http://littlebug.oss-cn-beijing.aliyuncs.com/www.littlebug.vip/favicon.ico" width="75">
	</a>
</p>


[切换中文](/README.zh-CN.md) | [Repository的用法](/docs/Repository.md)

### clear tree of project

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
            
            
### Install and use it

```bash
composer require littlebug/laravel-repository

mkdir app/Http/Requests

# touch a base Request to validate data

# just like this
```

[Request.php](https://github.com/Wanchaochao/laravel-repository/blob/master/src/littlebug/Request/Request.php)

### About the commands to generate base code

```bash

# after register commands to your laravel project

# enter 

# php artisan list

# if you see these , then you can use it to generate code quickly!~
```

![commands of generate code](/docs/core-commands.png 'core of commands')

```bash
# let`s use it to generate code 

# type code with help of the document of commands

# if your project database used prefix, don`t forget to add prefix to app\config\database.php

# demo, generate code for member_message

php artisan core:generate --table=member_message --path=Member --controller=Member/MemberMessageController

# then you can see the result at you terminal

文件 [ /Users/wanchao/www/lara-test/app/Models/Member/MemberMessage.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Repositories/Member/MemberMessageRepository.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/UpdateRequest.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/DestroyRequest.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/StoreRequest.php ] 生成成功

# add route to routes/web.php

Route::group(['namespace' => 'Member','prefix' => 'member'], function ($route) {
    $route->get('index', 'MemberController@indexAction');
    $route->get('message', 'MemberMessageController@indexAction');
});

# update the MemberMessageController

# dd the data of list, MemberMessageController

public function indexAction()
{
    $filters = Helper::filter_array(request()->all());
    $filters['order'] = 'id desc';
    $list = $this->memberMessageRepository->paginate($filters);
    return view('member.member_message.index', compact('list', 'filters'));
}

# terminal

php artisan serve

vist localhost:8001/member/message

# you can also change the table exists in your database
 
```

![data of member message](/docs/data-list.jpg 'data of member message')


### Custom
```bash

# maybe you want to custom your owm Repository

# you can touch a Repository.php at app\Repository

# It can also extends Littlebug\Repository, maybe you don`t want to extends, it`s your choice

```

##### thanks for [JinxingLiu](https://mylovegy.github.io/blog/) and seven 💐🌹

##### if my repository is helpful to you, give me a star to encourage me~ ✨, I will continue to maintain this project.