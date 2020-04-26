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

[切换中文](./README.zh-CN.md) | [Usage of Repository](https://wanchaochao.github.io/laravel-repository/?page=repository)

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
composer require littlebug/laravel-repository:2.0.*
```
or add this to require section in your composer.json file:

```bash
"littlebug/laravel-repository": "2.0.*"
```
then run composer update

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

In addition to the injection method invocation described above, you can also use static method invocation; As follows:

```php
use Littlebug\Repository\Tests\Stubs\UserRepository;

$paginate = UserRepository::instance()->paginate(['status' => 1]);

// Query a piece of data and return an array
$user = UserRepository::instance()->find(['status' => 1, 'id:gt' => 2]);
```
### 1.4 Other common methods

#### Retrieve the data

| method name | return value | description|
|-------------|:------:|------------------|
| `find($conditions, $columns = ['*'])` | `null\|array`|Querying individual data|
| `findBy($conditions, $column)` | `null\|mixed`|Query a single field for a single piece of data|
| `findAll($conditions, $columns = ['*])` | `array`|Query multiple data|
| `findAllBy($conditions, $column)` | `array`|Querying a single field array of multiple data|
| `first($conditions, $column)` | `null\|model`|Retrieve a single model|
| `get($conditions, $column)` | `Illuminate\Database\Eloquent\Collection`|Retrieve the collection|

#### Statistical query

| method name | return value | description|
|-------------|:------:|------------------|
| `count($conditions, $column = '*')` | `int`|The number of statistical|
| `max($conditions, $column)` | `mixed`|The maximum|
| `min($conditions, $column)` | `mixed`|The minimum value|
| `avg($conditions, $column)` | `mixed`|The average|
| `sum($conditions, $column)` | `mixed`|sum|

#### Create or modify data

| method name | return value | description|
|-------------|:------:|------------------|
| `increment($conditions, $column, $amount = 1, $extra = [])` | `int` | Since the increase|
| `decrement($conditions, $column, $amount = 1, $extra = [])` | `int` | Since the reduction of|
| `firstOrCreate(array $attributes, array $value = [])` | `model` |The query does not exist so create|
| `updateOrCreate(array $attributes, array $value = [])` | `model` |Modifications do not exist so create|

### 1.5 More documentation

[Please check more about `repository`](https://wanchaochao.github.io/laravel-repository/?page=repository)

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