laravel-repository
==================

<p align="center">
	<a href="https:www.littlebug.vip">
		<img src="http://littlebug.oss-cn-beijing.aliyuncs.com/www.littlebug.vip/favicon.ico" width="75">
	</a>
</p>

[change to English](/README.md) | [instruction of Repository](/docs/Repository.zh-CN.md) |

### 安装并使用

#### 安装包文件

```bash
composer require littlebug/laravel-repository
```

#### 使用命令生成 `model` 和 `repository`

假设你的数据库中存在 users, 或者你将 users 替换为你数据库中的表名称

```bash
php artisan core:model --table=users --name=User
```
该命令会在:

- `app/Models/` 文件下生成 `User` 文件
- `app/Repositories/` 文件下生成 `UserRepository`  文件 

#### 在控制器中使用 `repository`

```php

use App\Repositories\UserRepository;

class UsersController extends Controller 
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository   = $userRepository;
    }
    
    public function index()
    {
        // 分页查询
        $list = $this->userRepository->paginate([
            'name:like' => 'test123', 
            'status:in' => [1, 2],
        ]);
        
        return view('users.index');
    }
    
    public function create()
    {
        list($ok, $msg, $row) = $this->userRepository->create(request()->all());
        // 你的逻辑
    }
    
    public function update()
    {
        list($ok, $msg, $row) = $this->userRepository->update(request()->input('id'), request()->all());
        // 你的逻辑
    }
    
    public function delete()
    {
        list($ok, $msg, $row) = $this->userRepository->delete(request()->input('id'));
        // 你的逻辑
    }
}

```
```bash

# 在将命令注入到你的laravel 项目以后

# 输入

php artisan list

# 如果你看到下面这些提示，那么可以开始快速生成代码了!~
```

![commands of generate code](/docs/core-commands.png 'core of commands')

```bash
# 让我们来试一下

# 在commands帮助文档的提示下生成代码

# 如果你的项目用到了数据库前缀，不要忘了去database.php中添加，否则会找不到table

# 举个栗子,以member_message表为例

php artisan core:generate --table=member_message --path=Member --controller=Member/MemberMessageController

# 在终端中你可以看到下面的结果

文件 [ /Users/wanchao/www/lara-test/app/Models/Member/MemberMessage.php ] 生成成功
文件 [ /Users/wanchao/www/lara-test/app/Repositories/Member/MemberMessageRepository.php ] 生成成功
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

public function index()
{
    $filters = Helper::filter_array(request()->all());
    $filters['order'] = 'id desc';
    $list = $this->memberMessageRepository->paginate($filters);
    return view('member.member_message.index', compact('list', 'filters'));
}

# 终端

php artisan serve

vist localhost:8001/member/message

# 你应该尝试一些你的数据库中存在的表，而不是机械的去复制粘贴我的栗子
 
```

![member message 的数据](/docs/data-list.jpg 'member message 的数据')


### 自定义
```bash

# 也许你想自定义自己的Repository

# 创建一个 Repository.php 在 app\Repository

# 它也可以继承 Littlebug\Repository, 或许你不想继承，由你自己来决定

```

##### 感谢 天下第七 和 [鑫鑫](https://mylovegy.github.io/blog/) 贡献的代码 💐🌹

##### 如果这个仓库帮助到了你，给我一个star来鼓励我~ ✨,我会坚持继续维护这个仓库