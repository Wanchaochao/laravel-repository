<?php

namespace Littlebug\Repository;

use Closure;
use Exception;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Littlebug\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

/**
 * Class Repository 基础Repository类
 *
 * @method Model|null first($conditions = [], $columns = [])
 * @method Collection get($conditions = [], $columns = [])
 * @method Collection pluck($conditions, $column, $key = null)
 * @method int count($conditions = [])
 * @method int|mixed max($conditions, $column)
 * @method int|mixed min($conditions, $column)
 * @method int|mixed avg($conditions, $column)
 * @method int|mixed sum($conditions, $column)
 * @method string toSql($conditions = [])
 * @method array|mixed getBindings($conditions = [])
 *
 * @method array|mixed getConnection()
 * @method boolean insert(array $values)
 * @method int|mixed insertGetId(array $values, $sequence = null)
 * @method Model firstOrCreate(array $attributes, array $value = [])
 * @method Model firstOrNew(array $attributes, array $value = [])
 * @method Model updateOrCreate(array $attributes, array $value = [])
 * @method Model findOrFail($id, $columns = ['*'])
 * @method Model findOrNew($id, $columns = ['*'])
 * @method Collection findMany($ids, $columns = ['*'])
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
        'insert', 'insertGetId', 'getConnection',
        'firstOrCreate', 'firstOrNew', 'updateOrCreate',
        'findOrFail', 'findOrNew', 'findMany',
    ];

    /**
     * 分页样式
     * @var string
     */
    private $paginateStyle = 'default';

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
        'auto_like'   => 'like',
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
        // 没有查询条件
        if (empty($conditions)) {
            return $conditions;
        }

        // 标量(数字、字符、布尔值)查询, 处理为主键查询
        if (is_scalar($conditions)) {
            if ($this->model->getKeyType() == 'int') {
                $conditions = intval($conditions);
            }

            return [$this->model->getKeyName() => $conditions];
        } elseif (is_array($conditions) && !Helper::isAssociative($conditions)) {
            // 或者不是关联数组查询，也处理为主键查询
            return [$this->model->getKeyName() => array_values($conditions)];
        }

        return (array)$conditions;
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
     * 新增数据
     *
     * @param array $data 新增的数据
     *
     * @return array
     */
    final public function create(array $data)
    {
        // 过滤非法字段，禁止新增主键
        $data = $this->getValidColumns($data);

        // 不能是空数组
        if (empty($data) || !is_array($data)) {
            return $this->error('创建失败');
        }

        try {

            // 执行新增数据，并执行前置、后置方法
            $new = $this->runEventFunction(function ($data) {
                // 创建数据
                if (!$model = $this->model->create($data)) {
                    throw new Exception('创建失败');
                }

                return $model->toArray();
            }, 'create', $data);

            return $this->success($new, '创建成功');
        } catch (Exception $e) {
            return $this->error($this->getError($e), null);
        }
    }

    /**
     * 修改数据
     *
     * @param array|mixed $conditions 修改的查询条件
     * @param array       $data       修改的数据
     *
     * @return array
     */
    final public function update($conditions, array $data)
    {
        // 根据pk更新单条记录
        $conditions = $this->getPrimaryKeyCondition($conditions);
        if (empty($conditions)) {
            return $this->error('未指定修改条件');
        }

        // 过滤非法字段，禁止更新主键
        $data = $this->getValidColumns($data);

        // 空值判断
        if (empty($data)) {
            return $this->error('未指定更新字段');
        }

        try {

            // 执行修改，并且执行前置和后置方法
            $rows = $this->runEventFunction(function ($conditions, $data) {
                // 主键
                $primary = $this->getModel()->getKeyName();

                // 主键修改一条数据
                if (isset($conditions[$primary]) && is_scalar($conditions[$primary])) {
                    /* @var $model Model */
                    if ($model = $this->findCondition($conditions)->first()) {
                        return $model->update($data) ? 1 : 0;
                    }
                }

                return $this->findCondition($conditions)->update($data);
            }, 'update', $conditions, $data);

            return $this->success($rows, '更新成功');
        } catch (Exception $e) {
            return $this->error($this->getError($e), 0);
        }
    }

    /**
     * 删除数据
     *
     * @param mixed|array $conditions 删除的条件
     *
     * @return array
     */
    final public function delete($conditions)
    {
        // 查询条件处理
        $conditions = $this->getPrimaryKeyCondition($conditions);
        if (empty($conditions)) {
            return $this->error('未指定删除条件');
        }

        try {

            // 执行删除，并且执行前置和后置方法
            $rows = $this->runEventFunction(function ($conditions) {
                return $this->findCondition($conditions)->delete();
            }, 'delete', $conditions);

            return $this->success($rows, '删除成功');
        } catch (Exception $e) {
            return $this->error($this->getError($e), 0);
        }
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
     * @return mixed
     */
    public function find($conditions = [], $columns = [])
    {
        /* @var $item Model|object|static|null */
        if ($item = $this->findCondition($conditions, $columns)->first()) {
            return $item->toArray();
        }

        return false;
    }

    /**
     *
     * 获取一条记录的单个字段结果
     *
     * @param mixed|array $conditions 查询条件
     * @param string      $column     获取的字段
     *
     * @return bool|mixed
     */
    public function findBy($conditions, $column)
    {
        // 如果误传数组的话 取数组第一个值
        $column = $this->firstKey($column);
        $item   = $this->find($conditions, $this->firstField($column));
        return Arr::get($item, $column, false);
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
        return $this->findCondition($conditions, $columns)->get()->toArray();
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
        // 如果误传数组的话 取数组第一个值
        $column = $this->firstKey($column);
        if (!$data = $this->findAll($conditions, $this->firstField($column))) {
            return [];
        }

        $columns = [];
        foreach ($data as $value) {
            $columns[] = Arr::get($value, $column);
        }

        return $columns;
    }

    /**
     * 过滤查询中的空值查询一条数据
     *
     * @param array|int|string $conditions 查询条件
     * @param array            $columns    查询的字段
     *
     * @return mixed
     */
    public function filterFind($conditions, $columns = [])
    {
        return $this->find($this->filterCondition($conditions), $columns);
    }

    /**
     * 过滤查询中的空值 查询所有记录
     *
     * @param array|mixed $conditions 查询条件
     * @param array       $columns    查询字段
     *
     * @return array
     */
    public function filterFindAll($conditions, $columns = [])
    {
        return $this->findAll($this->filterCondition($conditions), $columns);
    }

    /**
     * 过滤获取分页列表
     *
     * @param array $conditions 查询条件
     * @param array $columns    查询字段
     * @param int   $size       每页数据数
     * @param int   $current    当前页
     *
     * @return mixed
     */
    public function filterPaginate($condtions = [], $columns = [], $size = 10, $current = null)
    {
        return $this->paginate($this->filterCondition($condtions), $columns, $size, $current);
    }

    /**
     * 获取过滤查询条件查询的 model
     *
     * @param array|mixed $conditions 查询条件
     * @param array       $columns    查询的字段
     *
     * @return Model|mixed
     */
    public function getFilterModel($conditions, $columns = [])
    {
        return $this->findCondition($this->filterCondition($conditions), $columns);
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

        foreach ($conditions as $key => $value) {
            if (strtolower($key) === 'or') {
                $conditions[$key] = $this->filterCondition($value);
            }

            if (Helper::isEmpty($value)) {
                unset($conditions[$key]);
            }
        }

        return $conditions;
    }

    /**
     * 设置分页样式，目前支持simple和default
     *
     * @param string $style
     *
     * @return $this
     */
    public function setPaginateStyle($style)
    {
        $this->paginateStyle = $style;
        return $this;
    }

    /**
     * 获取分页列表
     *
     * @param array $conditions 查询条件
     * @param array $columns    查询字段
     * @param int   $size       每页数据数
     * @param int   $current    当前页
     *
     * @return mixed
     */
    public function paginate($conditions = [], $columns = [], $size = 10, $current = null)
    {
        $model = $this->findCondition($conditions, $columns);
        if ($this->paginateStyle == 'simple') {
            $paginate = $model->simplePaginate($size, ['*'], 'page', $current);
        } else {
            $paginate = $model->paginate($size, ['*'], 'page', $current);
        }

        /* @var $paginate Paginator */
        $items = $paginate->items();
        foreach ($items as &$value) {
            /* @var $value Model */
            $value = $value->toArray();
        }
        unset($value);

        return [
            'items' => $items,
            'pager' => $paginate,
        ];
    }

    /**
     * 设置model 的查询信息
     *
     * @param array $conditions 查询条件
     * @param array $columns    查询字段
     *
     * @return Model|mixed
     */
    public function findCondition($conditions = [], $columns = [])
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
            $index = strpos($field, '.');
            if ($index === false) {
                $findConditions[$field] = $value;
            } else {
                // 处理关联名称
                $relationName = substr($field, 0, $index);
                $fieldName    = substr($field, $index + 1);
                $relationName = Str::camel($relationName);
                if (!isset($relations[$relationName])) {
                    $relations[$relationName] = [];
                }

                $relations[$relationName][$fieldName] = $value;
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
                // 判断是否存在表中
                if (isset($tableColumns[$field])) {
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
        $relations = [];
        foreach ($conditionRelations as $relationName => $conditions) {
            $relations[$relationName] = ['conditions' => $conditions, 'with' => true, 'withCount' => false];
        }

        foreach ($fieldRelations as $relationName => $relation) {
            $relations[$relationName] = array_merge($relation, Arr::get($relations, $relationName, []));
        }

        return $relations;
    }

    /**
     * 获取处理查询关联的 model
     *
     * @param Model|Builder $model         查询的model
     * @param array         $relations     关联数据信息
     * @param array         $selectColumns 查询字段信息
     * @param string        $table         表名称
     *
     * @return Builder|Model
     */
    public function getRelationModel($model, $relations, $selectColumns, $table)
    {
        // 没有关联信息
        if (empty($relations)) {
            return $this->select($model, $selectColumns);
        }

        // 处理数据
        $isNotSelectAll = $this->isNotSelectAll($selectColumns, $table);
        $with           = $withCount = [];
        $findModel      = $model->getModel();

        // 开始解析关联关系
        foreach ($relations as $relation => $value) {
            // 判断relations 是否真的存在
            if (method_exists($findModel, $relation)) {

                // 获取默认查询条件
                $defaultConditions   = $this->getRelationDefaultFilters($model, $relation);
                $value['conditions'] = array_merge($defaultConditions, Arr::get($value, 'conditions', []));

                if ($value['with']) {

                    // 获取关联的 $localKey or $foreignKey
                    list($localKey, $foreignKey) = $this->getRelationKeys($findModel->$relation());

                    // 防止关联查询，主键没有添加上去
                    if ($localKey && $isNotSelectAll && !in_array($localKey, $selectColumns)) {
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

        // 先处理查询字段
        $model = $this->select($model, $selectColumns);

        // 存在关联
        if ($with) {
            $model = $model->with($with);
        }

        // 存在统计关联
        if ($withCount) {
            return $model->withCount($withCount);
        }

        return $model;
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
     *
     * @return mixed
     */
    public function handleConditionQuery($condition, $query, $table, $columns)
    {
        // 添加指定了索引
        if ($forceIndex = Arr::pull($condition, 'force')) {
            $query = $query->from(DB::raw("{$this->model->getTable()} FORCE INDEX ({$forceIndex})"));
        }

        // 设置了排序
        if ($orderBy = Arr::pull($condition, 'order')) {
            $query = $this->orderBy($query, $orderBy, $table, $columns);
        }

        // 设置了limit
        if ($limit = Arr::pull($condition, 'limit')) {
            $query = $query->limit(intval($limit));
        }

        // 设置了offset
        if ($offset = Arr::pull($condition, 'offset')) {
            $query = $query->offset(intval($offset));
        }

        // 设置了分组
        if ($groupBy = Arr::pull($condition, 'group')) {
            $query = $query->groupBy($groupBy);
        }

        // 没有查询条件直接退出
        if (empty($condition)) {
            return $query;
        }

        return $this->conditionQuery($condition, $query, $table, $columns);
    }

    /**
     * 查询处理
     *
     * @param array  $condition 查询条件
     * @param mixed  $query     查询对象
     * @param string $table     查询表名称
     * @param array  $columns   查询的字段
     * @param bool   $or        是否是or 查询默认false
     *
     * @return Model|mixed
     */
    public function conditionQuery($condition, $query, $table, $columns, $or = false)
    {
        foreach ($condition as $column => $bindValue) {
            // or 查询
            if (strtolower($column) === 'or' && is_array($bindValue) && $bindValue) {
                $query = $query->where(function ($query) use ($bindValue, $table, $columns) {
                    $this->conditionQuery($bindValue, $query, $table, $columns, true);
                });

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
                $query = $this->handleExpressionConditionQuery($query, [$table . '.' . $field, $expression, $bindValue], $or);
                continue;
            }

            // 自定义 scope 查询
            if (is_a($query, Model::class)) {
                $strMethod = 'scope' . ucfirst($column);
                if (!method_exists($query, $strMethod)) {
                    $strMethod = 'scope' . ucfirst(Str::camel($column));
                    $strMethod = method_exists($query, $strMethod) ? $strMethod : null;
                }

                if ($strMethod) {
                    $query = $query->{$strMethod}($query, $bindValue);
                }

                continue;
            }

            // scope 自定义查询
            try {
                $query = $query->{$column}($bindValue);
            } catch (Exception $e) {
                try {
                    $column = Str::camel($column);
                    $query  = $query->{$column}($bindValue);
                } catch (Exception $e) {
                }
            }
        }

        return $query;
    }

    /**
     * 处理表达式查询
     *
     * @param Model $query
     * @param array $condition 查询对象
     *                         ['field', 'expression', 'value']
     * @param bool  $or
     *
     * @return Model
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
        if (in_array($allowExpression, ['In', 'NotIn', 'Between', 'NotBetween'])) {
            $strMethod .= $allowExpression;
            return $query->{$strMethod}($column, (array)$value);
        }

        // 其他查询方式
        if (in_array($allowExpression, ['like', 'not like'])) {
            $value = (string)$value;

            // 不存在模糊查询，自动添加模糊查询
            if ($expression === 'auto_like' && strpos($value, '%') === false) {
                $value = "%{$value}%";
            }
        }

        return $query->{$strMethod}($column, $allowExpression, $value);
    }

    /**
     * 字段查询
     *
     * @param model       $query 查询对象
     * @param string      $field 查询字段
     * @param mixed|array $value 查询的值
     * @param bool        $or    是否是or 查询
     *
     * @return mixed
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
     *
     * 根据运行环境上报错误
     *
     * @param Exception $e
     *
     * @return mixed|string
     */
    public function getError(Exception $e)
    {
        // 记录数据库执行错误日志
        logger()->error('db error', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        return app()->environment('production') ? '系统错误，请重试' : $e->getMessage();
    }

    /**
     * 获取关联查询的默认查询条件
     *
     * @param model  $model        查询的model
     * @param string $relationName 关联查询字段
     *
     * @return array
     */
    public function getRelationDefaultFilters($model, $relationName)
    {
        // 添加relation的默认条件，默认条件数组为 “$relationFilters" 的 public 属性
        $attribute = $relationName . 'Filters';
        if (isset($model->{$attribute}) && is_array($model->{$attribute})) {
            return $model->{$attribute};
        }

        // 不是 public 属性，可能是 protected 属性，通过反射获取
        $conditions = [];
        try {
            $properties = (new ReflectionClass($model))->getDefaultProperties();
            $value      = Arr::get($properties, $attribute, Arr::get($properties, strtolower($attribute)));
            if ($value && is_array($value)) {
                $conditions = $value;
            }
        } catch (Exception $e) {
        }

        return $conditions;
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
            /* @var $query Relation */
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
            if ($foreignKey && $this->isNotSelectAll($selectColumns, $table) && !in_array($foreignKey, $selectColumns)) {
                $selectColumns[] = $foreignKey;
            }

            $query = $this->getRelationModel($query, $hasRelations, $selectColumns, $table);

            // 处理查询条件
            return $this->handleConditionQuery($findConditions, $query, $table, $columns);
        };
    }

    /**
     * 查询字段信息
     *
     * @param mixed|model|Builder $query   查询对象
     * @param array               $columns 查询的字段
     *
     * @return mixed
     */
    public function select($query, $columns)
    {
        if ($columns) {
            return $query->select($columns);
        }

        return $query;
    }

    /**
     * 排序查询
     *
     * @param mixed|model|Builder $query   查询对象
     * @param string|array        $orderBy 排序信息
     * @param string              $table   表名称
     * @param array               $columns 表字段信息
     *
     * @return mixed
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
     * 获取传入的当个字段信息
     *
     * @param $mixedValue
     *
     * @return string
     */
    public function firstKey($mixedValue)
    {
        if (is_array($mixedValue)) {
            $mixedValue = Arr::get(array_values($mixedValue), 0);
        }

        return (string)$mixedValue;
    }

    /**
     * 获取查询单个字段数据信息
     *
     * @param string $column 查询的字段 （支持 parent.name ）
     *
     * @return array
     */
    public function firstField($column)
    {
        $index = strpos($column, '.');
        if ($index === false) {
            return [$column];
        }

        $columnName  = substr($column, 0, $index);
        $columnValue = substr($column, $index + 1);

        return [$columnName => $this->firstField($columnValue)];
    }

    /**
     * 是否没有查询全部字段
     *
     * @param array  $columns 查询的字段信息
     * @param string $table   表名称
     *
     * @return bool
     */
    public function isNotSelectAll($columns, $table)
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
        // 过滤非法字段，禁止更新主键
        Arr::pull($data, $primary);
        foreach ($data as $k => $v) {
            if (!isset($columns[$k])) {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * 成功返回
     *
     * @param array  $data    返回数据信息
     * @param string $message 返回提示信息
     *
     * @return array
     */
    public function success($data = [], $message = 'ok')
    {
        return [true, $message, $data];
    }

    /**
     * 失败返回
     *
     * @param string $message 错误提示信息
     * @param array  $data    返回数据信息
     *
     * @return array
     */
    public function error($message = 'error', $data = [])
    {
        return [false, $message, $data];
    }

    /**
     * 通过数组查询
     *
     * @param array $where   查询的条件
     *
     * @param array $columns 查询的字段信息
     *
     * @return Model|\Illuminate\Database\Query\Builder
     */
    public function findWhere(array $where, array $columns = [])
    {
        $model = $this->model->newModelInstance();

        // 查询条件为空，直接返回
        if (empty($where) && empty($columns)) {
            return $model;
        }

        $table        = $model->getTable();
        $tableColumns = $this->getTableColumns();

        list($fieldRelations, $selectColumns) = $this->parseColumnRelations($columns, $table, $tableColumns);

        // 处理关联信息查询
        $relations = $this->getRelations([], $fieldRelations);
        $model     = $this->getRelationModel($model, $relations, $selectColumns, $table);

        // 返回处理 $where 查询条件的 model
        return $this->getWhereQuery($model, $where, $table, $tableColumns);
    }

    /**
     * 处理 where 添加查询
     *
     * @param Model|\Illuminate\Database\Query\Builder $model   查询的model
     * @param array                                    $where   查询的条件
     * @param string                                   $table   查询的表
     * @param array                                    $columns 查询的字段信息
     * @param bool                                     $or      是否or查询
     *
     * @return Model|\Illuminate\Database\Query\Builder
     */
    public function getWhereQuery($model, array $where, $table, $columns, $or = false)
    {
        // 没有查询条件直接返回
        if (empty($where)) {
            return $model;
        }

        // 第一步：获取第一个元素 是否指定连接方式
        $firstWhere = array_shift($where);
        if (is_string($firstWhere)) {
            return $this->getWhereQuery($model, $where, $table, $columns, strtolower($firstWhere) == 'or');
        }

        // 第二步：第一个元素不是连接方式，那么就是查询条件了，需要添加上去
        array_unshift($where, $firstWhere);
        $method = $or ? 'orWhere' : 'where';
        foreach ($where as $value) {

            // 关联数组处理 ['name' => 2, 'age' => 1] or ['name:like' => 'test', 'age' => 2]
            if (Helper::isAssociative($value)) {

                // 关联数组处理
                foreach ($value as $valColumn => $valValue) {
                    // 当作 ['name:like' => 123] 处理 key
                    $columnExpressions = explode(':', $valColumn);

                    // 处理查询字段和表达式 表达式默认为 =
                    $column     = Arr::get($columnExpressions, 0);
                    $expression = Arr::get($columnExpressions, 1, is_array($valValue) ? 'in' : '=');
                    $column     = isset($columns[$column]) ? $table . '.' . $column : $column;
                    $condition  = [$column, $expression, $valValue];

                    // 直接查询
                    $model = $this->handleExpressionConditionQuery($model, $condition, $or);
                }


                continue;
            }

            // ['and', ['name' => 1], ['age' => 2]] or [['name' => 1], ['age' => 2]] 循环处理
            list($column) = $value;
            if (is_array($column) || (is_string($column) && in_array(strtolower($column), ['or', 'and']))) {
                $model = $model->{$method}(function ($q) use ($value, $table, $columns) {
                    return $this->getWhereQuery($q, $value, $table, $columns);
                });

                continue;
            }

            // 只有 ['name', '=', 1] 才处理
            if (count($value) === 3) {
                // 字段查询条件表名称
                if (isset($columns[$column])) {
                    $value[0] = $table . '.' . $column;
                }

                // 处理表达式查询
                $model = $this->handleExpressionConditionQuery($model, $value, $or);
            }
        }

        return $model;
    }

    /**
     * 调用 model 的方法
     *
     * @param string $name 调用model 自己的方法
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // 直接使用 model, 不需要查询条件的数据
        if (in_array($name, $this->passThru)) {
            return (new $this->model)->{$name}(...$arguments);
        }

        // 第一个参数传递给自己 findCondition 方法
        $conditions = Arr::pull($arguments, 0, []);
        return $this->findCondition($conditions)->{$name}(...$arguments);
    }
}
