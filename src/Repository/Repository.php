<?php

namespace Littlebug\Repository;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Littlebug\Traits\ResponseTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Exception;
use ReflectionClass;
use Closure;
use Littlebug\Helpers\Helper;
use \Illuminate\Database\Eloquent\Relations\Relation;
use \Illuminate\Database\Query\Expression;

/**
 * Class Repository 基础Repository类
 *
 * @method Model|null first($conditions = [], $columns = [])
 * @method Collection get($conditions = [], $columns = [])
 * @method Collection pluck($conditions = [], $columns = [], $key = null)
 * @method int count($conditions = [])
 * @method int|mixed max($conditions = [], $column)
 * @method int|mixed min($conditions = [], $column)
 * @method int|mixed avg($conditions = [], $column)
 * @method int|mixed sum($conditions = [], $column)
 * @method string toSql($conditions = [])
 * @method array|mixed getBindings($conditions = [])
 *
 * @method array|mixed getConnection()
 * @method boolean insert(array $insert)
 * @method int|mixed insertGetId(array $insert)
 *
 * @package Littlebug\Repository
 */
abstract class Repository
{
    use ResponseTrait;

    /**
     * The model to provide.
     *
     * @var Model|Builder
     */
    protected $model;

    /**
     * @var array 不需要查询条件的方法
     */
    protected $passthru = ['insert', 'insertGetId', 'getConnection'];

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
        'like'        => 'LIKE',
        'not_like'    => 'NOT LIKE',
        'not like'    => 'NOT LIKE',
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
     * @param $model
     *
     * @return array
     */
    public function getTableColumns($model = '')
    {
        $model   = $model && is_object($model) ? $model : $this->model;
        $columns = [];
        foreach ($model->columns as $column) {
            $columns[$column] = $column;
        }

        return $columns;
    }

    /**
     * 新增数据
     *
     * @param array $data 新增的数据
     *
     * @return array
     */
    final public function create($data)
    {
        // 不能是空数组
        if (empty($data) || !is_array($data)) {
            return $this->error('创建失败');
        }

        try {
            // 创建数据
            if (!$model = $this->model->create($data)) {
                return $this->error('创建失败');
            }

            return $this->success($model->toArray());
        } catch (Exception $e) {
            return $this->error($this->getError($e));
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
    final public function update($conditions, $data)
    {
        // 根据pk更新单条记录
        $conditions = $this->getPrimaryKeyCondition($conditions);
        if (empty($conditions)) {
            return $this->error('未指定修改条件');
        }

        // 过滤非法字段，禁止更新主键
        $columns = $this->getTableColumns();
        Arr::pull($data, $this->model->getKeyName());
        foreach ($data as $k => $v) {
            if (!isset($columns[$k])) {
                unset($data[$k]);
            }
        }

        // 空值判断
        if (empty($data)) {
            return $this->error('未指定更新字段');
        }

        try {
            $rows = $this->findCondition($conditions)->update($data);
            return $this->success($rows, '更新成功');
        } catch (Exception $e) {
            return $this->error($this->getError($e));
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
            $affected_rows = $this->findCondition($conditions)->delete();
            return $this->success($affected_rows, '删除成功');
        } catch (Exception $e) {
            return $this->error($this->getError($e));
        }
    }

    /**
     * 查询一条数据
     *
     * @param array|mixed $conditions 查询条件
     * @param array       $fields     查询字段
     *
     * @return mixed
     */
    public function find($conditions, $fields = [])
    {
        /* @var $item Model|object|static|null */
        if ($item = $this->findCondition($conditions, $fields)->first()) {
            return $item->toArray();
        }

        return false;
    }

    /**
     *
     * 获取一条记录的单个字段结果
     *
     * @param mixed|array $conditions 查询条件
     * @param string      $field      获取的字段
     *
     * @return bool|mixed
     */
    public function findBy($conditions, $field)
    {
        // 如果误传数组的话 取数组第一个值
        $field = $this->firstKey($field);
        $item  = $this->find($conditions, $this->firstField($field));
        return Arr::get($item, $field, false);
    }

    /**
     * 查询所有记录
     *
     * @param array|mixed $conditions 查询条件
     * @param array       $fields     查询字段
     *
     * @return array
     */
    public function findAll($conditions = [], $fields = [])
    {
        return $this->findCondition($conditions, $fields)->get()->toArray();
    }

    /**
     *
     * 获取结果集里的单个字段所有值的数组
     *
     * @param mixed|array $conditions 查询条件
     * @param string      $field      获取的字段
     *
     * @return array
     */
    public function findAllBy($conditions, $field)
    {
        // 如果误传数组的话 取数组第一个值
        $field = $this->firstKey($field);
        if (!$data = $this->findAll($conditions, $this->firstField($field))) {
            return [];
        }

        $columns = [];
        foreach ($data as $value) {
            $columns[] = Arr::get($value, $field);
        }

        return $columns;
    }

    /**
     * 过滤查询中的空值查询一条数据
     *
     * @param array|int|string $conditions 查询条件
     * @param array            $fields     查询的字段
     *
     * @return mixed
     */
    public function filterFind($conditions, $fields = [])
    {
        return $this->find($this->filterCondition($conditions), $fields);
    }

    /**
     * 过滤查询中的空值 查询所有记录
     *
     * @param array|mixed $conditions 查询条件
     * @param array       $fields     查询字段
     *
     * @return array
     */
    public function filterFindAll($conditions, $fields = [])
    {
        return $this->findAll($this->filterCondition($conditions), $fields);
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
     * @param array $fields     查询字段
     * @param int   $size       每页数据数
     * @param int   $current    当前页
     *
     * @return mixed
     */
    public function paginate($conditions = [], $fields = [], $size = 10, $current = null)
    {
        $model = $this->findCondition($conditions, $fields);
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
     * @param array $fields     查询字段
     *
     * @return Model|mixed
     */
    public function findCondition($conditions = [], $fields = [])
    {
        $model = $this->model->newModelInstance();
        // 查询条件为空，直接返回
        if (empty($conditions) && empty($fields)) {
            return $model;
        }

        // 查询条件为
        $conditions = $this->getPrimaryKeyCondition($conditions);
        $table      = $model->getTable();
        $columns    = $this->getTableColumns($model);
        $fields     = (array)$fields;

        // 解析出查询条件和查询字段中的关联信息
        list($conditionRelations, $findConditions) = $this->parseConditionRelations($conditions);
        list($fieldRelations, $selectColumns) = $this->parseFieldRelations($fields, $table, $columns);

        // 处理关联信息查询
        $relations = $this->getRelations($conditionRelations, $fieldRelations);
        $model     = $this->getRelationModel($model, $relations, $selectColumns);

        // 处理查询条件
        return $this->handleConditionQuery($findConditions, $model, $table, $columns);
    }

    /**
     * 解析查询条件中的关联关系
     *
     * @param array $conditions 查询条件
     *
     * @return array
     */
    protected function parseConditionRelations($conditions)
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
     * @param array  $fields       查询的字段信息
     * @param string $table        查询的表
     * @param array  $tableColumns 表中存在的字段信息
     *
     * @return array
     */
    protected function parseFieldRelations($fields, $table, $tableColumns)
    {
        $relations = $columns = [];
        if (empty($fields)) {
            return [$relations, $columns];
        }

        // 解析查询字段信息
        foreach ($fields as $k => $field) {
            if (is_int($k) && is_string($field)) { // 第一步，判断字段是否为字符串
                // 判断是否存在表中
                if (isset($tableColumns[$field])) {
                    $columns[] = $table . '.' . $field;
                } elseif (Str::endsWith($field, '_count')) {
                    $relationName = Str::replaceLast('_count', '', $field);
                    if (!isset($relations[$relationName])) {
                        $relations[$relationName] = ['withCount' => true, 'columns' => [], 'with' => false];
                    }

                    $relations[$relationName]['withCount'] = true;
                } else {
                    $columns[] = $field;
                }
            } elseif (!is_int($k) && is_string($k)) { // 如果是key => value 格式 那么认为是 关联查询
                $relationName = Str::camel($k);
                if (!isset($relations[$relationName])) {
                    $relations[$relationName] = ['withCount' => false, 'columns' => [], 'with' => true];
                }

                $relations[$relationName]['columns'] = $field;
                $relations[$relationName]['with']    = true;

            } elseif ($field instanceof Expression) { // 表达式查询字段
                $columns[] = $field;
            }
        }

        return [$relations, $columns];
    }

    /**
     * 获取关系信息
     *
     * @param array $conditionRelations 有查询条件的关联信息
     * @param array $fieldRelations     有字段查询关联信息
     *
     * @return array
     */
    protected function getRelations(array $conditionRelations, array $fieldRelations)
    {
        $relations = [];
        foreach ($conditionRelations as $relationName => $conditions) {
            $relations[$relationName] = ['conditions' => $conditions, 'with' => true];
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
     *
     * @return Builder|Model
     */
    protected function getRelationModel($model, $relations, $selectColumns)
    {
        // 没有关联信息
        if (empty($relations)) {
            return $this->select($model, $selectColumns);
        }

        // 处理数据
        $isNotSelectAll = $this->isNotSelectAll($selectColumns);
        $with           = $withCount = [];
        $findModel      = $model->getModel();

        // 开始解析关联关系
        foreach ($relations as $relation => $value) {
            // 判断relations 是否真的存在
            if (method_exists($findModel, $relation)) {

                /* @var $relationModel HasOneOrMany */
                $relationModel = $findModel->$relation();

                // 防止关联查询，主键没有添加上去
                if ($isNotSelectAll) {
                    $localKey = $relationModel->getQualifiedParentKeyName();
                    if (!in_array($localKey, $selectColumns)) {
                        array_push($selectColumns, $localKey);
                    }
                }

                // 获取默认查询条件
                $defaultConditions   = $this->getRelationDefaultFilters($model, $relation);
                $value['conditions'] = array_merge($defaultConditions, Arr::get($value, 'conditions', []));
                if ($value['with']) {
                    // 标记外键,防止查询的时候漏掉该字段
                    $value['foreignKey'] = $relationModel->getQualifiedForeignKeyName();
                    $with[$relation]     = $this->buildRelation($value);
                }

                if ($value['withCount']) {
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

        if ($with) {
            $model = $model->with($with);
        }

        // 查询关联的统计信息，那么不需要查询字段信息
        if ($withCount) {
            $selectColumns = [];
            $model         = $model->withCount($withCount);
        }

        return $this->select($model, $selectColumns);
    }

    /**
     * 过滤查询条件
     *
     * @param mixed|array $condition 查询条件
     *
     * @return mixed
     */
    protected function filterCondition($condition)
    {
        if (!is_array($condition)) {
            return $condition;
        }

        foreach ($condition as $key => $value) {
            if (strtolower($key) === 'or') {
                $condition[$key] = $this->filterCondition($value);
            }

            if (Helper::isEmpty($value)) {
                unset($condition[$key]);
            }
        }

        return $condition;
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
    protected function handleConditionQuery($condition, $query, $table, $columns)
    {
        // 添加指定了索引
        if ($force_index = Arr::pull($condition, 'force_index')) {
            $query = $query->from(DB::raw("{$this->model->getTable()} FORCE INDEX ({$force_index})"));
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
    protected function conditionQuery($condition, $query, $table, $columns, $or = false)
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
    protected function handleExpressionConditionQuery($query, $condition = [], $or = false)
    {
        list($column, $expression, $value) = $condition;
        if ($expression = Arr::get($this->expression, strtolower($expression))) {
            $strMethod = $or ? 'orWhere' : 'where';
            if (in_array($expression, ['In', 'NotIn', 'Between', 'NotBetween'])) {
                $strMethod .= $expression;
                $query     = $query->{$strMethod}($column, (array)$value);
            } else {
                if (in_array($expression, ['LIKE', 'NOT LIKE'])) {
                    $value = (string)$value;
                }

                $query = $query->{$strMethod}($column, $expression, $value);
            }
        }

        return $query;
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
    protected function handleFieldQuery($query, $field, $value, $or = false)
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
    protected function getError(Exception $e)
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
    private function getRelationDefaultFilters($model, $relationName)
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
    private function buildRelation($relations)
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
            list($fieldRelations, $selectColumns) = $this->parseFieldRelations($fields, $table, $columns);

            // 处理关联信息查询
            $hasRelations = $this->getRelations($conditionRelations, $fieldRelations);

            // 添加关联的外键，防止关联不上
            if ($this->isNotSelectAll($selectColumns) && !in_array($foreignKey, $selectColumns)) {
                $selectColumns[] = $foreignKey;
            }

            $query = $this->getRelationModel($query, $hasRelations, $selectColumns);

            // 处理查询条件
            return $this->handleConditionQuery($findConditions, $query, $table, $columns);
        };
    }

    /**
     * 查询字段信息
     *
     * @param mixed|model $query  查询对象
     * @param array       $fields 查询的字段
     *
     * @return mixed
     */
    private function select($query, $fields)
    {
        if ($fields) {
            return $query->select($fields);
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
    private function orderBy($query, $orderBy, $table, $columns)
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
    private function firstKey($mixedValue)
    {
        if (is_array($mixedValue)) {
            $mixedValue = Arr::get(array_values($mixedValue), 0);
        }

        return (string)$mixedValue;
    }

    /**
     * 获取查询单个字段数据信息
     *
     * @param string $field 查询的字段 （支持 parent.name ）
     *
     * @return array
     */
    private function firstField($field)
    {
        $index = strpos($field, '.');
        if ($index === false) {
            return [$field];
        }

        $field_name  = substr($field, 0, $index);
        $field_value = substr($field, $index + 1);

        return [$field_name => $this->firstField($field_value)];
    }

    /**
     * 是否查询全部字段
     *
     * @param array $columns 查询的字段信息
     *
     * @return bool
     */
    private function isNotSelectAll($columns)
    {
        return !empty($columns) && !in_array('*', $columns);
    }

    /**
     * 调用 model 的方法
     *
     * @param string $name 调用model 自己的方法
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        // 直接使用 model, 不需要查询条件的数据
        if (in_array($name, $this->passthru)) {
            return (new $this->model)->{$name}(...$arguments);
        }

        // 第一个参数传递给自己 findCondition 方法
        $conditions = Arr::pull($arguments, 0, []);
        return $this->findCondition($conditions)->{$name}(...$arguments);
    }
}
