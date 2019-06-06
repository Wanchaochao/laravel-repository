Repository 使用说明
==================

[change to English](./Repository.md)

`Repository` 是对 `laravel model` 的一个补充，优化了`laravel model` 的 `CURD` 操作，
并提供更多的方法，以及更友好的编辑器提示

## 一 增删改查

### 1.1 新增数据

```php
/**
 * 返回值说明
 * @param boolean $ok   true 表示成功
 * @param string  $msg  操作的提示信息
 * @param array   $data 新增成功后调用 model->toArray() 返回的数据， 失败为null 
 */
list($ok, $msg, $data) = $this-repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address    => 'America'
]);

```

### 1.2 删除数据

```php
/**
 * 返回值说明
 * @param boolean $ok   true 表示成功
 * @param string  $msg  操作的提示信息
 * @param int     $rows 表示删除数据条数
 */
list($ok, $msg, $rows) = $this->repository->delete(1); // 主键删除 pk = 1

$this->repository->delete(['id:gt' => 10]);  // 条件删除 id > 10

$this->>repository->delete([1, 2, 3, 4, 5]); // 主键删除 pk in (1, 2, 3, 4)
``` 

### 1.3 编辑数据

```php
/**
 * 返回值说明
 * @param boolean $ok   true 表示成功
 * @param string  $msg  操作的提示信息
 * @param int     $rows 表示修改数据条数
 */
list($ok, $msg, $rows) = $this->repository->update(['name:like' => '%555'], [
    'type' => 3,
    'money' => 9999
]);

```

### 1.4 查询数据

#### 查询单条数据

1. 查询单条数据 find($conditions, $fields)

    ```php
    $item = $this->repository->find(1);  // 主键查询 pk = 1
    
    ```

2. 查询单个字段 findBy($conditions, $field)

    ```php
    $name = $this->repository->findBy(1, 'name'); // 查某个字段
    ```

#### 查询多条数据

1. 查询多条数据 findAll($conditions, $fields)

    ```php
    $items = $this->repository->findAll([1, 2, 3, 4]); // 主键查询 pk in (1, 2, 3, 4)
    ```

2. 查询多条数据的单个字段 findAllBy($conditions, $filed)

    ```php
    $usernames = $this->repository->findAllBy([1, 2, 3], 'username'); // 查询某个字段的所有值
    ```

#### 分页查询

分页查询 paginate($conditions = [], $fields = [], $pageSize = 10, $currentPage = null)

```php
$list = $this->repository->paginate(['status' => 1], ['id', 'name', ...]);
```

#### 使用表达式查询数据

> 下面列出查询方法，均支持表达式查询

1. find
2. findBy
3. findAll
4. findAllBy
5. paginate
6. update
7. delete
8. filterFind
9. filterFindAll
10. getFilterModel
11. findCondition

> 使用方式

字段:表达式 => 对应查询的值

```php

$items = $this->repository->findAll([
    'id:neq'    => 1,
    'name:like' => '%test%'
]);

// 对应生成的sql: `id` != 1 and `name` like '%test%' 
```

#####  目前支持的表达式

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

#### 关于auto_like 查询说明

```php
// 没有添加前后模糊查询，会自动加上 username like '%test%'
$this->repository->findAll(['username:auto_like' => 'test']); 

// 添加了前缀或者后缀模糊查询，那么不处理 username like 'test%'
$this->repository->findAll(['username:auto_like' => 'test%']);

```
#### 你可以像下面这样使用表达式:

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
        '2020-01-01 00:00:00
    ]
]);

// 封停以@@@结尾的账号
$this->repository->update(['name:like' => '%@@@'], ['status' => 0]);

``` 

#### 如果你记不住表达式，那么你同样可以直接使用操作符查询也是一样的

```php
$item = $this->repository->findAll([
    'id:!='         => 2,
    'username:like' => '%test%',
    'status:>='     => 4,
])
```

同样是 查询字段:操作符 => '查询的值'

#### 进阶用法

```php
# Example 1:
# 举个🌰栗子,你有一张用户表users,用户表的扩展信息存在user_ext里 
# 也许你想查询用户信息的时候同时查出用户的扩展信息

# step 1.在模型Users.php中定义模型关系

/**
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function extInfo()
{
    # if foreignKey == localKey, you could only write the first user_id 
    # that`s enough
    return $this->hasOne(UsersExt::class, 'user_id');
}

# step 2.这样使用
$this->userRepository->findAll(
    ['status' => 1],  // filters
    [
        '*', // users columns
        'extInfo' => [
            // user_ext columns
            'address', 
            'sex', 
            'hobby', 
            'phone'
        ] 
    ]// fields
);
```

```php
# Example 2:
# 还是用户表和用户扩展表
# 也许你想找到id 大于10的用户并且用户的地址是NewYork

# step 1.
# 在users模型中定义scope

/**
 * @param $query
 * @param $address
 * @return mixed
 */
public function scopeAddress($query, $address)
{
    return $query->leftJoin('user_ext', function ($join) use ($address) {
        $join->on('user_ext.user_id', '=', 'users.user_id');
    })->where('user_ext.address', '=', $address);
}

# step 2.
# 像下面这样使用

$users = $this->userRepository->findAll(
    ['user_id:gt' => 10, 'address' => 'NewYork']
);

```

#### 过滤空值查询

**空字符串、空数组、null会被认为空值**

1. 查询单个 filterFind($conditions, $fields = [])

    ```php
    $item = $this->repositpry->filterFind([
        'username:like' => request()->input('username'),
        'status'        => request()->input('status')
    ]);
    ```

2. 查询多个 filterFindAll($conditions, $fields = [])

    ```php
    $items = $this->repositpry->filterFindAll([
        'username:like' => request()->input('username'),
        'status'        => request()->input('status')
    ]);
    ```
3. 获取过滤空值查询的model getFilterModel($conditions, $fields = [])

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

### 1.5 其他比较常用方法

#### 通过处理表达式查询、自动关联查询 findCondition() 之后的其他查询

这些方法都是通过 $this->findCondition($conditions) 之后直接调用 model 的方法

```php
    /**
     * 调用 model 的方法
     *
     * @param string $name 调用model 自己的方法
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // 直接使用 model, 不需要查询条件的数据
        if (in_array($name, $this->passThru)) {
            return (new $this->model)->{$name}(...$arguments);
        }

        // 第一个参数传递给自己 findCondition 方法
        $conditions = Arr::pull($arguments, 0, []);
        return $this->findCondition($conditions)->{$name}(...$arguments);
    }
```

##### 查询方法

1. first($conditions, $columns = []) 
2. get($conditions, $columns = [])
3. pluck($conditions, $column, $key = null)

##### 统计、聚合查询

1. count($conditions)
2. max($conditions, $column)
3. min($conditions, $column)
4. sum($conditions, $column)
5. avg($conditions, $column)
6. toSql($conditions)
7. getBindings($conditions)

#### 其他方法

1. getConnection()
2. insert(array $insert)
3. insertGetId(array $insert)
4. firstOrCreate(array $attributes, array $value = [])
5. firstOrNew(array $attributes, array $value = [])
6. updateOrCreate(array $attributes, array $value = [])
7. findOrFail($id, $columns = ['*'])
8. findOrNew($id, $columns = ['*'])
9. findMany($ids, $columns = ['*'])

是不是非常简洁方便 ^_^ 😋
后面会继续补充

## 二 方法列表

### 2.1 方法列表

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
|`update($conditions, $data)`|`array`|修改数据(使用的是批量修改)|
|`delete($conditions)`|`array`|删除数据(使用的是批量删除)|

### 2.2 支持`model`自带方法

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

### 2.3 通过`findCondition($conditions)`查询后转换为`model`查询方法

|方法名称|返回值|方法说明|
|---------------|-------------|----------|
|`first($conditions, $columns = ['*'])`|`Illuminate\Database\Eloquent\Model or null`| 查询一条数据|
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
