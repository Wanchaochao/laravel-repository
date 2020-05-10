# API List

[TOC]

## 1、Retrieves the model and the collection

### first()

```
public function first($conditions, $columns = []);
```

Retrieve objects

#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$columns` Query field；[Support field specification](/?page=repository#6、query-field%20description)
#### example

```php
$user = $this->userRepository->first([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);
```

### firstOrFail()

```
public function firstOrFail($conditions, $columns = []);
```
Retrieves an object, but the query does not throw an error

#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$columns` Query field；[Support field specification](/?page=repository#6、query-field%20description)
#### example

```php
$user = $this->userRepository->firstOrFail([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);
```

### get()

```
public function get($conditions, $columns = []);
```

Retrieve the collection

#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$columns` Query field；[Support field specification](/?page=repository#6、query-field%20description)
#### example

```php
$users = $this->userRepository->get([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);
```

### pluck()

Retrieve the collection

```
public function pluck($conditions, $column, $key = null);
```
#### Parameters

- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specify the fields for the query
- `$key` Specify the field as the key

#### example

```php
$user_ids = $this->userRepository->pluck([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'user_id');

// Specify the field as the key
$ages = $this->userRepository->pluck([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age', 'user_id');
```
## 2、Statistical query

### count()

count

```
public function count($conditions, $column = '*');
```
#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specifies the fields for statistics by default *
#### example

```php
$count = $this->userRepository->count([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
]);
```

### max()

max

```
public function max($conditions, $column);
```
#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specified field
#### example

```php
$max_age = $this->userRepository->max([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age');
```

### min()

min

```
public function min($conditions, $column);
```
#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specified field
#### example

```php
$min_age = $this->userRepository->min([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age');
```

### avg()

avg

```
public function avg($conditions, $column);
```
#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specified field
#### example

```php
$avg_age = $this->userRepository->avg([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
]);
```

### sum()

sum

```
public function sum($conditions, $column);
```
#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specified field
#### example

```php
$sum_age = $this->userRepository->sum([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age');
```

## 3、Data increasing and decreasing

### increment() 

Increments the specified value by the specified field (default increments 1)

```
public function increment($conditions, $column, $amount = 1, $extra = []);
```
#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specified field
- `$amount` Incremented value, default 1
- `$extra`  Attach modified values
#### example

```php
// 年龄加1、状态改为1
$ok = $this->userRepository->increment([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age', 1, ['status' => 1]);
```

### decrement() 

Decrement the specified value by the specified field (default decrement 1)

```
public function decrement($conditions, $column, $amount = 1, $extra = []);
```
#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$column` Specified field
#### example

```php
// Age minus 1, state 1
$ok = $this->userRepository->decrement([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age', 1, ['status' => 1]);
```

## 4、Add data

### insert() 

The new data

```
public function insert(array $values);
```
#### Parameters
- `$values` The new data needs to be a full field array
#### example

```php
$ok = $this->userRepository->insert([
    'name'       => '123456',
    'age'        => 20,
    'status'     => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
```

#### You can add data in batches
```php
$ok = $this->userRepository->insert([
    [
        'name'       => '123456',
        'age'        => 20,
        'status'     => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ],
        [
        'name'       => '123456789',
        'age'        => 20,
        'status'     => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ],
]);
```

### insertGetId() 

The new data gets the new ID

```
public function insertGetId(array $values);
```
#### Parameters
- `$values` The new data needs to be a full field array
#### example

```php
$user_id = $this->userRepository->insertGetId([
    'name'       => '123456',
    'age'        => 20,
    'status'     => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
```

### firstOrCreate() 

The query object is not created (new data is executed), and the query is not processed

```
public function firstOrCreate(array $attribute, array $values);
```
#### Parameters
- `$attribute` An array of query properties for the query data
- `$values` Creates an array of additional properties for the data
#### example

```php
// Query name is 123456, not so created, exist not processed
$user = $this->userRepository->firstOrCreate(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);
```

### firstOrNew() 

The query data is not instantiated

```
public function firstOrNew(array $attribute, array $values);
```
#### Parameters
- `$attribute` An array of query properties for the query data
- `$values` Creates an array of additional properties for the data
#### example

```php
// The query name is 123456, so there is no New object
$user = $this->userRepository->firstOrNew(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);

// If you want to add it
// $user->save();
```

### updateOrCreate() 

Query changes are not created

```
public function UpdateOrCreate(array $attribute, array $values);
```
#### Parameters
- `$attribute` An array of query properties for the query data
- `$values` Modify the array of properties
#### example

```php
// The query name is 123456, so the modified state is 1 and the age is 20, not so the created data
$user = $this->userRepository->UpdateOrCreate(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);
```

### updateOrInsert() 

Query modification is not instantiated

```
public function updateOrInsert(array $attribute, array $values);
```
#### Parameters
- `$attribute` An array of query properties for the query data
- `$values` Modify the array of properties
#### example

```php
// The query name is 123456, so the modified state is 1 and the age is 20, there is no such instantiation of the object
$user = $this->userRepository->updateOrInsert(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);
```

### 5、Other methods

### newBuilder()

Create a Query `Illuminate\Database\Query Builder` or `Illuminate\Database\ Builder` object

>All query methods are implemented based on this method

```
public function newBuilder($conditions, $columns = [])
```

#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
- `$columns` Query field；[Support field specification](/?page=repository#6、query-field%20description)
#### example

```php
// new builder
$builder = $this->userRepository->newBuilder([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);

// Querying individual data
$user = $this->userRepository->newBuilder([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']])->first();

// Query multiple data
$user = $this->userRepository->newBuilder([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']])->get();
```

### filterCondition()

Filter the null value in Query conditions in the way of `filter` series. Query conditions is processed with this method

>An empty array, empty string, ' ', and null are considered null values

```
public function filterCondition($conditions)
```

#### Parameters
- `$conditions` Query conditions；[Multiple queries are supported](/?page=repository#5、description-of%20query%20conditions)
#### example

```php
// 获取Query conditions
$conditions = $this->userRepository->filterCondition([
    'status:in'      => request()->input('status'),
    'username:like'  => request()->input('username'),
]);

```