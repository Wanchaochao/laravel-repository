Repository The basic use
==================

[TOC]

## 1、Create

New data

```
public function create(array $data);
```

#### parameter
- `$data` Added data (will automatically filter out non-table field information and is not allowed to be null)

#### return value

- `array` 

#### example

```php
$user = $this->repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address'    => 'America'
]);

// create Method filters data from non-model fields, so you can use it directly: request()->all();
$user = UserRepository::instance()->create(request()->all());
```

## 2、Update

```
public function update($conditions, array $updateValues);
```

#### parameter
- `$conditions` Modified conditions[Support for multiple types of queries](/?page=repository#五、查询条件说明)
- `$updateValues` Modified data (non-table field information is automatically filtered out, and is not allowed to be null)

#### return value
- `int` affected rows

#### example
```php
// A single modification of the primary key
$row = $this->repository->update(1, ['user_name' => 'Tony', 'status' => 2]);

// Primary key multiple modifications
$row = $this->repository->update([1, 2, 3, 4], ['user_name' => 'Tony', 'status' => 2]);

// Expression query modification
$row = $this->repository->update([
    'id:gt'  => 2,
    'status' => 1,
], ['user_name' => 'Tony', 'status' => 2]);
```
> Batch modification is used, **But you can use the modifier of the model**
> `$conditions` Modify conditional support, primary keys, arrays, expressions

## 3、Delete

Deletes data and returns the number of affected rows

```
public function update($conditions, array $updateValues);
```

#### parameter
- `$conditions` Conditions for deletion [Support for multiple types of queries](/?page=repository#五、查询条件说明)

#### return value
- `int` affected rows

```php
// Primary key single delete
$row = $this->repository->delete(1);

// Primary key multiple deletes
$row = $this->repository->delete([1, 2, 3, 4, 5]);

// Expression array delete
$row = $this->repository->delete(['id:gt' => 2, 'status' => 1]);
```

## 4、Query

In all query methods, `$conditions` represents the query condition and  `$columns` represents the query field

### 4.1 find Query a single

> `find($conditions, $columns = [])` 

```php
// The primary key query
$item = $this->repository->find(1);

// Expression array query
$item = $this->repository->find(['status' => 1, 'age:gt' => 2]);
```

> `findBy($conditions, $column)` Querying a single field

```php
$name = $this->repository->findBy(1, 'username');
```

### 4.2 findAll Query multiple

> `findAll($conditions, $columns = [])` Query multiple

```php
// The primary key query
$item = $this->repository->findAll([1, 2, 3, 4, 5]);

// Expression array query
$item = $this->repository->findAll(['status' => 1, 'age:gt' => 2, 'id' => [1, 2, 3, 4]]);
```

> `findAllBy($conditions, $column)` Querying a single field

```php
$names = $this->repository->findAllBy([1, 2, 3, 4], 'username');
```

### 4.3 paginate Paging query

> `paginate($conditions, $columns = [], $size = 10, $current = null)` Paging queries that return paging objects

#### parameter
- `$conditions` Query conditions
- `$columns`    Query field
- `$size`       Represents the number of rows per page
- `$current` Represents the current page (does not automatically get the `page` value of the request parameter)

```php
$pagination = $this->repository->paginate(['status' => 1], ['id', 'name', 'age', 'status']);
```

### 4.4 Filter null value queries

In our business scenarios, we often judge whether to add a specified condition based on the request parameters. For example, common background search list business：

```php
$conditions = [];
if ($username = request()->input('username')) {
    $conditions['username:like'] = $username;
}

if ($status = request()->input('status')) {
    $conditions['status'] = $status;
}

if ($age = request()->input('age')) {
    $conditions['age:gt'] = $age;
}

$pagination = $this->repositpory->paginate($conditions);
```

Use `filter` The serial approach simplifies our code， `filter` Series methods automatically filter out null values in query conditions; The above code is written using `filterPaginate`
> Null characters, null, empty array, ' ' are considered null values

```php
$pagination = $this->repositpory->filterPaginate([
    'username:like' => request()->input('username'),
    'status'        => request()->input('status'),
    'age:gt'        => request()->input('age'),
]);
```

Other `filter` Methods:

#### filterFind($conditions, $columns = []) Query a single piece of data
#### filterFindBy($conditions, $column) Querying a single field
#### filterFindAll($conditions, $columns = []) Query multiple data
#### filterFindAllBy($conditions, $column) Query an array of individual fields

## 5、Description of query conditions

For query conditions `$conditions` instructions,**includes modified and deleted query conditions**

### 5.1 Primary key, array query

Define the way: `[key => value]` 

```php
// Simple primary key query
$user = $this->repositpory->find(1);
// Array primary key query
$users = $this->repositpory->findAll([1, 2, 3]);
// [key => value] query
$users = $this->repositpory->findAll([
    'status' => 1,
    'name'   => 'test',
    'type'   => [1, 2, 3], // This will be automatically converted to an in query
]);
```

### 5.2 Expression query

Queries by defined expressions, or operators

1. Expression definition: `['field:expression' => 'value']`
2. Operator definition: `['field:operator' => 'value']`

```php
// Using expressions
$user = $this->repositpory->findAll([
    'parent_id:eq'       => 0,         // =
    'status:in'          => [1, 2, 3], // in
    'id:gt'              => 100,       // >
    'age:lt'             => 35,        // <
    'created_at:between' => [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')],
]);

// Use operator
$users = $this->repositpory->findAll([
    'status:in'          => [1, 2, 3], // in
    'id:>='              => 100,       // >
    'age:<='             => 35,        // <
    'created_at:between' => [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')],
]);
```

#### Currently supported expressions:

| expression  | meaning                                  | Special instructions                                                                                        |
| :---------- | :--------------------------------------- | :---------------------------------------------------------------------------------------------------------- |
| eq          | equal(=)                                 |                                                                                                             |
| neq         | not equal(!=)                            |                                                                                                             |
| ne          | not equal(!=)                            |                                                                                                             |
| gt          | greater(>)                               |                                                                                                             |
| egt         | Greater than or equal to(>=)             |                                                                                                             |
| gte         | Greater than or equal to(>=)             |                                                                                                             |
| ge          | Greater than or equal to(>=)             |                                                                                                             |
| lt          | less(<)                                  |                                                                                                             |
| le          | Less than or equal to(<=)                |                                                                                                             |
| lte         | Less than or equal to(<=)                |                                                                                                             |
| elt         | Less than or equal to(<=)                |                                                                                                             |
| in          | IN                                       | Incoming data is strongly converted to an array                                                             |
| not in      | NOT IN                                   | Incoming data is strongly converted to an array                                                             |
| not_in      | NOT IN                                   | Incoming data is strongly converted to an array                                                             |
| between     | Range queries(between)                   | Incoming data is strongly converted to an array                                                             |
| not_between | Non-interval query(not between)          | Incoming data is strongly converted to an array                                                             |
| not between | Non-interval query(not between)          | Incoming data is strongly converted to an array                                                             |
| like        | No fuzzy queries contain queries(like)   | Will automatically determine the addition of % fuzzy query; Incoming data is strongly converted to a string |
| not_like    | Fuzzy queries are not included(not like) | Will automatically determine the addition of % fuzzy query; Incoming data is strongly converted to a string |
| not like    | Fuzzy queries are not included(not like) | Will automatically determine the addition of % fuzzy query; Incoming data is strongly converted to a string |
| rlike       | No fuzzy queries contain queries(rlike)  |                                                                                                             |
| <>          | not equal(<>)                            |                                                                                                             |

#### `like`, `not like` The query specification

```php
// Fuzzy queries before and after are not added, they are added automatically username like '%test%'
$this->repository->findAll(['username:like' => 'test']);

// Fuzzy queries with prefixes or suffixes are added, then not processed username like 'test%'
$this->repository->findAll(['username:like' => 'test%']);

// If the above like queries do not meet your needs, you can use native SQL queries
$this->repository->findAll(['username' => DB::raw("like 'username'")]);
```

### 5.3 预定义字段查询

有些预定义的 key 是做特殊查询用的

```php
$this->repository->findAll([
    'limit' => 10,                        // 限制查询10条
    'order' => 'id desc, created_at asc', // 指定排序条件
    'group' => 'id',                      // 通过 id 分组
]);
```

#### 预定义的字段

| 字段名称        | 字段值类型        | 说明                                |
| --------------- | ----------------- | ----------------------------------- |
| `and`           | `array`           | 添加`and`查询条件; 只能传递一个数组 |
| `or`            | `array`           | 添加`or`查询条件; 只能传递一个数组  |
| `force`         | `string`          | 强制走指定索引                      |
| `order`         | `string or array` | 指定排序条件                        |
| `limit`         | `int`             | 指定查询条数                        |
| `offset`        | `int`             | 指定跳转位置                        |
| `group`         | `string`          | 指定分组字段                        |
| `groupBy`       | `string`          | 指定分组字段                        |
| `join`          | `array`           | 查询join的参数，多个二维数组        |
| `leftJoin`      | `array`           | 查询`leftJoin`的参数、多个二维数组  |
| `rightJoin`     | `array`           | 查询`rightJoin`的参数、多个二维数组 |
| `joinWith`      | `string or array` | 通过关联关系对应join查询            |
| `leftJoinWith`  | `string or array` | 通过关联关系对应leftJoin查询        |
| `rightJoinWith` | `string or array` | 通过关联关系对应rightJoin查询       |

#### `and`, `or` 查询说明

值必须为一个数组，里面支持`[key => value]` 和 [表达式查询方式](/?page=repository#5.2-表达式查询)的数组；表示数组里面的查询条件通过什么连接

> 支持嵌套使用 `and` 和 `or`

示例：

```php
$this->repository->findAll([
    'status' => 1,
    'or'     => [
        'username:like' => 'test',
        'age:gt'        => 10,
        'and'           => [
            'user_id' => [1, 2, 3],
            'gener'   => 1,
        ],
    ]
]);
```

执行SQL:

```SQL
select `users`.*
from `users`
where `users`.`status` = 1 and (
    `users`.`username` like '%test%' or
    `users`.`age` > 10 or
    (
        `users`.`user_id` in (1, 2, 3) and
        `users`.`gener` = 1
    )
)
```

### 5.4 关联关系join查询

>前提是你的model定义了表的关联

例如下面：

用户Model
```php
<?php

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function ext()
    {
        return $this->hasOne(UserExt::class, 'user_id', 'user_id');
    }
}
```

用户扩展信息Model

```php
<?php

use Illuminate\Database\Eloquent\Model;

class UserExt extends Model
{
    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }
}
```

那么你在查询的时候可以通过关联关系进行join 查询(通过定义关联的关系，自动处理你的join)

```php

// userRepository
UserRepostiory::instance()->findAll([
    'status'   => 1,
    'joinWith' => 'ext', // ext 表示关联方法名称， 多个需要使用数组 ['ext', 'children']
]);

```
执行的SQL:
```SQL
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) where `users`.`status` = 1
```

```php
// userExtRepository
UserExtRepository::instance()->findAll([
    'status'   => 1,
    'joinWith' => 'user',
]);
```

执行的SQL:
```SQL
select `user_ext`.* from `user_ext` inner join `users` on (`users`.`user_id` = `user_ext`.`user_id`) where `user_ext`.`status` = 1
```

##### leftJoinWith 和 rightJoinWith

>如果需要使用 `leftJoin` 或者 `rightJoin` 的使用 `leftJoinWith` 或者 `rightJoinWith` 就好了

##### 添加join查询查询条件

通过：`['表名字.字段' => '查询值']`

```php
UserRepostiory::instance()->findAll([
    'status'                 => 1,
    'joinWith'               => 'ext',
    'user_ext.status:neq'    => 1,
    'user_ext.created_at:gt' => date('Y-m-d H:i:s')
]);
```
执行的SQL:
```SQL:
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) where `users`.`status` = 1 and `user_ext`.`status` != 1 and `user_ext`.`created_at` > '2020-04-29 22:31:00'
```

##### 给join表命别名

通过： `['别名' => '关联方法名']`

```php

UserRepostiory::instance()->findAll([
    'status'           => 1,
    'joinWith'         => ['t1' => 'ext'],
    't1.status:neq'    => 1,
    't1.created_at:gt' => date('Y-m-d H:i:s')
]);
```

执行的SQL:
```SQL
select `users`.* from `users` inner join `user_ext` AS `t1` on (`users`.`user_id` = `t1`.`user_id`) where `users`.`status` = 1 and `t1`.`status` != 1 and `t1`.`created_at` > '2020-04-29 22:31:00'
```

### 5.5 关联查询附加条件

**切记关联查询不是join查询** 关联查询是主表查询完成后，通过定义的关联然后再去查询关联表，是执行了两条SQL

定义方式： `['rel.关联方法名称.关联表字段' => '查询的值']`

[Model 使用 5.4 定义的model](/?page=repository#5.4-使用关联关系join查询)

```php
UserRepostiory::instance()->find([
    'user_id'        => 1,
    'rel.ext.status' => 1, // 为关联表查询添加条件
    'rel.ext.type'   => 2, // 为关联表查询添加条件
], ['*', 'ext' => ['*']]);

```

最终执行的SQL

1. 主表查询
```SQL
select `users`.* from `users` where `users`.`user_id` = 1
```

2. 关联表查询
```SQL
select `user_ext`.* from `user_ext` where `user_id` in (1) and `user_ext`.`status` = 1 and `user_ext`.`type` = 1
```

### 5.6 join 查询

使用 join 查询

```php
UserRepostiory::instance()->findAll([
    'status'                 => 1,
    'join'                   => ['user_ext', 'users.user_id', '=', 'user_ext.user_id'],
    'user_ext.status:neq'    => 1,
    'user_ext.created_at:gt' => date('Y-m-d H:i:s')
]);
```

执行SQL:
```SQL
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) where `users`.`status` = 1 and `user_ext`.`status` != 1 and `user_ext`.`created_at` > '2020-04-29 22:31:00'
```

##### leftJoin 和 rightJoin

>直接使用 `leftJoin` 或者 `rightJoin` 就好了

#### 一次存在多个join

需要将join定义为二维数组

```php
UserRepostiory::instance()->findAll([
    'join'=> [
        ['user_ext', 'users.user_id', '=', 'user_ext.user_id'],
        ['users as t1', 'users.user_id', '=', 't1.user_id']
    ],
]);
```

执行SQL:
```SQL
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) inner join `users` as `t1` on (`users`.`user_id` = `t1`.`user_id`)
```
### 5.7 scope 查询

需要 `Model` 定义了 `scope` 前缀的查询方法

```php
<?php

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function scopeUsername($query, $username)
    {
        return $query->where('name', 'like', $username);
    }

    public function scopeJoinExt($query, $status)
    {
        return $query->join('user_ext', 'users.user_id', '=', 'user_ext.user_id')->where('user_ext.status', $status);
    }
}
```

定义方式：`['去掉scope前缀方法名称' => '需要的参数']`

```php
UserRepostiory::instance()->findAll([
    'username' => 'test',
    'joinExt'  => 1,
]);
```

执行的SQL:
```SQL
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) where `name` like 'test' and `user_ext`.`status` = 1
```

### 5.8 原生SQL查询

> 慎用；存在SQL注入风险

使用：DB::raw() 函数包裹查询条件

```php
UserRepostiory::instance()->findAll([
    DB::raw("user_id = 1 and status = 1"),
    'name' => DB::raw('like "_test"'),
]);
```

### 总结
```php
$conditions = [
    // 表中字段精准查询
    'status' => 1,
    'id'     => [1, 2, 3, 4], // 值为数组会自动转为in查询 `id` in (1, 2, 3, 4)

    // 预定义字段查询
    'order' => 'id desc', // 指定排序字段和方式
    'limit' => 10,        // 限制查询条件
    'group' => 'id',      // 指定分组条件
    'force' => 'name',    // 指定使用的索引

    // join 关联查询
    'join'     => ['users', 'users.user_id', '=', 'orders.user_id'],
    'leftJoin' => [
        // 多个leftJoin
        ['users as u1', 'u1.user_id', '=', 'orders.user_id'],
        ['user_image', 'users_image.user_id', '=', 'users.user_id'],
    ],

    // 表达式查询
    'username:like'      => 'test',                                         // 模糊查询
    'created_at:between' => ['2019-01-02 00:00:00', '2019-01-03 23:59:59'], // 区间查询
    'id:ge'              => 12, // id > 12

    // relation 关联查询,查询条件只对当前relation关联查询限制
    'rel.ext.address:like'   => '北京',
    'rel.ext.created_at:gte' => '2019-01-01 00:00:00',

    // 通过 relation 关联关系，添加join 查询
    'joinWith'     => ['ext'],
    // 关联表定义别名 alias, 如果没有别名，关联表和主表同名，使用自定义别名 `t1`, 多个同名以此地址 `t2`、`t3`
    'leftJoinWith' => ['alias' => 'children'],

    // 为join连表查询添加条件
    'user_ext.status' => 1,
    'users.status'    => 1,

    // scope 自定义查询
    'address'  => '北京',      // 查找`scopeAddress($query, $address)`方法
    'children' => [1, 2, 3],  // 查找`scopeChildren($query, $childrenIds)`方法
];
```

>如果查询字段匹配不到上述的9种方式，那么会将查询字段转为方法名称，查询值为参数直接调用`Illuminate\Database\Eloquent\Builder`的方法（**如果字段方法不存在、程序抛错**, 这一点有别于 1.0.* 版本） 例如：

```php
UserRepostiory::instance()->findAll([
    'with'        => ['ext', 'children'],
    'orderByDesc' => 'id',
    'limit'       => 10,
]);

// 内部实际调用
// $query->with(['ext', 'children'])->orderByDesc('id')->limit(10);
```

## 六、查询字段说明

对查询字段 `$columns` 说明, 就是 select 的字段

### 6.1 查询本表字段

本表的字段直接写

```php
// select `user_id`, `name` from `users` where `user_id` = 1
$this->userRepostiory->find(1, ['user_id', 'name']);
```

### 6.2 查询关联表字段

>model 需要定义的关联关系

通过： `['关联方法' => ['字段信息']]`

```php
$this->userRepostiory->find(1, ['user_id', 'name', 'ext' => ['status', 'avatar', 'auth_id']]);
```

### 6.3 查询关联表统计

>model 需要定义的关联关系

通过： `['关联方法名称_count']`

```php
$this->userRepostiory->find(1, ['user_id', 'name','ext_count']);
```
// 使用的是`model`的`withCount()` 方法
```php
// `model` 的写法
User::select(['user_id', 'name'])->withCount('ext')->where('user_id', 1)->first()->toArray();
```
### 6.4 查询join表字段

```php
$this->userRepostiory->find([
    'joinWith' => 'ext',
], ['user_id', 'name', 'user_ext.status']);
```

### 6.5 查询原生SQL字段

```php
$this->userRepostiory->find([
    'status' => 1,
], [
    DB::raw('COUNT(*) AS `count_number`'),
    DB::raw('MAX(`user_id`) AS `max_user_id`'),
    DB::raw('AVG(`age`) AS `avg_age`'),
]);
```

### 6.6 总结

```php
$columns = [

    // 本表的字段查询
    'id',
    'username',

    // 关联表字段查询
    'ext'      => ['*'], // 对应`model`定义的 ext 关联
    'children' => ['*'], // 对应`model`定义的 children 关联

    // 关联表统计字段查询
    'ext_count',      // 对应`model`定义的 ext 关联
    'children_count', // 对应`model`定义的 children 关联

    // join表字段
    'users.username',
    'users.age',

    // 原生SQL字段
    DB::raw('SUM(`users`.`age`) AS `sum_age`'),
    DB::raw('COUNT(*) AS `total_count`'),
];
```

## 七、增删改的事件方法

子类定义了这些方法，才会执行，如果想阻止主方法执行，并能让主方法返回错误信息，直接抛出错误就可以

### 7.1 新增的事件 在`create($data)` 执行的时候触发

1. `beforeCreate($data)` 新增之前

2. `afterCreate($data, $news)`  新增之后

#### 7.1.1 参数说明

| 参数名称 | 参数类型 | 参数说明                               |
| -------- | -------- | -------------------------------------- |
| `$data`  | `array`  | 过滤掉干扰数据(非表中字段的数据)的数组 |
| `$news`  | `array`  | 新增成功调用 `model->toArray()` 数组   |

### 7.2 修改的事件 在`update($conditions, array $data)` 执行的时候触发

1. `beforeUpdate($conditions, $data)` 修改之前

2. `afterUpdate($conditions, $data, $row)` 修改之后

#### 7.2.1 参数说明

| 参数名称      | 参数类型 | 参数说明                               |
| ------------- | -------- | -------------------------------------- |
| `$conditions` | `array`  | 处理了主键查询后的查询条件数组         |
| `$data`       | `array`  | 过滤掉干扰数据(非表中字段的数据)的数组 |
| `$row`        | `int`    | 修改受影响的行数                       |

### 7.3 删除的事件 在`delete($conditions)` 执行的时候触发

1. `beforeDelete($conditions)` 删除之前

2. `afterDelete($conditions, $row)` 删除之后

#### 7.3.1 参数说明

| 参数名称      | 参数类型 | 参数说明                       |
| ------------- | -------- | ------------------------------ |
| `$conditions` | `array`  | 处理了主键查询后的查询条件数组 |
| `$row`        | `int`    | 删除受影响的行数               |

### 7.4 关于`$conditions` 处理为主键查询

不为空的 字符串、整数、浮点数、索引数组 都会被转为主键查询

```php
// 假设表的主键为id
$conditions = 1;            // 会被转为 ['id' => 1]
$conditions = '1';          // 会被转为 ['id' => '1']
$conditions = [1, 2, 3];    // 会被转为 ['id' => [1, 2, 3, 4]]
```

## 八、其他说明

### 8.1 查询返回说明

#### 8.1.1 查询返回数组的方法
返回的是model->toArray()

##### create(array $data)
##### find($conditions, $columns = [])
##### findAll($conditions, $columns = [])

#### 8.1.2 返回的是对象的方法
返回的model对象或者集合
##### first($conditions, $columns = [])
##### firstOrFail($conditions, $columns = [])
##### firstOrCreate($attributes, $values)
##### updateOrCreate($attributes, $values)
##### get($conditions, $columns = [])

### 8.2 对于`model`的要求

1. `create` 和 `update` 都是批量赋值，需要`model`定义批量赋值的白名单`$fillable` 或者 黑名单 `$guarded`
2. 需要定义 `$columns` 字段信息，表示表中都有哪些字段

```php
class Posts extends \Illuminate\Database\Eloquent\Model
{
    public $guarded = ['id'];

    public $columns = [
        'id',
        'title',
        'content',
        'created_at',
        'updated_at'
    ];
}

```

>虽然这一步是非必须的，但定义了`$columns`会减少一次`SQL`查询的代价