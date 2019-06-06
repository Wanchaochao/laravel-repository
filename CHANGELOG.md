更新记录
=======

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