<?php

namespace Littlebug\Repository;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

/**
 * Class Repository 基础 Repository 类
 *
 * @method Model|null first($conditions = [], $columns = []) 查询单个数据
 * @method Model firstOrFail($conditions, $columns = []) 查询单个数据, 不存在抛出错误
 * @method Collection get($conditions = [], $columns = []) 查询多个数据
 * @method Collection pluck($conditions, $column, $key = null) 查询多个数据然后按照指定字段为数组的key
 * @method int count($conditions = [], $column = '*') 统计数量
 * @method int|mixed max($conditions, $column) 统计求最大值
 * @method int|mixed min($conditions, $column) 统计求最小值
 * @method int|mixed avg($conditions, $column) 统计求平均值
 * @method int|mixed sum($conditions, $column) 统计求和
 * @method string toSql($conditions = [], $columns = []) 获取执行的SQL
 * @method array|mixed getBindings($conditions = []) 获取绑定的值
 * @method int increment($conditions, $column, $amount = 1, $extra = []) 按查询条件指定字段递增指定值(默认递增1)
 * @method int decrement($conditions, $column, $amount = 1, $extra = []) 按查询条件指定字段递减指定值(默认递减1)
 * @method null|array filterFind($conditions = [], $columns = []) 过滤查询单个数据
 * @method null|array filterFindBy($conditions, $column) 过滤查询条件查询单个数据的单个字段
 * @method array filterFindAll($conditions = [], $columns = []) 过滤查询条件查询多个数据
 * @method array filterFindAllBy($conditions, $column) 过滤查询条件查询多个数据的单字段数组
 * @method LengthAwarePaginator|\Illuminate\Contracts\Pagination\LengthAwarePaginator filterPaginate($conditions = [], $columns = [], $size = 10, $current = null) 过滤查询条件查询分页数据
 * @method array|mixed getConnection() 获取数据库连接
 * @method boolean insert(array $values) 新增数据
 * @method int|mixed insertGetId(array $values, $sequence = null) 新增数据获取新增ID
 * @method Model firstOrCreate(array $attributes, array $value = []) 查询数据没有就创建
 * @method Model firstOrNew(array $attributes, array $value = []) 查询数据没有就实例化
 * @method Model updateOrCreate(array $attributes, array $value = []) 查询修改没有就创建
 * @method Model updateOrInsert(array $attributes, array $values = []) 查询修改没有就实例化
 * @method Model findOrFail($id, $columns = ['*']) 主键查询没有就抛出错误
 * @method Model findOrNew($id, $columns = ['*']) 主键查询没有就实例化
 * @method Collection findMany($ids, $columns = ['*']) 主键查询多个
 *
 * @package Littlebug\Repository
 */
abstract class Repository
{
    /**
     * The model to provide.
     *
     * @var Model|Builder
     */
    protected $model;

    /**
     * @var array 不需要查询条件的方法
     */
    protected $passThru = [
        'insert', 'insertGetId', 'getConnection', 'firstOrCreate', 'firstOrNew',
        'updateOrCreate', 'findOrFail', 'findOrNew', 'findMany', 'updateOrInsert',
    ];

    /**
     * @var array 需要处理columns字段的原生方法
     */
    protected $columnMethods = ['first', 'firstOrFail', 'get', 'toSql'];

    /**
     * @var array 支持查询的表达式
     */
    protected $expression = [
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
        'in'          => 'In',
        'not_in'      => 'NotIn',
        'not in'      => 'NotIn',
        'between'     => 'Between',
        'not_between' => 'NotBetween',
        'not between' => 'NotBetween',
        'like'        => 'like',
        'not_like'    => 'not like',
        'not like'    => 'not like',
        'rlike'       => 'rlike',
        '<>'          => '<>',
        '<=>'         => '<=>',
    ];

    /**
     * Create a new instance.
     *
     * @param Model $model
     *
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 静态方法调用
     *
     * @return static
     */
    public static function instance()
    {
        return app(static::class);
    }

    /**
     * Return the model instance.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 获取主键查询条件
     *
     * @param mixed|array $conditions 查询的条件
     *
     * @return array
     */
    public function getPrimaryKeyCondition($conditions)
    {
        // 查询条件为空
        if (empty($conditions)) {
            return [];
        }

        // 标量(数字、字符、布尔值)查询, 处理为主键查询
        if (is_scalar($conditions)) {
            if ($this->model->getKeyType() == 'int') {
                $conditions = intval($conditions);
            }

            return [$this->model->getKeyName() => $conditions];
        }

        // 没有自定义查询的索引数组也要转为主键查询
        if (is_array($conditions) && !Helper::isAssociative($conditions, false) && !$this->hasRaw($conditions)) {
            $values = array_values($conditions);
            // 主键为 int 类型使用 intval 处理
            if ($this->model->getKeyType() === 'int') {
                $values = array_map('intval', $values);
            }

            // 或者不是关联数组查询，也处理为主键查询
            return [$this->model->getKeyName() => $values];
        }

        return (array)$conditions;
    }

    /**
     * 验证是否存在自定义查询条件
     *
     * @param array $conditions
     *
     * @return bool
     */
    private function hasRaw($conditions)
    {
        foreach ($conditions as $value) {
            if ($value instanceof Expression) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * 获取表格字段，并转换为KV格式
     *
     * @param Model|null|string $model 指定使用model
     *
     * @return array
     */
    public function getTableColumns($model = '')
    {
        $model        = $model && is_object($model) ? $model : $this->model;
        $modelColumns = isset($model->columns) && is_array($model->columns) && !empty($model->columns) ?
            $model->columns :
            Schema::setConnection($model->getConnection())->getColumnListing($model->getTable());
        return array_combine($modelColumns, $modelColumns);
    }

    /**
     * 抛出错误
     *
     * @param string $message 错误信息
     *
     * @throws Exception
     */
    public function throw($message)
    {
        throw new Exception($message);
    }

    /**
     * 新增数据
     *
     * @param array $data 新增的数据
     *
     * @return array
     *
     * @throws Exception
     */
    final public function create(array $data)
    {
        // 过滤非法字段，禁止新增主键
        $data = $this->getValidColumns($data);

        // 不能是空数组
        if (empty($data) || !is_array($data)) {
            $this->throw('创建数据为空');
        }

        // 执行新增数据，并执行前置、后置方法
        return $this->runEventFunction(function ($data) {
            return $this->model->create($data)->toArray();
        }, 'create', $data);
    }

    /**
     * 修改数据
     *
     * @param array|mixed $conditions 修改的查询条件
     * @param array       $data       修改的数据
     *
     * @return array
     * @throws Exception
     */
    final public function update($conditions, array $data)
    {
        // 根据pk更新单条记录
        $conditions = $this->getPrimaryKeyCondition($conditions);
        if (empty($conditions)) {
            $this->throw('未指定修改条件');
        }

        // 过滤非法字段，禁止更新主键
        $data = $this->getValidColumns($data);

        // 空值判断
        if (empty($data)) {
            $this->throw('未指定更新字段');
        }

        // 执行修改，并且执行前置和后置方法
        return $this->runEventFunction(function ($conditions, $data) {
            // 应用 model 的修改器
            $updateAttributes = $this->getModel()->newInstance()->fill($data)->getAttributes();

            // 使用批量修改数据
            return $this->newBuilder($conditions)->update($updateAttributes);
        }, 'update', $conditions, $data);
    }

    /**
     * 删除数据
     *
     * @param mixed|array $conditions 删除的条件
     *
     * @return array
     * @throws \Exception
     */
    final public function delete($conditions)
    {
        // 查询条件处理
        $conditions = $this->getPrimaryKeyCondition($conditions);
        if (empty($conditions)) {
            $this->throw('未指定删除条件');
        }

        // 执行删除，并且执行前置和后置方法
        return $this->runEventFunction(function ($conditions) {
            return $this->newBuilder($conditions)->delete();
        }, 'delete', $conditions);
    }

    /**
     * 运行带前置和后置的方法
     *
     * @param callable $func    需要执行的方法
     * @param string   $method  方法名称
     * @param mixed    ...$args 需要执行的参数
     *
     * @return mixed
     */
    final public function runEventFunction(callable $func, $method, ...$args)
    {
        // 处理方法
        $method = ucfirst($method);

        // 执行前置函数
        $beforeMethod = 'before' . $method;
        if (method_exists($this, $beforeMethod)) {
            $this->{$beforeMethod}(...$args);
        }

        // 执行函数
        $result = $func(...$args);

        // 执行后置函数
        $afterMethod = 'after' . $method;
        if (method_exists($this, $afterMethod)) {
            array_push($args, $result);
            $this->{$afterMethod}(...$args);
        }

        return $result;
    }

    /**
     * 查询一条数据
     *
     * @param array|mixed $conditions 查询条件
     * @param array       $columns    查询字段
     *
     * @return array|null
     */
    public function find($conditions = [], $columns = [])
    {
        if ($item = $this->newBuilder($conditions, $columns)->first()) {
            return $item->toArray();
        }

        return null;
    }

    /**
     *
     * 获取一条记录的单个字段结果
     *
     * @param mixed|array $conditions 查询条件
     * @param string      $column     获取的字段
     *
     * @return mixed
     */
    public function findBy($conditions, $column)
    {
        return Arr::get($this->find($conditions, $this->getFieldArray($column)), $column);
    }

    /**
     * 查询所有记录
     *
     * @param array|mixed $conditions 查询条件
     * @param array       $columns    查询字段
     *
     * @return array
     */
    public function findAll($conditions, $columns = [])
    {
        return $this->newBuilder($conditions, $columns)->get()->toArray();
    }

    /**
     *
     * 获取结果集里的单个字段所有值的数组
     *
     * @param mixed|array $conditions 查询条件
     * @param string      $column     获取的字段
     *
     * @return array
     */
    public function findAllBy($conditions, $column)
    {
        if (!$data = $this->findAll($conditions, $this->getFieldArray($column))) {
            return [];
        }

        $columns = [];
        foreach ($data as $value) {
            $columns[] = Arr::get($value, $column);
        }

        return $columns;
    }

    /**
     * 分页查询数据
     *
     * @param array $conditions 查询条件
     * @param array $columns    查询字段
     * @param int   $size       每页条数
     * @param null  $current    当前页
     *
     * @return LengthAwarePaginator|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($conditions = [], $columns = [], $size = 10, $current = null)
    {
        return $this->newBuilder($conditions, $columns)->paginate($size, ['*'], 'page', $current);
    }

    /**
     * 过滤查询条件
     *
     * @param mixed|array $conditions 查询条件
     *
     * @return mixed
     */
    public function filterCondition($conditions)
    {
        if (!is_array($conditions) || !Helper::isAssociative($conditions)) {
            return $conditions;
        }

        foreach ($conditions as $key => &$value) {
            if (in_array(strtolower($key), ['or', 'and'], true)) {
                $value = $this->filterCondition($value);
            }

            if (Helper::isEmpty($value)) {
                unset($conditions[$key]);
            }
        }
        unset($value);

        return $conditions;
    }

    /**
     * 设置model 的查询信息
     *
     * @param array $conditions 查询条件
     * @param array $columns    查询字段
     *
     * @return Model|Builder|QueryBuilder
     */
    public function newBuilder($conditions = [], $columns = [])
    {
        $model = $this->model->newModelInstance();
        // 查询条件为空，直接返回
        if (empty($conditions) && empty($columns)) {
            return $model;
        }

        // 查询条件为
        $conditions   = $this->getPrimaryKeyCondition($conditions);
        $table        = $model->getTable();
        $tableColumns = $this->getTableColumns($model);
        $columns      = (array)$columns;

        // 解析出查询条件和查询字段中的关联信息
        list($conditionRelations, $findConditions) = $this->parseConditionRelations($conditions);
        list($fieldRelations, $selectColumns) = $this->parseColumnRelations($columns, $table, $tableColumns);

        // 处理关联信息查询
        $relations = $this->getRelations($conditionRelations, $fieldRelations);
        $model     = $this->getRelationModel($model, $relations, $selectColumns, $table);

        // 处理查询条件
        return $this->handleConditionQuery($findConditions, $model, $table, $tableColumns);
    }

    /**
     * 解析查询条件中的关联关系
     *
     * @param array $conditions 查询条件
     *
     * @return array
     */
    public function parseConditionRelations($conditions)
    {
        // 分组，如果是relation的查询条件，需要放在前面build
        $relations = $findConditions = [];
        if (empty($conditions)) {
            return [$relations, $findConditions];
        }

        // 解析查询条件
        foreach ($conditions as $field => $value) {
            // 第一步：检查关联查询
            if (Str::startsWith($field, 'rel.')) {
                $field = Str::replaceFirst('rel.', '', $field);
                $index = strpos($field, '.');
                // 处理关联名称
                $relationName = substr($field, 0, $index);
                $fieldName    = substr($field, $index + 1);
                $relationName = Str::camel($relationName);
                if (!isset($relations[$relationName])) {
                    $relations[$relationName] = [];
                }

                $relations[$relationName][$fieldName] = $value;
            } else {
                $findConditions[$field] = $value;
            }
        }

        return [$relations, $findConditions];
    }

    /**
     * 解析查询字段中的关联关系
     *
     * @param array  $columns      查询的字段信息
     * @param string $table        查询的表
     * @param array  $tableColumns 表中存在的字段信息
     *
     * @return array
     */
    public function parseColumnRelations($columns, $table, $tableColumns)
    {
        $relations = $selectColumns = [];
        if (empty($columns)) {
            return [$relations, $selectColumns];
        }

        // 解析查询字段信息
        foreach ($columns as $k => $field) {
            if (is_int($k) && is_string($field)) { // 第一步，判断字段是否为字符串
                // 判断是否存在表中 或者 field === *
                if (isset($tableColumns[$field]) || $field === '*') {
                    $selectColumns[] = $table . '.' . $field;
                } elseif (Str::endsWith($field, '_count')) {
                    $relationName = Str::replaceLast('_count', '', $field);
                    $relationName = Str::camel($relationName);
                    if (!isset($relations[$relationName])) {
                        $relations[$relationName] = ['withCount' => true, 'columns' => [], 'with' => false];
                    }

                    $relations[$relationName]['withCount'] = true;
                } else {
                    $selectColumns[] = $field;
                }
            } elseif (!is_int($k) && is_string($k)) { // 如果是key => value 格式 那么认为是 关联查询
                // edit by jinxing.liu 2020-08-15 添加可以排除查询的字段 start:
                if ($k === 'except') {
                    // 比较表中的字段、进行排除处理
                    $diffColumns = array_values(array_diff($tableColumns, (array)$field));
                    foreach ($diffColumns as $diffColumn) {
                        $tableColumn = $table . '.' . $diffColumn;
                        if (!in_array($tableColumn, $selectColumns)) {
                            $selectColumns[] = $tableColumn;
                        }
                    }

                    continue;
                }
                // end

                $relationName = Str::camel($k);
                if (!isset($relations[$relationName])) {
                    $relations[$relationName] = ['withCount' => false, 'columns' => [], 'with' => true];
                }

                $relations[$relationName]['columns'] = $field;
                $relations[$relationName]['with']    = true;

            } elseif ($field instanceof Expression) { // 表达式查询字段
                $selectColumns[] = $field;
            }
        }

        return [$relations, $selectColumns];
    }

    /**
     * 获取关系信息
     *
     * @param array $conditionRelations 有查询条件的关联信息
     * @param array $fieldRelations     有字段查询关联信息
     *
     * @return array
     */
    public function getRelations(array $conditionRelations, array $fieldRelations)
    {
        // 查询条件中定义的关联、优先级最低
        $relations = [];
        foreach ($conditionRelations as $relationName => $conditions) {
            $relations[$relationName] = ['conditions' => $conditions];
        }

        // 字段中指定关联查询、优先级最高
        foreach ($fieldRelations as $relationName => $relation) {
            $relations[$relationName] = array_merge(Arr::get($relations, $relationName, []), $relation);
        }

        return $relations;
    }

    /**
     * 获取处理查询关联的 model
     *
     * @param Model|Builder|QueryBuilder $model         查询的model
     * @param array                      $relations     关联数据信息
     * @param array                      $selectColumns 查询字段信息
     * @param string                     $table         表名称
     *
     * @return Builder|Model|QueryBuilder
     */
    public function getRelationModel($model, $relations, $selectColumns, $table)
    {
        // 没有关联信息
        if (empty($relations)) {
            return $this->select($model, $selectColumns, $table);
        }

        // 处理数据
        $notSelectAll = $this->notSelectAll($selectColumns, $table);
        $with         = $withCount = [];
        $findModel    = $model->getModel();

        // 开始解析关联关系
        foreach ($relations as $relation => $value) {
            // 判断relations 是否真的存在
            if (method_exists($findModel, $relation)) {

                // 获取默认查询条件
                $value['conditions'] = Arr::get($value, 'conditions', []);

                if (Arr::get($value, 'with')) {

                    // 获取关联的 $localKey or $foreignKey
                    list($localKey, $foreignKey) = $this->getRelationKeys($findModel->$relation());

                    // 防止关联查询，主键没有添加上去
                    if ($localKey && $notSelectAll && !in_array($localKey, $selectColumns)) {
                        array_push($selectColumns, $localKey);
                    }

                    // 标记外键,防止查询的时候漏掉该字段
                    $value['foreignKey'] = $foreignKey;
                    $with[$relation]     = $this->buildRelation($value);
                }

                if (Arr::get($value, 'withCount')) {
                    $withCount[$relation] = function ($query) use ($value) {
                        /* @var $query Builder */
                        $queryModel = $query->getModel();
                        return $this->handleConditionQuery(
                            $value['conditions'],
                            $query,
                            $queryModel->getTable(),
                            $this->getTableColumns($queryModel)
                        );
                    };
                }
            }
        }

        // 处理查询字段、添加关联、和关联统计
        return $this->select($model, $selectColumns, $table)->with($with)->withCount($withCount);
    }

    /**
     *
     * 获取关联的关系的 localKey 和 foreignKey
     *
     * @param HasOneOrMany|BelongsTo
     *
     * @return array [localKey, foreignKey]
     */
    public function getRelationKeys($relation)
    {
        // 确定关联类型
        if ($relation instanceof HasOneOrMany) {
            // 正向关联
            $localKey   = $relation->getQualifiedParentKeyName();
            $foreignKey = $relation->getQualifiedForeignKeyName();
        } else if ($relation instanceof BelongsTo) {
            // 反向关联

            // laravel 5.5 版本
            if (method_exists($relation, 'getQualifiedForeignKey')) {
                $localKey = $relation->getQualifiedForeignKey();
            } else if (method_exists($relation, 'getQualifiedForeignKeyName')) {
                // laravel 5.8 版本
                $localKey = $relation->getQualifiedForeignKeyName();
            } else {
                $localKey = null;
            }

            $foreignKey = $relation->getQualifiedOwnerKeyName();
        } else {
            $localKey = $foreignKey = null;
        }

        return [$localKey, $foreignKey];
    }

    /**
     * 查询处理
     *
     * @param array  $condition 查询条件
     * @param mixed  $query     查询对象
     * @param string $table     查询的表
     * @param array  $columns   查询的字段
     * @param bool   $or        是否 or 查询
     *
     * @return mixed
     */
    public function handleConditionQuery($condition, $query, $table, $columns, $or = false)
    {
        // 处理表关联信息
        $query = $this->handleJoinQuery($condition, $query, $table);

        // 处理其他的查询信息
        $query = $this->handleExtraQuery($condition, $query, $table, $columns);

        // 没有查询条件直接退出
        if (empty($condition)) {
            return $query;
        }

        // 去构建查询
        return $this->conditionQuery($condition, $query, $table, $columns, $or);
    }

    /**
     * 处理额外的自定义的查询条件
     *
     * @param array        $conditions 查询的条件
     * @param QueryBuilder $query      查询的对象
     * @param string       $table      查询的表格
     * @param array        $columns    查询的字段
     *
     * @return QueryBuilder|Builder|mixed
     */
    protected function handleExtraQuery(&$conditions, $query, $table, $columns)
    {
        // 添加指定了索引
        if ($forceIndex = Arr::pull($conditions, 'force')) {
            $query = $query->from(DB::raw("`{$table}` FORCE INDEX (`{$forceIndex}`)"));
        }

        // 设置了排序
        if ($orderBy = Arr::pull($conditions, 'order')) {
            $query = $this->orderBy($query, $orderBy, $table, $columns);
        }

        // 设置了分组
        if ($groupBy = Arr::pull($conditions, 'group')) {
            $query = $query->groupBy($groupBy);
        }

        return $query;
    }

    /**
     * 查询处理
     *
     * @param array                      $condition 查询条件
     * @param Builder|Model|QueryBuilder $query     查询对象
     * @param string                     $table     查询表名称
     * @param array                      $columns   查询的字段
     * @param bool                       $or        是否是or 查询默认false
     *
     * @return Model|Builder|QueryBuilder
     */
    public function conditionQuery($condition, $query, $table, $columns, $or = false)
    {
        foreach ($condition as $column => $bindValue) {
            // 自定义查询
            if ($bindValue instanceof Expression && is_int($column)) {
                $query = $query->whereRaw($bindValue);
                continue;
            }

            // or 、and 查询 || 允许数组查询 ['or' => [['status' => 1, 'age' => 2], ['status' => 2, 'age' => 5]]
            if (
                in_array(strtolower($column), ['or', 'and'], true) ||
                (is_int($column) && Helper::isAssociative($bindValue))
            ) {

                // 存在值才去查询
                if (is_array($bindValue) && $bindValue) {
                    $column = strtolower($column);
                    $method = $or ? 'orWhere' : 'where';
                    $query  = $query->{$method}(function ($query) use ($bindValue, $table, $columns, $column) {
                        $this->conditionQuery($bindValue, $query, $table, $columns, $column === 'or');
                    });
                }

                continue;
            }

            // 字段直接查询 field1 => value1
            if (isset($columns[$column])) {
                $query = $this->handleFieldQuery($query, $table . '.' . $column, $bindValue, $or);
                continue;
            }

            // 表达式查询 field1:neq => value1
            list($field, $expression) = array_pad(explode(':', $column, 2), 2, null);
            if ($field && $expression) {
                $field = isset($columns[$field]) ? $table . '.' . $field : $field;
                $query = $this->handleExpressionConditionQuery($query, [$field, $expression, $bindValue], $or);
                continue;
            }

            // join 表的查询 goods.name = 1 或者 config->merchant = true 的查询
            if (strrpos($column, '.') !== false || strrpos($column, '->') !== false) {
                $query = $this->handleFieldQuery($query, $column, $bindValue, $or);
                continue;
            }

            // scope 自定义查询
            try {
                $query = $query->{$column}($bindValue);
            } catch (\Exception $e) {
                $column = Str::camel($column);
                $query  = $query->{$column}($bindValue);
            }
        }

        return $query;
    }

    /**
     * 处理表达式查询
     *
     * @param Model|Builder|QueryBuilder $query
     * @param array                      $condition 查询对象 ['field', 'expression', 'value']
     * @param bool                       $or
     *
     * @return Model|Builder|QueryBuilder
     */
    public function handleExpressionConditionQuery($query, $condition = [], $or = false)
    {
        list($column, $expression, $value) = $condition;

        // 匹配两次，第一次直接使用表达式简写，第二次：直接使用表达式
        $allowExpression = Arr::get($this->expression, strtolower($expression));
        if (empty($allowExpression)) {
            if (in_array($expression, $this->expression)) {
                $allowExpression = $expression;
            }
        }

        // 两次都没有匹配到话，直接返回
        if (empty($allowExpression)) {
            return $query;
        }

        // 数组查询方式
        $strMethod = $or ? 'orWhere' : 'where';
        if (in_array($allowExpression, ['In', 'NotIn', 'Between', 'NotBetween'], true)) {
            $strMethod .= $allowExpression;
            return $query->{$strMethod}($column, (array)$value);
        }

        // 模糊查询
        if (in_array($allowExpression, ['like', 'not like'])) {
            $value = (string)$value;

            // 自动添加模糊查询
            if (in_array($expression, ['like', 'not like'], true) && strpos($value, '%') === false) {
                $value = "%{$value}%";
            }
        }

        return $query->{$strMethod}($column, $allowExpression, $value);
    }

    /**
     * 字段查询
     *
     * @param Model|Builder|QueryBuilder $query 查询对象
     * @param string                     $field 查询字段
     * @param mixed|array                $value 查询的值
     * @param bool                       $or    是否是or 查询
     *
     * @return Model|Builder|QueryBuilder
     */
    public function handleFieldQuery($query, $field, $value, $or = false)
    {
        $strMethod = is_array($value) ? 'whereIn' : 'where';
        if ($or) {
            $strMethod = 'or' . ucfirst($strMethod);
        }

        return $query->{$strMethod}($field, $value);
    }

    /**
     * 连表查询
     *
     * @param Builder|QueryBuilder $query  查询对象
     * @param array                $params 查询数据
     * @param string               $method 连表方式
     *
     * @return Builder||QueryBuilder
     */
    protected function join($query, $params, $method = 'join')
    {
        /**
         * 不是二维数组，处理为二维数组
         *
         * [['users', 'users.user_id', '=', 'order.user_id'], ['table', 'table.user_id', '=', 'order.user_id']]
         */
        if (!is_array(Arr::get($params, 0))) {
            $params = [$params];
        }

        // join 方式
        foreach ($params as $join) {
            $query = $query->{$method}(...$join);
        }

        return $query;
    }

    /**
     * 连表查询
     *
     * @param Builder|QueryBuilder $query     查询对象
     * @param string               $table     当前表名称
     * @param array                $relations 关联表方法名称
     * @param string               $method    连表方式
     *
     * @return Builder|QueryBuilder
     */
    protected function joinWith($query, $table, $relations, $method = 'join')
    {
        // 字符串转数组
        if (is_string($relations)) {
            $relations = [$relations];
        }

        $model  = $query->getModel();
        $number = 1;
        foreach ($relations as $key => $relation) {
            $relation = Str::camel($relation);
            // 判断relations 是否真的存在
            if (method_exists($model, $relation)) {

                /* @var $relationObject Relation */
                $relationObject = $model->$relation();

                // 获取关联的 $localKey or $foreignKey
                list($localKey, $foreignKey) = $this->getRelationKeys($relationObject);

                // 关联表的名称
                $joinTable = $relationObject->getQuery()->getModel()->getTable();

                // 使用了别名
                if (is_string($key)) {
                    $aliasTable = $key;
                } else if ($joinTable === $table) {
                    // 表重名的话、使用t1、t2代替
                    $aliasTable = 't' . $number;
                    $number++;
                } else {
                    $aliasTable = '';
                }

                $query = $this->join($query, [
                    $aliasTable ? $joinTable . ' as ' . $aliasTable : $joinTable,
                    $localKey,
                    '=',
                    ($aliasTable ? $aliasTable : $joinTable) . '.' . Str::replaceFirst($joinTable . '.', '', $foreignKey),
                ], $method);
            }
        }

        return $query;
    }

    /**
     * 处理查询条件中的 join 信息
     *
     * @param array                      $conditions 查询条件
     * @param Builder|QueryBuilder|Model $query      查询对象
     * @param string                     $table      查询的表名称
     *
     * @return Builder|QueryBuilder|Model
     */
    protected function handleJoinQuery(&$conditions, $query, $table)
    {
        // 设置关联join
        $joins = Arr::pull($conditions, 'join');
        if ($joins && is_array($joins)) {
            $query = $this->join($query, $joins);
        }

        // 设置关联leftJoin
        $leftJoins = Arr::pull($conditions, 'leftJoin');
        if ($leftJoins && is_array($leftJoins)) {
            $query = $this->join($query, $leftJoins, 'leftJoin');
        }

        // 设置关联rightJoin
        $rightJoin = Arr::pull($conditions, 'rightJoin');
        if ($rightJoin && is_array($rightJoin)) {
            $query = $this->join($query, $rightJoin, 'rightJoin');
        }

        // 设置关联joinWith
        if ($joinWiths = Arr::pull($conditions, 'joinWith')) {
            $query = $this->joinWith($query, $table, $joinWiths);
        }

        // 设置关联leftJoinWith
        if ($leftJoinWiths = Arr::pull($conditions, 'leftJoinWith')) {
            $query = $this->joinWith($query, $table, $leftJoinWiths, 'leftJoin');
        }

        // 设置关联rightJoinWith
        if ($rightJoinWiths = Arr::pull($conditions, 'rightJoinWith')) {
            $query = $this->joinWith($query, $table, $rightJoinWiths, 'rightJoin');
        }

        return $query;
    }

    /**
     * 添加关联查询
     *
     * @param array $relations 关联查询的条件和字段信息
     *
     * @return Closure
     */
    public function buildRelation($relations)
    {
        return function ($query) use ($relations) {
            // 获取relation的表字段
            /* @var $model Model */
            /* @var $query Builder|Model|QueryBuilder|Relation */
            $fields     = (array)Arr::get($relations, 'columns', []);
            $conditions = Arr::get($relations, 'conditions', []);
            if (empty($fields) && empty($conditions)) {
                return $query;
            }

            $model      = $query->getRelated();
            $table      = $model->getTable();
            $columns    = $this->getTableColumns($model);
            $foreignKey = Arr::get($relations, 'foreignKey');

            // 解析出查询条件和查询字段中的关联信息
            list($conditionRelations, $findConditions) = $this->parseConditionRelations($conditions);
            list($fieldRelations, $selectColumns) = $this->parseColumnRelations($fields, $table, $columns);

            // 处理关联信息查询
            $hasRelations = $this->getRelations($conditionRelations, $fieldRelations);

            // 添加关联的外键，防止关联不上
            if ($foreignKey && $this->notSelectAll($selectColumns, $table) && !in_array($foreignKey, $selectColumns)) {
                $selectColumns[] = $foreignKey;
            }

            $query = $this->getRelationModel($query, $hasRelations, $selectColumns, $table);

            // 处理查询条件
            return $this->handleConditionQuery($findConditions, $query, $table, $columns);
        };
    }

    /**
     * 查询字段信息(没有指定查询字段那么查询表全部字段)
     *
     * @param QueryBuilder|model|Builder $query   查询对象
     * @param array|string               $columns 查询的字段
     * @param string                     $table   查询的表
     *
     * @return QueryBuilder|model|Builder
     */
    public function select($query, $columns, $table = '')
    {
        if ($columns) {
            return $query->select($columns);
        }

        // 没有指定了字段但指定了表、那么查询表的全部字段
        return $table ? $query->select($table . '.*') : $query;
    }

    /**
     * 排序查询
     *
     * @param QueryBuilder|model|Builder $query   查询对象
     * @param string|array               $orderBy 排序信息
     * @param string                     $table   表名称
     * @param array                      $columns 表字段信息
     *
     * @return QueryBuilder|model|Builder
     */
    public function orderBy($query, $orderBy, $table, $columns)
    {
        // 为空，直接返回
        if (empty($orderBy)) {
            return $query;
        }

        // 处理多字段的排序情况
        $orders = is_array($orderBy) ? $orderBy : explode(',', (string)$orderBy);
        foreach ($orders as $order) {

            // 处理排序字段和排序方式
            $order     = trim($order);
            $tmpOrders = explode(' ', preg_replace('/\s+/', ' ', $order));
            list($column, $direction) = array_pad($tmpOrders, 2, null);

            if ($column && in_array(strtolower($direction), ['', 'asc', 'desc'])) {
                // 存在表中，添加表名称
                if (isset($columns[$column])) {
                    $column = $table . '.' . $column;
                }

                $query = $query->orderBy($column, $direction ?: 'desc');
            }
        }

        return $query;
    }

    /**
     * 获取查询单个字段数据信息
     *
     * @param string $column 查询的字段 （支持 parent.name ）
     *
     * @return array
     */
    public function getFieldArray($column)
    {
        $index = strpos($column, '.');
        if ($index === false) {
            return [$column];
        }

        $columnName  = substr($column, 0, $index);
        $columnValue = substr($column, $index + 1);

        return [$columnName => $this->getFieldArray($columnValue)];
    }

    /**
     * 没有查询全部字段
     *
     * @param array  $columns 查询的字段信息
     * @param string $table   表名称
     *
     * @return bool
     */
    public function notSelectAll($columns, $table)
    {
        return !empty($columns) && !in_array('*', $columns) && !in_array($table . '.*', $columns);
    }

    /**
     * 获取有效的新增和修改字段信息
     *
     * @param array  $data    新增或者修改的数据
     * @param array  $columns 表中的字段
     * @param string $primary 表的主键信息
     *
     * @return array
     */
    public function getValidColumns($data, $columns = null, $primary = null)
    {
        $columns = $columns ?: $this->getTableColumns();
        $primary = $primary ?: $this->model->getKeyName();

        // 不管是新增还是修改、不允许操作主键字段
        unset($columns[$primary]);

        return Arr::only($data, $columns);
    }

    /**
     * 调用 model 的方法
     *
     * @param string $method 调用model 自己的方法
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        // 直接使用 model, 不需要查询条件的数据
        if (in_array($method, $this->passThru)) {
            return (new $this->model)->{$method}(...$arguments);
        }

        // filter 系列函数
        if (Str::startsWith($method, 'filter')) {
            // 过滤方法查询
            $method       = lcfirst(substr($method, 6));
            $arguments[0] = $this->filterCondition(Arr::get($arguments, 0, []));
            return $this->{$method}(...$arguments);
        }

        // 第一个参数传递给自己 newBuilder 方法
        $conditions = Arr::pull($arguments, 0, []);

        // 处理查询字段信息
        if (in_array($method, $this->columnMethods, true)) {
            $columns = Arr::pull($arguments, 1, []); // 查询的字段信息
        } else {
            $columns = [];
        }

        return $this->newBuilder($conditions, $columns)->{$method}(...$arguments);
    }
}
