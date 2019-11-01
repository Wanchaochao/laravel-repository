更新记录
=======

v1.0.14 2019-11-01
------------------

- feat: `repository` 查询添加对 joinWith、leftJoinWith、rightJoinWith、join、leftJoin、rightJoin、的支持
- refactor: `repository` 没有指定查询字段、默认查询 table.* 而不是 * (解决关联&连表查询字段问题)
- change: `repository` 修改查询条件中定义了关联、但是在查询字段的时候、没有定义要查询关联的字段、
那么不添加关联查询(关联条件也会舍弃)、之前处理会自动添加关联查询! 如果需要按照之前的处理的话，需要自己重写 
`getRelations` 方法

v1.0.13 2019-08-24
------------------

- refactor: `repository` 的 `update` 方法优化，使用批量删除，但应用模型的修改器

v1.0.12 2019-07-22
------------------

- add: 添加两个`trait`; 方便在修改和删除之前或者之后清理缓存；
    - `BeforeTrait` 在修改和删除之前执行 `clearCache` 方法
    - `AfterTrait` 在修改和删除之后执行 `clearCache` 方法

v1.0.11 2019-07-05
------------------

- refactor: 优化 `repository` `update` 方法 如果是通过主键修改，不走批量修改(可以走修改器)
- add: `repository` 添加 `filterPaginate` 方法 过滤查询分页数据信息

v1.0.10 2019-06-27
------------------

- refactor: `repository`中`getRelationModel`方法优化

    有关联`count`查询，如果指定字段，那么不查询全部字段

- refactor: `repository`中`findWhere`优化，对关联数组查询支持

    ```php
    $this->userRepository->findWhere([
        'and', 
        ['username' => 1, 'age' => 2],
        ['or', ['name' => '1', 'name' => 2]]
    ])->get();
    ```

v1.0.9 2019-06-15
-----------------

- fix: `findWhere`查询`or`查询的bug 
- add: `repository`查询指定字段关联字段添加支持反向关联

v1.0.8 2019-06-12
-----------------

- add: `repository`添加新方法
    - `findWhere(array $where, array $columns = [])`通过数组查询数据,支持更复杂的查询
    
    ```php
    $this->userRepository->findWhere([
        'and',
        ['or', ['username', 'like', 'test'], ['age' => 5]],
        ['level' => 5]
    ])->get();
    
    // sql: where ((`username` like 'test' or `age` = 5) and `level` = 5)
    ```
- remove: 删除多余代码生成命令

v1.0.7 2019-06-06
-----------------

- add: repository 支持更多的 model 原生方法

     * @method Model firstOrCreate(array $attributes, array $value = [])
     * @method Model firstOrNew(array $attributes, array $value = [])
     * @method Model updateOrCreate(array $attributes, array $value = [])
     * @method Model findOrFail($id, $columns = ['*'])
     * @method Model findOrNew($id, $columns = ['*'])
     * @method Model findMany($ids, $columns = ['*'])
     
- add: repository 添加事件处理方法
    - beforeCreate(array $data)
    - afterCreate(array $data, array $news)
    - beforeUpdate(array $conditions, array $data)
    - afterUpdate(array $conditions, array $data, $row)
    - beforeDelete(array $conditions)
    - afterDelete(array $conditions, $row)

- fix: 修复 withCount 不存在报的错误

v1.0.6 2019-06-03
-----------------
- add: repository 添加 getFilterModel($conditions, $fields) 方法

v1.0.5 2019-06-02
-----------------

- change: repository 所有方法全部对外开放(方法全部为public)

v1.0.4 2019-06-02
-----------------

- bug: 因为批量赋值使用的是黑名单制，laravel 不会自己过滤多余字段，导致新增时候添加了多余字段bug修复

v1.0.3 2019-06-02
-----------------

- change: model 没有通过字段 columns 定义表的字段，那么通过数据库查询获取表的字段信息
