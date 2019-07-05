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

[TOC]

[切换中文](https://wanchaochao.github.io/laravel-repository/docs/home.zh-cn) | [Usage of Repository](https://wanchaochao.github.io/laravel-repository/docs/repository)

## Introduction

`laravel-repository` provides the basic `repository` class for [laravel](https://laravel.com/)
[model](https://learnku.com/docs/laravel/5.5/eloquent/1332) The package was made to provide more
More external methods, and more friendly editor prompts; layering the code, `repository` is 
responsible for external business logic processing, `model` is only responsible for the definition 
of the fields, attributes, query conditions, and return values of the data table. It does not 
participate in specific logical operations, and does not serve the control layer.


### Relative to the direct use of `model` advantages:

- Solve the problem that `model` does not automatically handle extra fields when adding or modifying
- Optimize chained calls for `model` queries, query directly using arrays
- Automatically process corresponding associated data queries through query conditions and query fields
- Provides a more friendly editor prompt

## Install

### Installation requirements

- PHP >= 7.0.0
- Laravel >= 5.5.0

### 1.1 Install package

```bash
composer require littlebug/laravel-repository
```

### 1.2 Use the command to generate `model` and `repository`

Suppose you have users in your database, or you replace users with the table names in your database.

```bash
php artisan core:model --table=users --name=User
```
The command will be at:

- Generate `User` file under `app/Models/` file
- Generate `UserRepository` file under `app/Repositories/` file

### 1.3 Using `repository` in the controller

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
        // Paging query
        $list = $this->userRepository->paginate([
            'name:like' => 'test123', 
            'status:in' => [1, 2],
        ]);
        
        return view('users.index');
    }
    
    public function create()
    {
        list($ok, $msg, $user) = $this->userRepository->create(request()->all());
        // You are right logic
    }
    
    public function update()
    {
        list($ok, $msg, $row) = $this->userRepository->update(request()->input('id'), request()->all());
        // You are right logic
    }
    
    public function delete()
    {
        list($ok, $msg, $row) = $this->userRepository->delete(request()->input('id'));
        // You are right logic
    }
}

```

#### 1.3.1 About paging query data

![member message 的数据](https://wanchaochao.github.io/laravel-repository/docs/images/data-list.jpg 'member message 的数据')

## [Please check more about `repository`](https://wanchaochao.github.io/laravel-repository/docs/Repository.md)
[Please check more about `repository`](https://wanchaochao.github.io/laravel-repository/docs/Repository.md)

## More code generation commands

> Commands support specifying database connections such as --table=dev.users

1. `core:model` generates `model` class files and `repository` class files by querying database table information.

    ```bash
    php artisan core:model --table=users --name=User
    ```

2. `core:repository` generates the `repository` class file

    ```bash
    php artisan core:repository --model=User --name=UserRepository
    ```

3. `core:request` generates `request` verification class file by querying database table information

    ```bash
    php artisan core:request --table=users --path=Users
    ```

### Command Parameter Details

![commands of generate code](https://wanchaochao.github.io/laravel-repository/docs/images/commands.png 'core of commands')

#### thanks for [jinxing.liu](https://mylovegy.github.io/blog/) and seven 💐🌹

#### if my repository is helpful to you, give me a star to encourage me~ ✨, I will continue to maintain this project.