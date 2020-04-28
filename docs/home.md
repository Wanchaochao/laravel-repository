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

## 1. Introduction

`laravel-repository` provides the basic `repository` class for [laravel](https://laravel.com/)
[model](https://learnku.com/docs/laravel/5.5/eloquent/1332) The package was made to provide more
More external methods, and more friendly editor prompts; layering the code, `repository` is 
responsible for external business logic processing, `model` is only responsible for the definition 
of the fields, attributes, query conditions, and return values of the data table. It does not 
participate in specific logical operations, and does not serve the control layer.

>  Relative to the direct use of `model` advantages:

- Solve the problem that `model` does not automatically handle extra fields when adding or modifying
- Optimize chained calls for `model` queries, query directly using arrays
- Automatically process corresponding associated data queries through query conditions and query fields
- Provides a more friendly editor prompt

## 2. Install

> Installation requirements

- PHP >= 7.0.0
- Laravel >= 5.5.0

> 1.1 Install package

```bash
composer require littlebug/laravel-repository:2.0.*
```
or add this to require section in your composer.json file:

```bash
"littlebug/laravel-repository": "2.0.*"
```
then run composer update

## 3. Used in the controller

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
        // Paging queries, returning paging objects
        $paginate = $this->userRepository->paginate([
            'name:like' => 'test', 
            'status'    => [1, 2], // Automatically converts to an in query
        ]);
        
        return view('users.index', compact('paginate'));
    }
    
    public function create()
    {
        // Add data and return an array
        $user = $this->userRepository->create(request()->all());
        dump($user);
    }
    
    public function update()
    {
        // Modify the data and return the number of modified rows
        $row = $this->userRepository->update(request()->input('id'), request()->all());
        dump($row);
    }
    
    public function delete()
    {
        // Deletes data and returns the number of rows deleted
        $row = $this->userRepository->delete(request()->input('id'));
        dump($row);
    }
}

```

## 4. Static method call

In addition to the injection method invocation described above, you can also use static method invocation; As follows:

```php
use Littlebug\Repository\Tests\Stubs\UserRepository;

$paginate = UserRepository::instance()->paginate(['status' => 1]);

// Query a piece of data and return an array
$user = UserRepository::instance()->find(['status' => 1, 'id:gt' => 2]);
```

## 5. More code generation commands

> Commands support specifying database connections such as --table=dev.users

### 5.1 `core:model` generates `model` class files and `repository` class files by querying database table information.

    ```bash
    php artisan core:model --table=users --name=User
    ```

### 5.2 `core:repository` generates the `repository` class file

    ```bash
    php artisan core:repository --model=User --name=UserRepository
    ```

### 5.3 `core:request` generates `request` verification class file by querying database table information

    ```bash
    php artisan core:request --table=users --path=Users
    ```

### 5.4 Command Parameter Details

![commands of generate code](https://wanchaochao.github.io/laravel-repository/docs/images/commands-en.png 'core of commands')