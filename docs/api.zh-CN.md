# API 列表

[TOC]

## 一、检索model和集合

### first()
```
public function first($conditions, $columns = []);
```
检索对象

#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$columns` 查询字段；[支持字段指定](/?page=repository#六、查询字段说明)
#### 示例

```php
$user = $this->userRepostiory->first([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);
```

### firstOrFail()

```
public function firstOrFail($conditions, $columns = []);
```
检索对象,查询不到抛出错误

#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$columns` 查询字段；[支持字段指定](/?page=repository#六、查询字段说明)
#### 示例

```php
$user = $this->userRepostiory->firstOrFail([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);
```

### get()

```
public function get($conditions, $columns = []);
```
检索集合

#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$columns` 查询字段；[支持字段指定](/?page=repository#六、查询字段说明)
#### 示例

```php
$users = $this->userRepostiory->get([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);
```

### pluck()
检索集合
```
public function pluck($conditions, $column, $key = null);
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定查询的字段
- `$key` 指定字段作为key
#### 示例

```php
$user_ids = $this->userRepostiory->pluck([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'user_id');

// 指定字段作为key
$ages = $this->userRepostiory->pluck([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age', 'user_id');
```
## 二、统计查询

### count()

统计数量

```
public function count($conditions, $column = '*');
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定统计的字段默认*
#### 示例

```php
$count = $this->userRepostiory->count([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
]);
```

### max()

获取最大值

```
public function max($conditions, $column);
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定字段
#### 示例

```php
$max_age = $this->userRepostiory->max([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age');
```

### min()

获取最小值

```
public function min($conditions, $column);
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定字段
#### 示例

```php
$min_age = $this->userRepostiory->min([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age');
```

### avg()

获取平均值

```
public function avg($conditions, $column);
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定字段
#### 示例

```php
$avg_age = $this->userRepostiory->avg([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
]);
```

### sum()

求和

```
public function sum($conditions, $column);
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定字段
#### 示例

```php
$sum_age = $this->userRepostiory->sum([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age');
```

## 三、数据递增递减

### increment() 
按查询条件指定字段递增指定值(默认递增1)

```
public function increment($conditions, $column, $amount = 1, $extra = []);
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定字段
- `$amount` 递增的值，默认1
- `$extra`  附加修改的值
#### 示例

```php
// 年龄加1、状态改为1
$ok = $this->userRepostiory->increment([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age', 1, ['status' => 1]);
```

### decrement() 
按查询条件指定字段递减指定值(默认递减1)

```
public function decrement($conditions, $column, $amount = 1, $extra = []);
```
#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$column` 指定字段
#### 示例

```php
// 年龄减1，状态改为1
$ok = $this->userRepostiory->decrement([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], 'age', 1, ['status' => 1]);
```

## 四、添加数据

### insert() 

新增数据

```
public function insert(array $values);
```
#### 参数说明
- `$values` 新增的数据、需要是全量字段数组
#### 示例

```php
// 年龄减1，状态改为1
$ok = $this->userRepostiory->insert([
    'name'       => '123456',
    'age'        => 20,
    'status'     => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
```

#### 可以批量添加数据
```php
$ok = $this->userRepostiory->insert([
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

新增数据获取新增ID

```
public function insertGetId(array $values);
```
#### 参数说明
- `$values` 新增的数据、需要是全量字段数组
#### 示例

```php
// 年龄减1，状态改为1
$user_id = $this->userRepostiory->insertGetId([
    'name'       => '123456',
    'age'        => 20,
    'status'     => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
```

### firstOrCreate() 

查询对象没有就创建(执行新增数据)，查询到不处理

```
public function firstOrCreate(array $attribute, array $values);
```
#### 参数说明
- `$attribute` 查询数据的查询属性数组
- `$values` 创建数据的附加属性数组
#### 示例

```php
// 查询名称为123456，没有那么创建, 存在不处理
$user = $this->userRepostiory->firstOrCreate(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);
```

### firstOrNew() 

查询数据没有就实例化

```
public function firstOrNew(array $attribute, array $values);
```
#### 参数说明
- `$attribute` 查询数据的查询属性数组
- `$values` 创建数据的附加属性数组
#### 示例

```php
// 查询名称为123456，没有那么New对象
$user = $this->userRepostiory->firstOrNew(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);

// 如果要新增的话
// $user->save();
```

### updateOrCreate() 

查询修改没有就创建

```
public function UpdateOrCreate(array $attribute, array $values);
```
#### 参数说明
- `$attribute` 查询数据的查询属性数组
- `$values` 修改的属性数组
#### 示例

```php
// 查询名称为123456，存在那么修改状态为 1 年龄为 20, 没有那么创建数据
$user = $this->userRepostiory->UpdateOrCreate(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);
```

### updateOrInsert() 

查询修改没有就实例化

```
public function updateOrInsert(array $attribute, array $values);
```
#### 参数说明
- `$attribute` 查询数据的查询属性数组
- `$values` 修改的属性数组
#### 示例

```php
// 查询名称为123456，存在那么修改状态为 1 年龄为 20, 没有那么实例化对象
$user = $this->userRepostiory->updateOrInsert(['name' => '123456'], [
    'status' => 1,
    'age'    => 20,
]);
```

### 五、其他方法

### newBuilder()

创建一个查询`Illuminate\Database\Query\Builder` 或者 `Illuminate\Database\Eloquent\Builder` 对象

>所有查询方法都是基于该方法实现

```
public function newBuilder($conditions, $columns = [])
```

#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
- `$columns` 查询字段；[支持字段指定](/?page=repository#六、查询字段说明)
#### 示例

```php
// 获取 builder
$builder = $this->userRepostiory->newBuilder([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']]);

// 查询 单个数据
$user = $this->userRepostiory->newBuilder([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']])->first();

// 查询 多个数据
$user = $this->userRepostiory->newBuilder([
    'status:in'      => [1, 2, 3],
    'username:like'  => 'test',
], ['*', 'ext' => ['*']])->get();
```

### filterCondition()

过滤查询条件中的空值，`filte`系列的方式，查询条件使用该方法处理

>空数组、空字符串、' '、null 会被认为是空值

```
public function filterCondition($conditions)
```

#### 参数说明
- `$conditions` 查询条件；[支持多种方式查询](/?page=repository#五、查询条件说明)
#### 示例

```php
// 获取查询条件
$conditions = $this->userRepostiory->filterCondition([
    'status:in'      => request()->input('status'),
    'username:like'  => request()->input('username'),
]);

```