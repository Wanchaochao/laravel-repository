laravel-repository
==================

<p align="center">
	<a href="https:www.littlebug.vip">
		<img src="http://littlebug.oss-cn-beijing.aliyuncs.com/www.littlebug.vip/favicon.ico" width="75">
	</a>
</p>

[change to English](/README.md) | [instruction of Repository](/docs/Repository.zh-CN.md) |

## 一 安装使用

### 1.1 安装包文件

```bash
composer require littlebug/laravel-repository
```

### 1.2 使用命令生成 `model` 和 `repository`

假设你的数据库中存在 users, 或者你将 users 替换为你数据库中的表名称

```bash
php artisan core:model --table=users --name=User
```
该命令会在:

- `app/Models/` 文件下生成 `User` 文件
- `app/Repositories/` 文件下生成 `UserRepository`  文件 

### 1.3 在控制器中使用 `repository`

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

#### 1.3.1 关于分页查询数据

![member message 的数据](./docs/data-list.jpg 'member message 的数据')

## 二 [关于`repository`更多使用方法请查看](./docs/Repository.zh-CN.md)

## 三 更多的代码生成命令

>命令都支持指定数据库连接 例如 --table=dev.users  

1. `core:model` 通过查询数据库表信息生成 `model` 类文件 和 `repository` 类文件

    ```bash
    php artisan core:model --table=users --name=User
    ```

2. `core:repository` 生成 `repository` 类文件 

    ```bash
    php artisan core:repository --model=User --name=UserRepository  
    ```

3. `core:request` 通过查询数据库表信息生成 `request` 验证类文件

    ```bash
    php artisan core:request --table=users --path=Users
    ```

### 命令参数详情

![commands of generate code](./docs/commands.png 'core of commands')


#### 感谢 天下第七 和 [jinxing.liu](https://mylovegy.github.io/blog/) 贡献的代码 💐🌹

#### 如果这个仓库帮助到了你，给我一个star来鼓励我~ ✨,我会坚持继续维护这个仓库