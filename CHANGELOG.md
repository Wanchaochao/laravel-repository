更新记录
=======
v2.0.3 2020-08-12
-----------------

- fix: 修复生成文件判断条件错误bug

v2.0.2 2020-05-08
-----------------

- feat: `joinWith` 使用了别名，优先使用别名

v2.0.1 2020-05-01
-----------------

- feat: 添加 `and` 和 `or` 预定义字段查询，支持嵌套
- refactor: 
    - `posts.name` 查询改为添加`join`查询条件 而不是关联查询条件 
    - `rel.posts.name` 给关联查询添加附加添加 
- delete: 删除 `findWhere` 方法； 上述 `and` 和 `or` 完全可以代替

v2.0.0 2020-04-20
-----------------
- feat: 添加 `throw` 方法，抛出错误
- refactor: 部分代码重构
    1. `create` 方法返回 `$model->toArray()` 结果
    2. `update` 方法返回修改受影响行数
    3. `delete` 方法返回返回删除行数
    4. `paginate` 方法返回 `\Illuminate\Pagination\Paginator` 对象
    5. `firstField` 方法重命名为 `getFieldArray`
- delete: 删除部分方法
    1. `success` 方法
    2. `error` 方法
    3. `getRelationDefaultFilters` 方法
    4. `getError` 方法
    5. `firstKey` 方法
- refactor: 命名空间修改为 `Littlebug\Repository`

v1.0.18 2020-05-01
------------------

- feat: 添加新功能
    - 添加 `and` 和 `or` 的查询方式
    - 添加 `instance` 静态方法调用，可以不依赖注入使用`repository`类
    ```php
    \Littlebug\Repository\Repository::instance()->find(['status' => 1]);
    ```
- factor: 代码重构
    - `firstField` 方法重命名为 `getFieldArray`
    - 删除类方法 `handleExtraQuery` 中拦截的 `offset`、`limit` 字段
    - 删除类方法 `conditionQuery` 中 `scope` 自定义方法的处理
- delete: 删除方法
    - 删除 `findWhere` 方法； 上述 `and` 和 `or` 完全可以代替
    - 删除 `firstKey` 方法， `findBy` 和 `findAllBy` 字段参数不兼容数组，必须传递字符串
- test: 添加测试用例
 
v1.0.16 2020-03-21
------------------

- refactor: 部分代码重构
    * 删除 `$paginateStyle` 属性
    * 删除 `setPaginateStyle` 方法

- feat: 添加方法 
    * 添加 `simplePaginate($condition = [], $columns = [], $size = 10, $current = null)` 方法
    * 添加 `filterSimplePaginate($condition = [], $columns = [], $size = 10, $current = null)` 方法
    * 添加 `filterFindBy($conditions, $column)` 方法
    * 添加 `filterFindAllBy($conditions, $column)` 方法

- fix: 修复关联表查询 `['__goods.id' => 1]` 条件添加不上问题

v1.0.15 2019-11-04
------------------

- refactor: 命令行使用英文说明

v1.0.14 2019-11-01
------------------

- feat: `repository` 查询添加对 joinWith、leftJoinWith、rightJoinWith、join、leftJoin、rightJoin、的支持
![使用参考](https://wanchaochao.github.io/laravel-repository/docs/images/join.png 'join使用参考')
- refactor: `repository` 没有指定查询字段、默认查询 table.* 而不是 * (解决关联&连表查询字段问题)
- refactor: `repository` 查询条件添加对原生的SQL支持，`DB::raw('users.username >= users.type')`
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
        ['or', ['name' => '1', 'name:eq' => 2]]
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
