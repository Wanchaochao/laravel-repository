Repository 基本使用
==================

[TOC]

## 一、新增数据

使用 `create(array $data)` 方法, 返回的 `model->toArray()` 数组

```php
$user = $this->repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address'    => 'America'
]);

// create 方法能够过滤非model字段的数据、所以你可以直接使用request()->all();
$user = UserRepository::instance()->create(request()->all());
```

## 二、修改数据

使用 `update($conditions, array $update_data)` 方法、返回受影响行数

```php
// 主键单个修改
$row = $this->repository->update(1, ['user_name' => 'Tony', 'status' => 2]);

// 主键多个修改
$row = $this->repository->update([1, 2, 3, 4], ['user_name' => 'Tony', 'status' => 2]);

// 表达式查询修改
$row = $this->repository->update([
    'id:gt'  => 2,  
    'status' => 1,
], ['user_name' => 'Tony', 'status' => 2]);
```
> 使用的是批量修改方式，但**能够使用模型的修改器**
> `$conditions` 修改条件支持，主键、数组、表达式

## 三、删除数据

使用 `delete($conditions)` 方法、返回受影响行数

```php
// 主键单个删除
$row = $this->repository->delete(1);

// 主键多个删除
$row = $this->repository->delete([1, 2, 3, 4, 5]);

// 表达式数组删除
$row = $this->repository->delete(['id:gt' => 2, 'status' => 1]);
```

## 四、查询数据

所有查询方法中 `$conditions` 表示查询条件， `$columns` 表示查询字段

### 4.1 find 查询单个

> `find($conditions, $columns = [])` 查询单条数据

```php
// 主键查询
$item = $this->repository->find(1); 

// 表达式数组查询
$item = $this->repository->find(['status' => 1, 'age:gt' => 2]); 
```

> `findBy($conditions, $column)` 查询单个字段

```php
$name = $this->repository->findBy(1, 'username');
```

### 4.2 findAll 查询多个

> `findAll($conditions, $columns = [])` 查询多条数据

```php
// 主键查询
$item = $this->repository->findAll([1, 2, 3, 4, 5]); 

// 表达式数组查询
$item = $this->repository->findAll(['status' => 1, 'age:gt' => 2, 'id' => [1, 2, 3, 4]]); 
```

> `findAllBy($conditions, $column)` 查询单个字段

```php
$names = $this->repository->findAllBy([1, 2, 3, 4], 'username');
```

### 4.3 paginate 分页查询

> `paginate($conditions, $columns = [], $size = 10, $current = null)` 分页查询， 返回分页对象
> `$size` 表示每页多少条 `$current` 表示当前页(不传自动获取请求参数的 `page` 的值)

```php
$pagination = $this->repository->paginate(['status' => 1], ['id', 'name', 'age', 'status']);
```

### 4.4 filter系列过滤空值查询

在我们业务场景中，经常会根据请求参数来判断是否添加指定条件；例如常见的后台搜索列表业务中：

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

使用 `filter` 系列方法可以简化我们的代码， `filter` 系列方法会自动过滤掉查询条件中的空值；上述代码使用 `filterPaginate`写法
> 空字符，null, 空数组、' ' 会被认为是空值

```php
$pagination = $this->repositpory->filterPaginate([
    'username:like' => request()->input('username'),
    'status'        => request()->input('status'),
    'age:gt'        => request()->input('age'),
]);
```

其他`filter`系列方法:

#### filterFind($conditions, $columns = []) 查询单条数据
#### filterFindBy($conditions, $column) 查询单个字段
#### filterFindAll($conditions, $columns = []) 查询多条数据
#### filterFindAllBy($conditions, $column) 查询单个字段数组

## 五、查询条件说明

对于查询条件 `$conditions` 说明,**包括修改和删除的查询条件**

### 5.1 简单主键、数组查询

就是简单的 [key => value] 数组方式

```php
// 简单主键查询
$user = $this->repositpory->find(1);
// 数组主键查询
$users = $this->repositpory->findAll([1, 2, 3]);
// 简单[key => value]查询
$users = $this->repositpory->findAll([
    'status' => 1,
    'name'   => 'test',
    'type'   => [1, 2, 3], // 会自动转为 in 查询
]);
```

### 5.2 使用表达式查询

通过定义的表达式、或者操作符查询

1. 表达式定义方式：`['查询字段:表达式' => '查询值']`
2. 操作符定义方式：`['查询字段:操作符' => '查询值']`

```php
// 使用表达式
$user = $this->repositpory->findAll([
    'parent_id:eq'       => 0,         // =
    'status:in'          => [1, 2, 3], // in
    'id:gt'              => 100,       // >
    'age:lt'             => 35,        // <
    'created_at:between' => [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')],
]);

// 使用操作符
$users = $this->repositpory->findAll([
    'status:in'          => [1, 2, 3], // in
    'id:>='              => 100,       // >
    'age:<='             => 35,        // <
    'created_at:between' => [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')],
]);
```

#### 目前支持的表达式：

| 表达式      | 含义                      | 特别说明                                          |
| :---------- | :------------------------ | :------------------------------------------------ |
| eq          | 等于(=)                   |                                                   |
| neq         | 不等于(!=)                |                                                   |
| ne          | 不等于(!=)                |                                                   |
| gt          | 大于(>)                   |                                                   |
| egt         | 大于等于(>=)              |                                                   |
| gte         | 大于等于(>=)              |                                                   |
| ge          | 大于等于(>=)              |                                                   |
| lt          | 小于(<)                   |                                                   |
| le          | 小于等于(<=)              |                                                   |
| lte         | 小于等于(<=)              |                                                   |
| elt         | 小于等于(<=)              |                                                   |
| in          | IN 查询                   | 传入数据会强转为数组                              |
| not in      | NOT IN 查询               | 传入数据会强转为数组                              |
| not_in      | NOT IN 查询               | 传入数据会强转为数组                              |
| between     | 区间查询(between)         | 传入数据会强转为数组                              |
| not_between | 不在区间查询(not between) | 传入数据会强转为数组                              |
| not between | 不在区间查询(not between) | 传入数据会强转为数组                              |
| like        | 模糊查询包含(like)        | 会自动判断添加 % 模糊查询；传入数据会强转为字符串 |
| not_like    | 模糊查询不包含(not like)  | 会自动判断添加 % 模糊查询；传入数据会强转为字符串 |
| not like    | 模糊查询不包含(not like)  | 会自动判断添加 % 模糊查询；传入数据会强转为字符串 |
| rlike       | 模糊查询包含(rlike)       |                                                   |
| <>          | 不等于(<>)                |                                                   |

#### 关于 `like`, `not_like` 查询说明

```php
// 没有添加前后模糊查询，会自动加上 username like '%test%'
$this->repository->findAll(['username:like' => 'test']); 

// 添加了前缀或者后缀模糊查询，那么不处理 username like 'test%'
$this->repository->findAll(['username:like' => 'test%']);

// 如果上述like的查询不满足你的需求，可以使用原生SQL查询
$this->repository->findAll(['username' => DB::raw("like 'username'")]);
```
### 5.3 使用预定义字段查询

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

### 5.4 为关联添加查询条件
### 5.5 使用关联关系join查询

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

##### 给join查询添加查询条件

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

##### 给join表名别名

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

### 5.6 为关联查询添加条件

**切记关联查询不是join查询** 关联查询是主表查询完成后，通过定义的关联然后再去查询关联表，是执行了两条SQL

定义方式： `['rel.关联方法名称.关联表字段' => '查询的值']`

Model 使用 5.5 定义的model

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

### 5.7 使用join查询

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
### 5.8 使用model定义scope查询

需要 model 定义了 scope 查询方法

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

定义方式：`['去掉scope方法名称' => '需要的参数']`

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

### 5.9 使用原生SQL查询

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