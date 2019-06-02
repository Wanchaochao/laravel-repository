Repository 使用说明
==================

## 增删改查

### 新增数据

```php
/**
 * 返回值说明
 * @param boolean $ok   true 表示成功
 * @param string  $msg  操作的提示信息
 * @param array   $data 新增后台调用 model->toArray() 返回的数据， 失败为null 
 */
list($ok, $msg, $data) = $this-repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address    => 'America'
]);


```

### 删除数据

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

### 编辑数据

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

### 查询数据

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
$list = $this->>repository->paginate(['status' => 1], ['id', 'name', ...]);
```

#### 使用表达式查询数据

> 下面列出查询方法，都支持表达式查询

1. find
2. findBy
3. findAll
4. findAllBy
5. paginate

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
| elt    | 小于等于(<=)  | |
| in     | IN 查询      | 传入数据会强转为数组| 
| not in | NOT IN 查询  | 传入数据会强转为数组| 
| not_in | NOT IN 查询  | 传入数据会强转为数组| 
| between| 区间查询(between)  | 传入数据会强转为数组| 
| not_between| 不在区间查询(between)  | 传入数据会强转为数组| 
| not between| 不在区间查询(between)  | 传入数据会强转为数组| 
| like   | 模糊查询包含(like)  | 传入数据会强转为字符串 | 
| not_like   | 模糊查询不包含(like)  | 传入数据会强转为字符串 | 
| not like   | 模糊查询不包含(like)  | 传入数据会强转为字符串 | 
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
是不是非常简洁方便 ^_^ 😋
后面会继续补充
