Repository 使用说明
==================

### 查询数据

#### 1. 查询单条数据
  
```php
    // 简单主键查询
    $one = $this->repository->find(1);
    
    // 简单数组查询
    $one = $this->repository->find([
        'id'     => 1,
        'level'  => 1,
        'status' => [1, 2]
    ]);
    
    // 表达式查询
    $one = $this->repository->find([
        'username:like'        => 'username',
        'created_time:get'     => '2019-05-28 00:00:00',
        'updated_time:between' => ['2019-04-28 00:00:00', '2019-05-28 00:00:00']
    ]);

```

#### 2. 查询多条数据

```php

```