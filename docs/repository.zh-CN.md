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

#### filterFind($conditions, $columns = [])
#### filterFindBy($conditions, $column)
#### filterFindAll($conditions, $columns = [])
#### filterFindAllBy($conditions, $column)

## 五、查询条件说明

对于查询条件 `$conditions` 说明

### 5.1 简单主键、数组查询
### 5.2 使用表达式查询

#### 目前支持的表达式：

| 表达式 | 含义 | 特别说明 |
|:------|:--------------|:-----|
| eq    | 等于(=)      | |
| neq   | 不等于(!=)   | |
| ne    | 不等于(!=)   | |
| gt    | 大于(>)      | |
| egt    | 大于等于(>=) | |
| gte    | 大于等于(>=) | |
| ge     | 大于等于(>=) | |
| lt     | 小于(<)      | |
| le     | 小于等于(<=)  | |
| lte    | 小于等于(<=)  | |
| elt    | 小于等于(<=)  | |
| in     | IN 查询      | 传入数据会强转为数组| 
| not in | NOT IN 查询  | 传入数据会强转为数组| 
| not_in | NOT IN 查询  | 传入数据会强转为数组| 
| between| 区间查询(between)  | 传入数据会强转为数组| 
| not_between| 不在区间查询(not between)  | 传入数据会强转为数组| 
| not between| 不在区间查询(not between)  | 传入数据会强转为数组| 
| like   | 模糊查询包含(like)  | 会自动判断添加 % 模糊查询；传入数据会强转为字符串 | 
| not_like   | 模糊查询不包含(not like)  | 会自动判断添加 % 模糊查询；传入数据会强转为字符串 | 
| not like   | 模糊查询不包含(not like)  | 会自动判断添加 % 模糊查询；传入数据会强转为字符串 | 
| rlike      | 模糊查询包含(rlike)   |  | 
| <>         | 不等于(<>)            |  | 

#### 关于 `like`, `not_like` 查询说明

```php
// 没有添加前后模糊查询，会自动加上 username like '%test%'
$this->repository->findAll(['username:like' => 'test']); 

// 添加了前缀或者后缀模糊查询，那么不处理 username like 'test%'
$this->repository->findAll(['username:like' => 'test%']);
```
### 5.3 使用预定义字段查询
### 5.4 为关联添加查询条件
### 5.5 使用关联关系join查询
### 5.6 使用join查询
### 5.7 使用model定义scope查询
### 5.8 使用原生SQL查询
### 5.9 总结

## 六、查询字段说明

### 6.1 查询本表字段
### 6.2 查询关联表字段
### 6.3 查询关联表统计
### 6.4 查询join表字段
### 6.5 查询原生SQL字段
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

#### 1.5.2 使用 `model` 的 `scope` 查询

>举个🌰栗子,你有一张用户表 users, 用户表的扩展信息存在 user_ext 里;
现在你想查询用户地址在指定位置信息的所有用户信息, 那么就需要使用`scope` 查询了

要求model定义了`scope`查询

##### 1. model 

```php

class User extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'user_id';
    public    $columns    = [
        'user_id',
        'username',
        //...
        'created_at',
        'updated_at',
    ];
 
  
    /**
     * 定义关联扩展信息
     * 
     * return Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function extInfo()
    {
      return $this->hasOne(UserExt::class, 'user_id', 'user_id');
    }
 

    /**
     * 根据地址查询
     *
     * @param \Illuminate\Database\Eloquent\Builder $query   查询对象
     * @param string                                $address 地址信息
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAddress($query, $address)
    {
        return $query->leftJoin('user_ext', 'user_ext.user_id', '=', 'users.user_id')
            ->where('user_ext.address', '=', $address);
    }
}   

```

##### 2. 使用 `repository` 查询

```php

$this->userRepositoy->findAll([
    'status'  => 1,
    'address' => '北京'
]);

```

##### 3. 查询的SQL

```sql

select 
    * 
from 
    `users` 
left join 
    `user_ext` on (`user_ext`.`user_id` = `users`.`user_id`) 
where 
    `users.status` = 1 and `user_ext`.`address` = '北京'
    
```
##### 4 使用说明

从上面的SQL和容易发现一个问题，没有指定查询字段，默认查询的 `*` 所有字段，如果`users`表和`user_ext`
表的字段没有冲突，那么没有什么问题，但如果有冲突，那么查询出来的数据可能和你想象的不一样，特别在还有
关联查询的时候，这个问题更容易凸显出来，所以建议在有连表查询的时候，最好有加上你需要查询的字段信息

```php
$this->userRepository->findAll([
    'status'  => 1,
    'address' => '北京',
], ['users.*'])
```

#### 1.5.4 获取 `model` 的 `relation` 关联数据信息

当我们查询数据时候，也想把关联数据查询出来的时候，就会用到关联查询

使用的是`model`的`with`方法

>举个🌰栗子,你有一张用户表users,用户表的扩展信息存在user_ext里 
也许你想查询用户信息的时候同时查出用户的扩展信息

要求`model`定义了关联

`model` 使用上面定义的 [`User`](#152-使用-model-的-scope-查询)

1. 使用 `repository` 获取关联数据信息, 通过查询字段，自动处理关联

    查询字段中添加 `关联关系` => [`关联查询的字段信息`]
    
    ```php
        $users = $this->repository->findAll(['status' => 1], ['*', 'extInfo' => ['*']]);
    ```

2. 查询SQL 

    [这里使用预加载数据](https://learnku.com/docs/laravel/5.5/eloquent-relationships/1333#012e7e), 避免N+1问题

    ```sql
    select * from `users` where `users`.`status` = 1
    
    select * from `user_ext` where `user_id` in (1, 2, 3, 4)
    ```

3. 数据信息

    ![关联的数据](https://wanchaochao.github.io/laravel-repository/docs/images/relation.png '关联的数据')

##### 1.5.4.1
    
1. 上面有个小的问题，`model`定义的关联名称为`extInfo`, 但是出来数组对应的字段信息为
 `ext_info` , 并且查询指定字段信息也是为`extInfo` (`'extInfo' => ['*']`), 查询
 出来的数据是`laravel` `model` 的 `toArray()` 方法处理的结果,会将`小驼峰`命名的
 关联信息转为`蛇形`命名字段，`repository`查询字段支持`小驼峰`和`蛇形`命名，例如：
     
     ```php
        $users = $this->repository->findAll(['status' => 1], ['*', 'ext_info' => ['*]])
     ``` 
       
     和上面的结果是一致的，为了更好的一致性，建议`model`在定义联查询命名的时候，使用单个单词的单复数形式比较好

2. 在查询时候指定字段，并且指定查询关联查询字段

    ```php
    $users = $this->userRepository->findAll(['status' => 1], ['username', 'extInfo' => ['address']]);
    ```    
   
   上面查询指定了查询的字段，但有一个问题，没有指定出关联表查询需要的字段 `user_id` 字段信息，会导致关联信息关联不上的问题
   **但`repository`解决了这个问题，会自动加上关联查询需要的字段信息**，所以最终查询的SQL和数据如下:
   
   ![关联的数据](https://wanchaochao.github.io/laravel-repository/docs/images/relation-1.png '关联的数据')
   
   >这可能会让人认为我明明只查询了`username`字段，怎么还查出了其他字段信息
   
   **只有在关联查询的时候，没有指定查询关联字段，才会自动加上关联字段**
   
#### 1.5.5 获取 `model` 的 `relation` 关联统计数据信息

这个功能比较适合一对多的时候，我想知道关联的其他信息有多少

只要定义了`model`的关联信息，就可以直接使用了，其实就是 `model` 的 `withCount`

>`model`定义的`关联方法名称_count`

`model` 使用上面定义的 [`User`](#152-使用-model-的-scope-查询)

```php
$user = $this->repositoy->find(['status' => 1], ['id', 'username', 'extInfo_count']);
```

执行SQL以及数据

![关联的数据](https://wanchaochao.github.io/laravel-repository/docs/images/relation-2.png '关联的数据')

#### 1.5.6 给 `model` 的 `relation` 关联查询动态添加查询条件

查询条件中添加 `model定义关联方法名称.字段` => '查询的值'

`model` 使用上面定义的 [`User`](#152-使用-model-的-scope-查询)

例如：

```php

$users = $this->repository->findAll([
    'status'                => 1,
    'extInfo.address'       => '北京',
    'extInfo.created_at:gt' => '2019-02-01 00:00:00', // 同样支持表达式查询
], ['extInfo' => ['*']])

```
执行的SQL：
```sql
select * from `users` where `users`.`status` = 1

select * from `user_ext` where 
    `user_ext`.`address` = '北京' and 
    `user_ext`.`created_at` > '2019-02-01 00:00:00' and 
    `user_ext`.`user_id` in (1, 2, 3, 4)
```

##### 1.5.6.1 model 也可以定义默认关联的查询条件

需要在`model`里面定义 `关联方法名称Filters` 的属性信息

```php
    class User extends Model
    {
        protected $table      = 'users';
        protected $primaryKey = 'user_id';
        public    $columns    = [
            'user_id',
            'username',
            //...
            'created_at',
            'updated_at',
        ];
        
        /**
         * 为关联定义默认查询条件
         *
         * @var array
         */
        public $extInfoFilters = [
            'address' => '北京', 
            // 'ddress:like' => '北京', // 允许使用表达式方式
        ];
     
        /**
         * 定义关联扩展信息
         * 
         * return Illuminate\Database\Eloquent\Relations\HasOne
         */
        public function extInfo()
        {
          return $this->hasOne(UserExt::class, 'user_id', 'user_id');
        }
     
    
        /**
         * 根据地址查询
         *
         * @param \Illuminate\Database\Eloquent\Builder $query   查询对象
         * @param string                                $address 地址信息
         * @return \Illuminate\Database\Eloquent\Builder
         */
        public function scopeAddress($query, $address)
        {
            return $query->leftJoin('user_ext', 'user_ext.user_id', '=', 'users.user_id')
                ->where('user_ext.address', '=', $address);
        }
    }   
```
  
说明： 关联查询、统计查询都会添加默认关联查询条件

使用定义了默认关联查询条件进行查询

```php
$users = $this->repository->findAll(['status' => 1], ['extInfo' => ['*']])
```

执行的SQL：

```sql
select * from `users` where `users`.`status` = 1

select * from `user_ext` where 
    `user_ext`.`address` = '北京' 
    `user_ext`.`user_id` in (1, 2, 3, 4)
```

**默认关联查询条件和动态关联查询条件是叠加的**

#### 1.5.7 过滤空值查询

**空字符串、空数组、null会被认为空值**

1. 查询单个 filterFind($conditions, $columns = [])

    ```php
    $item = $this->repositpry->filterFind([
        'username:like' => request()->input('username'),
        'status'        => request()->input('status')
    ]);
    ```

2. 查询多个 filterFindAll($conditions, $columns = [])

    ```php
    $items = $this->repositpry->filterFindAll([
        'username:like' => request()->input('username'),
        'status'        => request()->input('status')
    ]);
    ```
3. 获取过滤空值查询的model getFilterModel($conditions, $columns = [])

    ```php
    $model = $this->repositpry->getFilterModel([
        'username:like' => request()->input('username'),
        'status'        => request()->input('status')
    ]);
    ```
    
>这几个方法，相当于 [when 条件查询](https://learnku.com/docs/laravel/5.5/queries/1327#conditional-clauses)
在和前端交互时，不确定前端是否传递值来进行查询时候，比较方便

```php
// 平时写法
$conditions = [];

if ($username = request()->input('username')) {
    $conditions['username:like'] = $username;
}

if ($status = request()->input('status')) {
    $conditions['status'] = $status;
}

$items = $this->repository->findAll($conditions);

// 使用 filter 过滤查询
$items = $this->repositpry->filterFindAll([
    'username:like' => request()->input('username'),
    'status'        => request()->input('status')
]);
```

#### 1.5.8 使用 `findWhere` 构建复杂查询

> `findWhere(array $where, $array $columns = [])`

```php
$this->userRespository->findWhere([
    'and',
    ['username' => 1, 'name:like' => '%test%'],
    ['or', ['level' => 10, 'level:eq' => 5]],
    ['and', ['status' => 1, 'age' => 1]],   
])->get();

```

执行的SQL

```sql
select * from `users` where 
    (`username` = 1 and `name` like '%test%') and 
    (`level` = 10 or `level` = 5) and 
    (`status` = 1 and `age` = 1)
```

`where`查询条件说明

数组第一个元素定义查询条件连接方式(后面数组的查询条件怎么连接`or` 或者 `and`)，如果是`and`连接
可以忽略不写,一定要是多维数组

第一个`and`忽略不写
```php
$where = [
['status' => 1]，
['name' => 2]
];

// where `status` = 1 and `name` = 2 
```

使用 `or` 连接
```php
$where = [
    // or 定义 后面的查询条件 通过 or 连接
    'or',
    ['status' => 1],
    ['age' => 1],
    
    // and 定义数组里面后面的查询条件使用 and 连接
    ['and', ['name', '=', 123], ['username', 'like', 'test']],
];

// where `status` = 1 or `age` = 1 or (`name` = 123 and `username` like 'test')
```

#### 1.5.9 使用 `join` 查询

>`leftJoin` 和 `rightJoin` 和 `join` 使用一致

##### 1. 简单`join`

```php
$this->repository->findAll([
    'join' => ['users', 'users.school_id', '=', 'school.id']
]);

```

```sql
select `school`.* from `school` inner join `users` on (`users`.`school_id` = `school`.`id`)
```

##### 2. 一次多个`join`
```php
$this->repository->findAll([
    'join' => [
        ['users', 'users.school_id', '=', 'school.id'],
        ['school as s1', 's1.parent_id', '=', 'school.id']
    ]
]);

```

```sql
select 
    `school`.* 
from 
    `school` 
inner join `users` on (`users`.`school_id` = `school`.`id`) 
inner join `school` as `s1` on (`s1`.`parent_id` = `school`.`id`)
```

##### 3. 使用关联关系对应join

`model` 使用上面定义的 [`User`](#152-使用-model-的-scope-查询)

```php
$users = $this->repository->findAll(['status' => 1, 'joinWith' => 'extInfo']);
```

```sql
select 
    `users`.* 
from 
    `users` 
inner join `user_ext` on `user_ext`.`user_id` = `users`.`user_id`
where `users`.`status` = 1
```

##### 4. 使用关联关系对应join并设置别名

>['别名' => '关联方法名称']

```php
$users = $this->repository->findAll(['status' => 1, 'joinWith' => ['ext' => 'extInfo']]);
```

```sql
select 
    `users`.* 
from 
    `users` 
inner join `user_ext` as `ext` on `ext`.`user_id` = `users`.`user_id` 
where `users`.`status` = 1
```

##### 5. 为`join`添加查询条件

>通过 ['__join表名称.字段' => '对应的值'], 目前没有比较直观的方式处理、因为 `order.user_id` 方式被关联关系的附加条件占用了

```php
$users = $this->repository->findAll([
    'status'        => 1, 
    'joinWith'      => ['ext' => 'extInfo']，
    '__ext.user_id' => 1,
]);
```

```sql
select 
    `users`.* 
from 
    `users` 
inner join `user_ext` as `ext` on `ext`.`user_id` = `users`.`user_id`
where `users`.`status` = 1 and `ext`.`user_id` = 1
```

## 二 关于查询中的`$conditions`和`$columns`信息说明

>`$conditions`为`sql`查询定义查询条件，`$columns`为`sql`的`select`添加指定的查询字段

### 2.1 `$conditions` 查询条件

1. 支持字段精准查询(表中字段的查询)
2. 预定有字段查询[参考](#211-预定义的字段)
3. 支持表达式查询[参考](#15-查询进阶使用)
4. 支持关联条件查询[参考](#156-给-model-的-relation-关联查询动态添加查询条件)
5. 支持`model`的`scope`[参考](#152-使用-model-的-scope-查询)

例如：

```php
$conditions = [
    // 表中字段精准查询
    'status' => 1,
    'id'     => [1, 2, 3, 4], // 值为数组会自动转为in查询 `id` in (1, 2, 3, 4)
    
    // 预定义字段查询
    'order' => 'id desc' // 指定排序字段和方式
    'limit' => 10,       // 限制查询条件
    'group' => 'id',     // 指定分组条件
    'force' => 'name',   // 指定使用的索引
  
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
    ...
    
    // relation 关联查询,查询条件只对当前relation关联查询限制
    'extInfo.address:like'   => '北京',
    'extInfo.created_at:gte' => '2019-01-01 00:00:00',
    
    // 通过 relation 关联关系，添加join 查询
    'joinWith'     => ['extInfo'],
    // 关联表定义别名 alias, 如果没有别名，关联表和主表同名，使用自定义别名 `t1`, 多个同名以此地址 `t2`、`t3`
    'leftJoinWith' => ['alias' => 'children'], 
    
    // scope 自定义查询
    'address'  => '北京',     // 查找`scopeAddress($query, $address)`方法
    'children' => [1, 2, 3],  // 查找`scopeChildren($query, $childrenIds)`方法
];
```

#### 2.1.1 预定义的字段

|字段名称|字段值类型|说明|
|-------|---------|----|
|`force`|`string`|强制走指定索引|
|`order`|`string or array`|指定排序条件|
|`limit`|`int`|指定查询条数|
|`offset`|`int`|指定跳转位置|
|`group`|`string`|指定分组字段|
|`join`|`array`|查询join的参数，多个二维数组|
|`leftJoin`|`array`|查询`leftJoin`的参数、多个二维数组|
|`rightJoin`|`array`|查询`rightJoin`的参数、多个二维数组|
|`joinWith`|`string or array`| 通过关联关系对应join查询|
|`leftJoinWith`|`string or array`| 通过关联关系对应leftJoin查询|
|`rightJoinWith`|`string or array`| 通过关联关系对应rightJoin查询|

## 四 增删改的事件方法

>子类定义了这些方法，才会执行，如果想阻止主方法执行，并能让主方法返回错误信息，直接抛出错误就可以

### 4.1 新增的事件 在`create($data)` 执行的时候触发

1. `beforeCreate($data)` 新增之前

2. `afterCreate($data, $news)`  新增之后

#### 4.1.1 参数说明

|参数名称    |参数类型| 参数说明 |
|---------------|-------------|----------|
|`$data`|`array`|过滤掉干扰数据(非表中字段的数据)的数组|
|`$news`|`array`|新增成功调用 `model->toArray()` 数组|

### 4.2 修改的事件 在`update($conditions, array $data)` 执行的时候触发

1. `beforeUpdate($conditions, $data)` 修改之前
 
2. `afterUpdate($conditions, $data, $row)` 修改之后

#### 4.2.1 参数说明

|参数名称    |参数类型| 参数说明 |
|---------------|-------------|----------|
|`$conditions`|`array`|处理了主键查询后的查询条件数组|
|`$data`|`array`|过滤掉干扰数据(非表中字段的数据)的数组|
|`$row`|`int`|修改受影响的行数|

### 4.3 删除的事件 在`delete($conditions)` 执行的时候触发

1. `beforeDelete($conditions)` 删除之前

2. `afterDelete($conditions, $row)` 删除之后

#### 4.3.1 参数说明

|参数名称    |参数类型| 参数说明 |
|---------------|-------------|----------|
|`$conditions`|`array`|处理了主键查询后的查询条件数组|
|`$row`       |`int`  |删除受影响的行数|

### 4.4 关于`$conditions` 处理为主键查询

不为空的 字符串、整数、浮点数、索引数组 都会被转为主键查询

```php
// 假设表的主键为id
$conditions = 1;            // 会被转为 ['id' => 1]
$conditions = '1';          // 会被转为 ['id' => '1']
$conditions = [1, 2, 3];    // 会被转为 ['id' => [1, 2, 3, 4]]
```

## 五 其他说明

### 5.1 关于`repository`的`create`、`update`、`delete` 的返回

这三个函数不管处理成功和失败，返回的都是数组信息。因为`php`不能像`golang`那样,
可以多返回，而在我们逻辑中，经常需要知道执行错误了，是什么样的错误信息，所以这里
都是通过数组的方式返回，这样就解决多值返回问题。这也是受`golang`的影响！不过
`laravel`其实更推荐是通过抛出错误方式，去统一管理所有的错误信息。所以如果不喜欢
现在数组的返回方式的话，只需要重写 `success($data, $message === 'ok')` 
和 `error($message, $data = [])` 这两个方法就好了

### 5.2 `repository` 查询 `find`, `findAll` 查询结果都是 `model->toArray()` 的数组，并不是 `model` 对象

### 5.3 对于`model`的要求

1. `create` 和 `update` 都是批量赋值，需要`model`定义批量赋值的白名单`$fillable` 或者 黑名单 `$guarded`
2. 需要定义 `$columns` 字段信息，表示表中都有哪些字段

    ```php
    public $columns = ['id', 'title', 'content', 'created_at', 'updated_at'];
    ```
    
    >虽然这一步是非必须的，但定义了`$columns`会减少一次`SQL`查询的代价