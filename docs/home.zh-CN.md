laravel-repository
==================

[TOC]

![Progress](http://progressed.io/bar/100?title=completed) 
[![Latest Stable Version](https://poser.pugx.org/littlebug/laravel-repository/v/stable)](https://packagist.org/packages/littlebug/laravel-repository)
[![Total Downloads](https://poser.pugx.org/littlebug/laravel-repository/downloads)](https://packagist.org/packages/littlebug/laravel-repository)
[![Latest Unstable Version](https://poser.pugx.org/littlebug/laravel-repository/v/unstable)](https://packagist.org/packages/littlebug/laravel-repository)
[![License](https://poser.pugx.org/littlebug/laravel-repository/license)](https://packagist.org/packages/littlebug/laravel-repository)
[![GitHub stars](https://img.shields.io/github/stars/Wanchaochao/laravel-repository.svg)](https://github.com/Wanchaochao/laravel-repository/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/Wanchaochao/laravel-repository.svg)](https://github.com/Wanchaochao/laravel-repository/issues)
[![GitHub forks](https://img.shields.io/github/forks/Wanchaochao/laravel-repository.svg)](https://github.com/Wanchaochao/laravel-repository/network)
[![Laravel](https://img.shields.io/badge/Laravel%20%5E5.5-support-brightgreen.svg)](https://github.com/laravel/laravel)

## 一、简介

`laravel-repository` 提供了基础的 `repository` 类, 对[laravel](https://laravel.com/) 的 
[model](https://learnku.com/docs/laravel/5.5/eloquent/1332) 进行了的封装，提供更
多的对外的方法，以及更友好的编辑器提示；对代码进行了的分层，`repository` 负责对外的业务逻辑处理，
`model` 只负责对数据表的字段、属性、查询条件、返回值的定义，不参与具体的逻辑运算，不对控制层服务

> 相对于直接使用`model`优势：

- 解决`model`在新增、修改时不自动处理多余字段问题
- 优化`model`查询时的链式调用，直接使用数组的方式进行查询
- 通过查询条件和查询字段，自动处理对应的关联数据查询
- 提供了更友好的编辑器提示

## 二、安装

> 安装要求

- PHP >= 7.0.0
- Laravel >= 5.5.0

> 安装包文件

```bash
composer require littlebug/laravel-repository:2.0.*
```
或者在你的项目 composer.json 文件中添加:

```bash
"littlebug/laravel-repository": "2.0.*"
```
然后执行 composer update

## 三、在控制器中使用

```php
<?php

use Illuminate\Routing\Controller;
use Littlebug\Repository\Tests\Stubs\UserRepository;

class UsersController extends Controller 
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function index()
    {
        // 分页查询、返回分页对象
        $paginate = $this->userRepository->paginate([
            'name:like' => 'test', 
            'status'    => [1, 2], // 自动转换为 in 查询
        ]);
        
        return view('users.index', compact('paginate'));
    }
    
    public function create()
    {
        // 添加数据、返回 model->toArray()
        $user = $this->userRepository->create(request()->all());
        dump($user);
    }
    
    public function update()
    {
        // 修改数据、返回受影响的行数
        $row = $this->userRepository->update(request()->input('id'), request()->all());
        dump($row);
    }
    
    public function delete()
    {
        // 删除数据、返回受影响的行数
        $row = $this->userRepository->delete(request()->input('id'));
        dump($row);
    }
}

```

## 四、静态方法调用

如果不想使用注入对象的方式调用的话，可以直接使用 `Repository` 对象静态方法 `instance()` 调用, 如下:

```php
use Littlebug\Repository\Tests\Stubs\UserRepository;

// 分页查询
$paginate = UserRepository::instance()->paginate(['status' => 1]);

// 查询一条数据
$user = UserRepository::instance()->find(['status' => 1, 'id:gt' => 2]);
```

## 五、命令行工具说明

> 如果需要指定数据库连接的名称，只需要在表名称前面添加数据库连接名称.就好 例如： --table=dev.users

### 1. `core:model` 生成 `model` 类文件和 `repository` 类文件

    ```bash
    php artisan core:model --table=users --name=User
    ```

### 2. `core:repository` 单独生成 `repository` 类文件

    ```bash
    php artisan core:repository --model=User --name=UserRepository
    ```

### 3. `core:request` 生成 `request` 验证类文件，会通过表结构信息生成对应的验证

    ```bash
    php artisan core:request --table=users --path=Users
    ```

### 4. 命令行参数详情

![commands of generate code](https://wanchaochao.github.io/laravel-repository/docs/images/commands-zh-cn.png 'core of commands')

