### Usage Instructions for Repository

[TOC]

[切换中文](https://wanchaochao.github.io/laravel-repository/docs/repository.zh-cn.md)

---

#### CURD

```php
# create

/**
 * About the res
 * @param boolean $ok   true: successful, false: failed
 * @param string  $msg  about the message
 * @param array   $data the data, it`s null when failed 
 */
$this-repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address    => 'America'
]);
```

```php
# delete
/**
 * About the res
 * @param boolean $ok   true: successful, false: failed
 * @param string  $msg  the message of res
 * @param int     $rows the number of data were deleted
 */
$this->repository->delete(1); //pk = 1
$this->repository->delete(['id:gt' => 10]); // delete id > 10
``` 

```php
# update
/**
 * About the res
 * @param boolean $ok   true: successful, false: failed
 * @param string  $msg  the message of res
 * @param int     $rows the number of data were updated
 */
$this->repository->update(['name:like' => '%555'], [
    'type' => 3,
    'money' => 9999
]);
```

```php
# query
# A column of a piece of data
$id = $this->repository->findBy(1, 'name'); // 查某个字段
# A piece of data
$this-repository->find(1); // PK was 1
# Multiple data
$this-repository->findAll(['user_id' => 10])
# All values of a field in the result set
$this-repository->findAllBy(['user_id' => 10], 'name');

``` 

#### page data

Repository->paginate($conditions = [], $fields = [], $pageSize = 10, $currentPage = null)

```php
$list = $this->repository->paginate(['status' => 1], ['id', 'name', ...]);
```

#### use expression to query data

> The following methods support expression queries

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

> usage

'column:expression' => 'value'

```php

$items = $this->repository->findAll([
    'id:neq'    => 1,
    'name:like' => '%test%'
]);

// result condition : `id` != 1 and `name` like '%test%' 
```

#### the expressions we support

| expression |    meaning   |      desc     |
|:------     |:-------------|:--------------|
| eq         |       =      |               |
| neq        |      !=      |               |
| ne         |      !=      |               |
| gt         |      >       |               |
| egt        |      >=      |               |
| gte        |      >=      |               |
| ge         |      >=      |               |
| lt         |      <       |               |
| le         |      <=      |               |
| lte        |      <=      |               |
| elt        |      <=      |               |
| in         |      IN      | need an array | 
| not in     |    NOT IN    | need an array | 
| not_in     |    NOT IN    | need an array | 
| between    |    between   | need an array | 
| not_between| not between  | need an array | 
| not between| not between  | need an array | 
| like       |     like     | need a string | 
| not_like   |    not like  | need a string | 
| not like   |    not like  | need a string | 
| rlike      |     rlike    |               | 
| <>         |      <>      |               | 
| auto_like  |     like     |   auto add %  |

#### about auto_like 

```php
// if there is no '%' at your condition，Repository will auto add '%' ('%test%')
$this->repository->findAll(['username:auto_like' => 'test']); 

// if you write '%'，Repository won`t add any '%' to your condition
$this->repository->findAll(['username:auto_like' => 'test%']);

```

```php
// you can use the expression like this:
# find the data where id > 10
$this->repository->findAll(['id:gt' => 10]);

# find the data where id != 10
$this->repository->findAll(['id:neq' => 10]);

# find the data where id in 1,2,3,4,5
$this->repository->findAll(['id:in' => [1,2,3,4,5]);

# find the data where created_at is between 
# 2019-01-01 00:00:00 and 2020-01-01 00:00:00
$this->repository->findAll(['created_at:between' => 
    [
        '2019-01-01 00:00:00', 
        '2020-01-01 00:00:00
    ]
]);

# stop the account end with @@@
$this->repository->update(['name:like' => '%@@@'], ['status' => 0]);

``` 


#### Advanced usage

```php
# Example 1:
# For example, you have an users table, 
# the user`s extension info saved in user_ext table
# maybe you want to find the users and its extension info 
# at the same time

# step 1.Determining model relationships at Users.php(model)

/**
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function extInfo()
{
    # if foreignKey == localKey, you could only write the first user_id 
    # that`s enough
    return $this->hasOne(UsersExt::class, 'user_id');
}

# step 2.do like this
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
# the same tables, users and user_ext
# maybe you want to find the users who user_id > 10 and address is NewYork

# step 1.
# define scope in users model like this

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
# use it like this

$users = $this->userRepository->findAll(
    ['user_id:gt' => 10, 'address' => 'NewYork']
);

```

Is it very simple? ^_^ 😜

To be continued
