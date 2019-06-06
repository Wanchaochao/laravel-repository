Repository 使用说明
==================

[change to English](./Repository.md)

`Repository` 是对 `laravel model` 的一个补充，优化了`laravel model` 的 `CURD` 操作，
并提供更多的方法，以及更友好的编辑器提示

## 一 增删改查

### 1.1 新增数据 `create(array $data)`

```php
/**
 * 返回值说明
 * @param boolean $ok   true 表示成功
 * @param string  $msg  操作的提示信息
 * @param array   $data 新增成功后调用 model->toArray() 返回的数据， 失败为null 
 */
list($ok, $msg, $data) = $this->repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address    => 'America'
]);

```

### 1.2 编辑数据 `update($conditions, array $data)`

```php
/**
 * 返回值说明
 * @param boolean $ok   true 表示成功
 * @param string  $msg  操作的提示信息
 * @param int     $rows 表示修改数据条数
 */
list($ok, $msg, $rows) = $this->repository->update(1, ['type' => 3, 'money' => 9999]); // 主键修改 pk = 1

// $this->repository->update(['id:gt' => 10], ['type' => 3, 'money' => 9999]);  // 条件修改 id > 10

// $this->repository->update([1, 2, 3, 4], ['type' => 3, 'money' => 9999]); // 主键修改 pk in (1, 2, 3, 4)
```

### 1.3 删除数据 `delete($conditions)`

```php
/**
 * 返回值说明
 * @param boolean $ok   true 表示成功
 * @param string  $msg  操作的提示信息
 * @param int     $rows 表示删除数据条数
 */
list($ok, $msg, $rows) = $this->repository->delete(1); // 主键删除 pk = 1

// $this->repository->delete(['id:gt' => 10]);  // 条件删除 id > 10

// $this->>repository->delete([1, 2, 3, 4, 5]); // 主键删除 pk in (1, 2, 3, 4)
``` 

### 1.4 查询数据

#### 1.4.1 查询单条数据

1. 查询单条数据 find($conditions, $columns = [])

    ```php
    $item = $this->repository->find(1);  // 主键查询 pk = 1
    ```

2. 查询单个字段 findBy($conditions, $column)

    ```php
    $name = $this->repository->findBy(1, 'name'); // 查某个字段
    ```

#### 1.4.2 查询多条数据

1. 查询多条数据 findAll($conditions, $columns = [])

    ```php
    $items = $this->repository->findAll([1, 2, 3, 4]); // 主键查询 pk in (1, 2, 3, 4)
    ```

2. 查询多条数据的单个字段 findAllBy($conditions, $column)

    ```php
    $usernames = $this->repository->findAllBy([1, 2, 3], 'username'); // 查询某个字段的所有值
    ```

#### 1.4.3 分页查询

分页查询 paginate($conditions = [], $columns = [], $size = 10, $current = null)

```php
$list = $this->repository->paginate(['status' => 1], ['id', 'name', ...]);
```

### 1.5 查询进阶使用

#### 1.5.1 使用表达式查询

> 使用方式

`字段`:`表达式` => `对应查询的值`

例如:

```php

$items = $this->repository->findAll([
    'id:neq'    => 1,
    'name:like' => '%test%'
]);

// 对应生成的sql: `id` != 1 and `name` like '%test%' 
```

##### 1.5.1.1  目前支持的表达式

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
| like   | 模糊查询包含(like)  | 传入数据会强转为字符串 | 
| not_like   | 模糊查询不包含(not like)  | 传入数据会强转为字符串 | 
| not like   | 模糊查询不包含(not like)  | 传入数据会强转为字符串 | 
| rlike      | 模糊查询包含(rlike)   |  | 
| <>         | 不等于(<>)            |  | 
| auto_like  | 模糊查询(like)        | 会自动判断添加 % 模糊查询

##### 1.5.1.2 关于 `auto_like` 查询说明

```php
// 没有添加前后模糊查询，会自动加上 username like '%test%'
$this->repository->findAll(['username:auto_like' => 'test']); 

// 添加了前缀或者后缀模糊查询，那么不处理 username like 'test%'
$this->repository->findAll(['username:auto_like' => 'test%']);

```

##### 1.5.1.3 你可以像下面这样使用表达式:

```php
// 查询大于10的账号
$this->repository->findAll(['id:gt' => 10]);

// 查询不等于10的账号
$this->repository->findAll(['id:neq' => 10]);

// 查询id是1,2,3,4,5的这些数据
$this->repository->findAll(['id:in' => [1, 2, 3, 4, 5]);
// or
$this->repository->findAll(['id' => [1, 2, 3, 4, 5]])

// 查询创建时间在2019年的数据
$this->repository->findAll(['created_at:between' => 
    [
        '2019-01-01 00:00:00', 
        '2020-01-01 00:00:00',
    ]
]);
``` 

##### 1.5.1.4 如果你记不住表达式,那么你同样可以直接使用操作符查询也是一样的

```php
$item = $this->repository->findAll([
    'id:!='         => 2,
    'username:like' => '%test%',
    'status:>='     => 4,
])
```

**同样是 查询字段:操作符 => '查询的值'**

##### 1.5.1.5 其他说明

`update` 和 `create` 方法同样支持表达式查询，都是使用`findCondition($condiitons)` 方法处理

1. [update 的使用说明](./#12-编辑数据-updateconditions-array-data)
2. [delete 的使用说明](./#13-删除数据-deleteconditions)

#### 1.5.2 使用 `model` 的 `scope` 查询

>举个🌰栗子,你有一张用户表 users, 用户表的扩展信息存在 user_ext 里;
现在你想查询用户地址在指定位置信息的所有用户信息, 那么就需要使用`scope` 查询了

要求model定义了`scope`查询

1. model 

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

2. 使用 `repository` 查询

    ```php
    
    $this->userRepositoy->findAll([
        'status'  => 1,
        'address' => '北京'
    ]);
    
    ```

3. 查询的SQL

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
##### 1.5.2.1 使用说明

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

`model` 使用上面定义的 [`User`](./#152-使用-model-的-scope-查询)

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

    ![关联的数据](./relation.png '关联的数据')

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
   
   ![关联的数据](./relation-1.png '关联的数据')
   
   >这可能会让人认为我明明只查询了`username`字段，怎么还查出了其他字段信息
   
   **只有在关联查询的时候，没有指定查询关联字段，才会自动加上关联字段**
   
#### 1.5.5 获取 `model` 的 `relation` 关联统计数据信息

这个功能比较适合一对多的时候，我想知道关联的其他信息有多少

只要定义了`model`的关联信息，就可以直接使用了，其实就是 `model` 的 `withCount`

>`model`定义的`关联方法名称_count`

`model` 使用上面定义的 [`User`](./#152-使用-model-的-scope-查询)

```php
$user = $this->repositoy->find(['status' => 1], ['id', 'username', 'extInfo_count']);
```

执行SQL以及数据

![关联的数据](./relation-2.png '关联的数据')

#### 1.5.6 给 `model` 的 `relation` 关联查询动态添加查询条件

查询条件中添加 `model定义关联方法名称.字段` => '查询的值'

`model` 使用上面定义的 [`User`](./#152-使用-model-的-scope-查询)

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

## 三 方法列表

### 3.1 方法列表

>repository所有方法都是对外的，这里只列出常用方法

|方法名称|返回值|方法说明|
|-------|-----|-------|
|`find($conditions, $columns = [])`|`array or false`|查询一条数据|
|`findBy($conditions, $column)`|`mixed`|查询单条数据的单个字段|
|`findAll($conditions, $columns = [])`|`array`|查询多条数据|
|`findAllBy($conditions, $column)`|`array`|查询多条数组的单个字段数组|
|`filterFind($conditions, $columns = [])`|`array or false`|过滤查询条件中的空值查询一条数据|
|`filterFindAll($condtions, $columns = [])`|`array`|过滤查询条件中的空值查询多条数据|
|`paginate($conditions = [], $columns = [], $size = 10, $current = null)`|`array`|分页查询数据|
|`getFilterModel($conditions, $columns = [])`|`Illuminate\Database\Eloquent\Model`|获取已经过滤处理查询条件的`model`|
|`findCondition($conditions = [], $columns = [])`|`Illuminate\Database\Eloquent\Model`|获取已经处理查询条件的`model`(**上面所有查询方法都基于这个方法**)|
|`create(array $data)`|`array`|添加数据|
|`update($conditions, array $data)`|`array`|修改数据(使用的是批量修改)|
|`delete($conditions)`|`array`|删除数据(使用的是批量删除)|

#### 参数说明

|参数名称    |参数类型| 参数说明 |
|---------------|-------------|----------|
|`$conditions`|`array or string or int`|查询条件(`string or int or 索引数组[1, 2, 3, 4]`会自动转换为主键查询)|
|`$columns`|`array`|查询的字段数组|
|`$column`|`string`|查询的字段名称|
|`$data`|`array`|创建或者修改的数组数据信息|

### 3.2 支持`model`自带方法

|方法名称    |返回值| 方法说明 |
|---------------|-------------|----------|
|`getConnection()`|`Illuminate\Database\Connection`|获取连接信息|
|`insert(array $values)`|`boolean`|新增数据(支持批量新增)|
|`insertGetId(array $values)`|`int`|新增数据并获取新增ID|
|`firstOrCreate(array $attributes, array $value = [])`|`Illuminate\Database\Eloquent\Model`|查询数据，不存在那么新增一条数据|
|`firstOrNew(array $attributes, array $value = [])`|`Illuminate\Database\Eloquent\Model`|查询数据、不存在那么`new`出来|
|`updateOrCreate(array $attributes, array $value = [])`|`Illuminate\Database\Eloquent\Model`|修改数据，不存在那么新增一条数据|
|`findOrFail($id, $columns = ['*'])`|`Illuminate\Database\Eloquent\Model`|通过主键查询数据，不存在抛出错误|
|`findOrNew($id, $columns = ['*'])` |`Illuminate\Database\Eloquent\Model`|通过主键查询数据，不存在`new`出来|
|`findMany($ids, $columns = ['*'])`|`\Illuminate\Database\Eloquent\Collection`|通过主键数组查询多条数据|

#### 参数说明

|参数名称    |参数类型| 参数说明 |
|---------------|-------------|----------|
|`$attributes`|`array`|`model`的字段信息(查询条件)|
|`$value`|`array or null`|`model`的其他字段信息(不参与查询、参与新增和`new`)
|`$values`|`array`|新增数据需要的字段 => 值 数组信息
|`$id`|`int or string`|主键ID值|
|`$ids`|`array`|主键ID数组|
|`$columns`|`array`|查询的字段信息|

### 3.3 通过`findCondition($conditions)`查询后转换为`model`查询方法

|方法名称|返回值|方法说明|
|---------------|-------------|----------|
|`first($conditions, $columns = ['*'])`|`Illuminate\Database\Eloquent\Model or null`|查询一条数据|
|`get($conditions, $columns = ['*'])`|`Illuminate\Database\Eloquent\Collection`|查询多条数据|
|`pluck($conditions, $column, $key = null)`|`Illuminate\Support\Collection`|查询单个字段信息|
|`firstOrFail($conditions)`|`Illuminate\Database\Eloquent\Model`|查询一条数据、没有那么抛出错误|
|`count($conditions = [])`|`int`|统计查询|
|`max($conditions, $column)`|`int or mixed`|最大值查询|
|`min($conditions, $column)`|`int or mixed`|最小值查询|
|`avg($conditions, $column)`|`int or mixed`|平均值查询|
|`sum($conditions, $column)`|`int or mixed`|求和查询|
|`toSql($conditions)`|`string`|获取执行的`SQL`|
|`getBindings($conditions = [])`|`array or mixed`|获取查询绑定的参数|

#### 参数说明

|参数名称    |参数类型| 参数说明 |
|---------------|-------------|----------|
|`$conditions`|`array or string or int`|查询条件(`string or int or 索引数组[1, 2, 3, 4]`会自动转换为主键查询)|
|`$columns`|`array`|查询的字段数组|
|`$column`|`string`|查询的字段名称|
|`$key`|`string or null`|查询单个字段组成数组的`key`(索引下标使用字段)|

是不是非常简洁方便 ^_^ 😋
后面会继续补充