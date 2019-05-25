# laravel-repository
laravel repository 


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
            
            
### Install and use it

```bash
composer require littlebug/laravel-repository

mkdir app/Http/Requests

# touch a base Request to validate data

# just like this
```

[Request.php](https://github.com/Wanchaochao/laravel-repository/blob/master/src/littlebug/Request/Request.php)

```php
# Add the commands to commands register

# find the app\Console\Kernel.php

# add these codes on the top

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Littlebug\Commands\ControllerCommand;
use Littlebug\Commands\GenerateCommand;
use Littlebug\Commands\ModelCommand;
use Littlebug\Commands\RepositoryCommand;
use Littlebug\Commands\RequestCommand;
use Littlebug\Commands\ViewCommand;

# find the property $commands = []

# add these code to it
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

### About the commands to generate base code

```bash

# after register commands to your laravel project

# enter php artisan list

# if you see these , then you can use it to generate code quickly!~
```

![commands of generate code](/core-commands.jpg 'core of commands')

```bash
# let`s use it to generate code 

# type code with help of the document of commands

# if your project database used prefix, don`t forget to add prefix to app\config\database.php

# demo, generate code for member_message

php artisan core:generate --table=member_message --path=Member --controller=Member/MemberMessageController

# then you can see the result at you terminal

文件 [ /Users/wanchao/www/lara-test/app/Models/Member/MemberMessage.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Repositories/Member/MemberMessageRepository.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Controllers/Member/MemberMessageController.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/UpdateRequest.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/DestroyRequest.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Http/Requests/Member/MemberMessage/StoreRequest.php ] 生成成功

# maybe your base controller isn`t BaseController, please update the controller file by yourself~

# update the MemberMessageController

# maybe there`s no BaseController at app\Http\Controllers

# add route to routes/web.php

Route::group(['namespace' => 'Member','prefix' => 'member'], function ($route) {
    $route->get('index', 'MemberController@indexAction');
    $route->get('message', 'MemberMessageController@indexAction');
});

# dd the data of list, MemberMessageController

public function indexAction()
{
    $filters = Helper::filter_array(request()->all());
    $filters['order'] = 'id desc';
    $list = $this->memberMessageRepository->getList($filters);
    dd($list);
    return view('member.member_message.index', compact('list', 'filters'));
}



# terminal

php artisan serve

vist localhost:8001/member/message

# you can also change the table exists in your database
 
```

![data of member message](/data-list.jpg 'data of member message')


### Custom
```bash

# maybe you want to custom your owm Repository

# you can touch a Repository.php at app\Repository

# It can also extends Littlebug\Repository, maybe you don`t want to extends, it`s your choice

```


#### if my repository is helpful to you, give me a star to encourage me~ ✨