laravel-repository
==================

![Progress](http://progressed.io/bar/100?title=completed) 
[![Latest Stable Version](https://poser.pugx.org/littlebug/laravel-repository/v/stable)](https://packagist.org/packages/littlebug/laravel-repository)
[![Total Downloads](https://poser.pugx.org/littlebug/laravel-repository/downloads)](https://packagist.org/packages/littlebug/laravel-repository)
[![Latest Unstable Version](https://poser.pugx.org/littlebug/laravel-repository/v/unstable)](https://packagist.org/packages/littlebug/laravel-repository)
[![License](https://poser.pugx.org/littlebug/laravel-repository/license)](https://packagist.org/packages/littlebug/laravel-repository)
[![GitHub stars](https://img.shields.io/github/stars/Wanchaochao/laravel-repository.svg)](https://github.com/Wanchaochao/laravel-repository/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/Wanchaochao/laravel-repository.svg)](https://github.com/Wanchaochao/laravel-repository/issues)
[![GitHub forks](https://img.shields.io/github/forks/Wanchaochao/laravel-repository.svg)](https://github.com/Wanchaochao/laravel-repository/network)
[![Laravel](https://img.shields.io/badge/Laravel%20%5E5.5-support-brightgreen.svg)](https://github.com/laravel/laravel)

[change to English](./home.md) | [Repository 使用文档](./repository.zh-cn.md) 

## 简介

`laravel-repository` 提供了基础的 `repository` 类, 对[laravel](https://laravel.com/) 的 
[model](https://learnku.com/docs/laravel/5.5/eloquent/1332) 进行了的封装，提供更
多的对外的方法，以及更友好的编辑器提示；对代码进行了的分层，`repository` 负责对外的业务逻辑处理，
`model` 只负责对数据表的字段、属性、查询条件、返回值的定义，不参与具体的逻辑运算，不对控制层服务

### 相对于直接使用`model`优势：

- 解决`model`在新增、修改时不自动处理多余字段问题
- 优化`model`查询时的链式调用，直接使用数组的方式进行查询
- 通过查询条件和查询字段，自动处理对应的关联数据查询
- 提供了更友好的编辑器提示

## 安装使用

### 安装要求

- PHP >= 7.0.0
- Laravel >= 5.5.0

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
        list($ok, $msg, $user) = $this->userRepository->create(request()->all());
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

![member message 的数据](https://wanchaochao.github.io/laravel-repository/docs/images/data-list.jpg 'member message 的数据')

## [关于`repository`更多使用方法请查看](./repository.zh-cn.md)

## 更多的代码生成命令

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

![commands of generate code](https://wanchaochao.github.io/laravel-repository/docs/images/commands.png 'core of commands')


#### 感谢 天下第七 和 [jinxing.liu](https://mylovegy.github.io/blog/) 贡献的代码 💐🌹

#### 如果这个仓库帮助到了你，给我一个star来鼓励我~ ✨,我会坚持继续维护这个仓库