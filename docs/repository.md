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
- `$conditions` Modified conditions[Support for multiple types of queries](/?page=repository#5、description-of%20query%20conditions)
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
- `$conditions` Conditions for deletion [Support for multiple types of queries](/?page=repository#5、description-of%20query%20conditions)

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

### 5.3 Predefined field query

Some of the predefined keys are for special queries

```php
$this->repository->findAll([
    'limit' => 10,                        // Limit queries to 10
    'order' => 'id desc, created_at asc', // Specify sort conditions
    'group' => 'id',                      // Specify grouping information
]);
```

#### Predefined fields

| Field names        | Type       | Instructions                                |
| --------------- | ----------------- | ----------------------------------- |
| `and`           | `array`           | Add `and` query conditions; You can only pass one array |
| `or`            | `array`           | Add `or` query conditions; You can only pass one array  |
| `force`         | `string`          | Specify the index                      |
| `order`         | `string or array` | Specify sort conditions                        |
| `limit`         | `int`             | Specifies the number of query bars                        |
| `offset`        | `int`             | Specify jump location                        |
| `group`         | `string`          | Specify grouping fields                        |
| `groupBy`       | `string`          | Specify grouping fields                        |
| `join`          | `array`           | Query the parameters of `join`, multiple two-dimensional arrays       |
| `leftJoin`      | `array`           | Query the parameters of `left join`, multiple two-dimensional arrays  |
| `rightJoin`     | `array`           | Query the parameters of `right join`, multiple two-dimensional arrays |
| `joinWith`      | `string or array` | `join` queries are matched by association relationships            |
| `leftJoinWith`  | `string or array` | `left join` queries are matched by association relationships        |
| `rightJoinWith` | `string or array` | `right join` queries are matched by association relationships       |

#### `and`, `or` The query specification

The value must be an array, which is supported `[key => value]` 和 [表达式查询方式](/?page=repository#5.2-表达式查询) The array;
What joins represent the query criteria in the array

> Support for nesting `and` and `or`

example：

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

Execute SQL:

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

### 5.4 Association relation join query

>The premise is that your model defines the relationships of the tables

For example, the following：

User Model

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

User Ext Model

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

Then when you query, you can join query by association relation (by defining the association relation, you can automatically process your join).

```php

// userRepository
UserRepository::instance()->findAll([
    'status'   => 1,
    'joinWith' => 'ext', // ext Represents the name of the associated method, multiple of which require an array ['ext', 'children']
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

Execute SQL:
```SQL
select `user_ext`.* from `user_ext` inner join `users` on (`users`.`user_id` = `user_ext`.`user_id`) where `user_ext`.`status` = 1
```

##### leftJoinWith 和 rightJoinWith

>If you want to use `left join` or `right join` you can just use `leftJoinWith` or `rightJoinWith`

##### Add join queries to query conditions

through：`['table name.field' => 'value']`

```php
UserRepository::instance()->findAll([
    'status'                 => 1,
    'joinWith'               => 'ext',
    'user_ext.status:neq'    => 1,
    'user_ext.created_at:gt' => date('Y-m-d H:i:s')
]);
```
Execute SQL:
```SQL:
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) where `users`.`status` = 1 and `user_ext`.`status` != 1 and `user_ext`.`created_at` > '2020-04-29 22:31:00'
```

##### Alias the join table

through： `['alias' => 'Related party method name']`

```php

UserRepository::instance()->findAll([
    'status'           => 1,
    'joinWith'         => ['t1' => 'ext'],
    't1.status:neq'    => 1,
    't1.created_at:gt' => date('Y-m-d H:i:s')
]);
```

Execute SQL:
```SQL
select `users`.* from `users` inner join `user_ext` AS `t1` on (`users`.`user_id` = `t1`.`user_id`) where `users`.`status` = 1 and `t1`.`status` != 1 and `t1`.`created_at` > '2020-04-29 22:31:00'
```

### 5.5 Associative query additional conditions

**Remember that an associated query is not a join query** an associated query is a query for the main table after the completion of the query, through the definition of the association and then to query the associated table, is the execution of two SQL

Define the way： `['rel.Associate method name.field' => 'value']`

```php
UserRepository::instance()->find([
    'user_id'        => 1,
    'rel.ext.status' => 1, // Add a condition for the associated table query
    'rel.ext.type'   => 2, // Add a condition for the associated table query
], ['*', 'ext' => ['*']]);

```

Execute SQL

1. The main query
```SQL
select `users`.* from `users` where `users`.`user_id` = 1
```

2. Associative table query
```SQL
select `user_ext`.* from `user_ext` where `user_id` in (1) and `user_ext`.`status` = 1 and `user_ext`.`type` = 1
```

### 5.6 join query

use join query

```php
UserRepository::instance()->findAll([
    'status'                 => 1,
    'join'                   => ['user_ext', 'users.user_id', '=', 'user_ext.user_id'],
    'user_ext.status:neq'    => 1,
    'user_ext.created_at:gt' => date('Y-m-d H:i:s')
]);
```

Execute SQL:
```SQL
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) where `users`.`status` = 1 and `user_ext`.`status` != 1 and `user_ext`.`created_at` > '2020-04-29 22:31:00'
```

##### leftJoin and rightJoin

>Just use `leftJoin` or `rightJoin`

#### Multiple joins exist at once

You need to define the join as a two-dimensional array

```php
UserRepository::instance()->findAll([
    'join'=> [
        ['user_ext', 'users.user_id', '=', 'user_ext.user_id'],
        ['users as t1', 'users.user_id', '=', 't1.user_id']
    ],
]);
```

Execute SQL:
```SQL
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) inner join `users` as `t1` on (`users`.`user_id` = `t1`.`user_id`)
```
### 5.7 scope query

You need a query method where `Model` defines the `scope` prefix

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

Define the way：`['Remove the scope prefix from the method name' => 'Required parameters']`

```php
UserRepository::instance()->findAll([
    'username' => 'test',
    'joinExt'  => 1,
]);
```

Execute SQL:
```SQL
select `users`.* from `users` inner join `user_ext` on (`users`.`user_id` = `user_ext`.`user_id`) where `name` like 'test' and `user_ext`.`status` = 1
```

### 5.8 Native SQL query

> Careful; There are SQL injection risks

Wrap the query condition with the: `DB::raw()` function

```php
UserRepository::instance()->findAll([
    DB::raw("user_id = 1 and status = 1"),
    'name' => DB::raw('like "_test"'),
]);
```

### conclusion
```php
$conditions = [
    // The fields in the table are precisely queried
    'status' => 1,
    'id'     => [1, 2, 3, 4], // An array value is automatically converted to an in query `id` in (1, 2, 3, 4)

    // Predefined field query
    'order' => 'id desc', // Specify the sort field and how
    'limit' => 10,        // Restrict query conditions
    'group' => 'id',      // Specify grouping conditions
    'force' => 'name',    // Specify the index to use

    // join Associated query
    'join'     => ['users', 'users.user_id', '=', 'orders.user_id'],
    'leftJoin' => [
        // multiple leftJoin
        ['users as u1', 'u1.user_id', '=', 'orders.user_id'],
        ['user_image', 'users_image.user_id', '=', 'users.user_id'],
    ],

    // Expression query
    'username:like'      => 'test',
    'created_at:between' => ['2019-01-02 00:00:00', '2019-01-03 23:59:59'],
    'id:ge'              => 12, // id > 12

    // relation The query condition restricts only the current relation association query
    'rel.ext.address:like'   => '北京',
    'rel.ext.created_at:gte' => '2019-01-01 00:00:00',

    // Add join queries through a relation relation
    'joinWith'     => ['ext'],
    // The associative table defines the alias, if there is no alias, the associative table and the main table have the same name, use the custom alias' t1 ', multiple homonyms with this address 't2', 't3'
    'leftJoinWith' => ['alias' => 'children'],

    // Add a condition to the join table query
    'user_ext.status' => 1,
    'users.status'    => 1,

    // scope Custom query
    'address'  => '北京',      // search `scopeAddress($query, $address)` method
    'children' => [1, 2, 3],  // search `scopeChildren($query, $childrenIds)` method
];
```

>If the query field in > does not match the nine methods described above, the query field is converted to the method name, and the query value is a parameter that directly calls the method 'Illuminate, Database, Eloquent Builder'
 (** if the field method does not exist and the program throws incorrectly **, this is different from version 1.0.*) for example:

```php
UserRepository::instance()->findAll([
    'with'        => ['ext', 'children'],
    'orderByDesc' => 'id',
    'limit'       => 10,
]);

// Internal actual call
// $query->with(['ext', 'children'])->orderByDesc('id')->limit(10);
```

## 6、Query field description

Query field `$columns`, is the field of select

### 6.1 Query the fields in this table

The fields in this table are written directly

```php
// select `user_id`, `name` from `users` where `user_id` = 1
$this->userRepository->find(1, ['user_id', 'name']);
```

### 6.2 Query the associated table fields

>`model` The association relationships that need to be defined

through： `['Correlation method' => ['Field information']]`

```php
$this->userRepository->find(1, ['user_id', 'name', 'ext' => ['status', 'avatar', 'auth_id']]);
```

### 6.3 Query associated table statistics

>`model` The association relationships that need to be defined

through： `['Correlation method_count']`

```php
$this->userRepository->find(1, ['user_id', 'name','ext_count']);
```
// We're using the `withCount()` method of `model`
```php
// `model`
User::select(['user_id', 'name'])->withCount('ext')->where('user_id', 1)->first()->toArray();
```
### 6.4 Query the join table field

```php
$this->userRepository->find([
    'joinWith' => 'ext',
], ['user_id', 'name', 'user_ext.status']);
```

### 6.5 Query native SQL fields

```php
$this->userRepository->find([
    'status' => 1,
], [
    DB::raw('COUNT(*) AS `count_number`'),
    DB::raw('MAX(`user_id`) AS `max_user_id`'),
    DB::raw('AVG(`age`) AS `avg_age`'),
]);
```

### 6.6 conclusion

```php
$columns = [

    // Field queries for this table
    'id',
    'username',

    // Associate a table field query
    'ext'      => ['*'], // The `ext` association that corresponds to the `model` definition
    'children' => ['*'], // The `children` association that corresponds to the `model` definition

    // Correlate table statistics field queries
    'ext_count',      // The `ext` association that corresponds to the `model` definition
    'children_count', // The `children` association that corresponds to the `model` definition

    // The join table field
    'users.username',
    'users.age',

    // Native SQL field
    DB::raw('SUM(`users`.`age`) AS `sum_age`'),
    DB::raw('COUNT(*) AS `total_count`'),
];
```

## 7、Create, delete and modify the event method

If you want to prevent the main method from executing and have the main method return an error, just throw the error

### 7.1 The new event is triggered when `create($data)` is executed

1. `beforeCreate($data)` Before the new

2. `afterCreate($data, $news)`  After the new

#### 7.1.1 Parameters

| Parameter name| Type | Instructions                               |
| -------- | -------- | -------------------------------------- |
| `$data`  | `array`  | Filters out arrays that interfere with data (data that is not a field in a table) |
| `$news`  | `array`  | New successful call to `model->toArray()` array   |

### 7.2 The modified event is triggered when `update($conditions, array $data)` executes

1. `beforeUpdate($conditions, $data)` Before the update

2. `afterUpdate($conditions, $data, $row)` After the update

#### 7.2.1 Parameters

| Parameter name| Type | Instructions                               |
| ------------- | -------- | -------------------------------------- |
| `$conditions` | `array`  | The query condition array after the primary key query is processed         |
| `$data`       | `array`  | Filters out arrays that interfere with data (data that is not a field in a table) |
| `$row`        | `int`    | Number of affected rows                       |

### 7.3 The deleted event is triggered when `delete($conditions)` is executed

1. `beforeDelete($conditions)` Before the delete

2. `afterDelete($conditions, $row)` After the delete

#### 7.3.1 Parameters

| Parameter name| Type | Instructions                               |
| ------------- | -------- | ------------------------------ |
| `$conditions` | `array`  | The query condition array after the primary key query is processed |
| `$row`        | `int`    | Number of affected rows               |

### 7.4 Processing of primary key queries about `$conditions`

Non-empty strings, integers, floating point Numbers, and indexed arrays are all turned into primary key queries

```php
// Assume that the primary key name of the table is id
$conditions = 1;            // ['id' => 1]
$conditions = '1';          // ['id' => 1]
$conditions = [1, 2, 3];    // ['id' => [1, 2, 3, 4]]
```

## 8、Other instructions

### 8.1 Other instructions

#### 8.1.1 Query the method that returns the array

>So we're going to return `model->toArray()`

##### create(array $data)
##### find($conditions, $columns = [])
##### findAll($conditions, $columns = [])

#### 8.1.2 Returns the method of the object
>Returns a model object or collection

##### first($conditions, $columns = [])
##### firstOrFail($conditions, $columns = [])
##### firstOrCreate($attributes, $values)
##### updateOrCreate($attributes, $values)
##### get($conditions, $columns = [])
##### pluck($conditions, $column, $key = null)

### 8.2 Requirements for `model`

1. `create` and ` update ` are batch assignment, need a `model` define batch assignment whitelist `$fillable` or blacklist `$guarded`
2. Need to define the `$columns` field information to indicate which fields are in the table

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

>Although this step is not required, defining `$columns` will reduce the cost of a `SQL` query