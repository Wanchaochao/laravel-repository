### Repository 使用说明

---

#### 增删改查

```php
# 增
$this-repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address    => 'America'
]);
```

```php
# 删
$this->repository->delete(1); //pk = 1
$this->repository->delete(['id:gt' => 10]); // delete id > 10
``` 

```php
# 改
$this->repository->update(['name:like' => '%555'], [
    'type' => 3,
    'money' => 9999
]);
```

#### 关于过滤条件的表达式
```php

# Repository.php

protected $expression = [
    // 下面的表达式只需要传入字符串或者数字
    'eq'          => '=',
    'neq'         => '!=',
    'ne'          => '!=',
    'gt'          => '>',
    'egt'         => '>=',
    'gte'         => '>=',
    'ge'          => '>=',
    'lt'          => '<',
    'le'          => '<=',
    'lte'         => '<=',
    'elt'         => '<=',
    'like'        => 'LIKE',
    'not_like'    => 'NOT LIKE',
    'not like'    => 'NOT LIKE',
    
    // 下面的这些表达式需要传入数组
    'in'          => 'In',
    'not_in'      => 'NotIn',
    'not in'      => 'NotIn',
    'between'     => 'Between',
    'not_between' => 'NotBetween',
    'not between' => 'NotBetween',
];

// 你可以像下面这样使用表达式:

# 查询大于10的账号
$this->repository->findAll(['id:gt' => 10]);

# 查询不等于10的账号
$this->repository->findAll(['id:neq' => 10]);

# 查询id是1,2,3,4,5的这些数据
$this->repository->findAll(['id:in' => [1, 2, 3, 4, 5]);
// or
$this->repository->findAll([1, 2, 3, 4, 5])

# 查询创建时间在2019年的数据
$this->repository->findAll(['created_at:between' => 
    [
        '2019-01-01 00:00:00', 
        '2020-01-01 00:00:00
    ]
]);
// 封停以@@@结尾的账号
$this->repository->update(['name:like' => '%@@@'], ['status' => 0]);

``` 


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
