<?php

namespace Littlebug\Repository;

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
use \Illuminate\Database\Query\Expression;
use \Illuminate\Database\Eloquent\Relations\Relation;

/**
 * This is the abstract repository class.
 *
 * @author Graham Campbell <graham@alt-three.com>
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
    public function all($conditions, $fields = [])
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
        if (!$data = $this->all($conditions, $this->firstField($field))) {
            return [];
        }

        $columns = [];
        foreach ($data as $value) {
            $columns[] = Arr::get($value, $field);
        }

        return $columns;
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
        return $this->all($conditions, $fields);
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
        return $this->all($this->filterCondition($conditions), $fields);
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
    public function lists($conditions = [], $fields = [], $size = 10, $current = null)
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
     *
     * 返回查询条件编译后的sql语句
     *
     * @param $filters
     *
     * @return mixed
     */
    public function toSql($filters)
    {
        return $this->findCondition($filters)->toSql();
    }

    /**
     *
     * 获取统计信息
     *
     * @param $filters
     *
     * @return mixed
     */
    public function count($filters)
    {
        return $this->findCondition($filters)->count();
    }

    /**
     * 获取最大值
     *
     * @param $filters
     * @param $field
     *
     * @return mixed
     */
    public function max($filters, $field)
    {
        return $this->findCondition($filters)->max($field);
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
        $model    = $model && is_object($model) ? $model : $this->model;
        $_columns = [];
        foreach ($model->columns as $column) {
            $_columns[$column] = $column;
        }

        return $_columns;
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
        // 查询条件为
        $conditions = $this->getPrimaryKeyCondition($conditions);
        $model      = $this->model->newModelInstance();
        $table      = $this->model->getTable();
        $columns    = $this->getTableColumns($model);
        $fields     = (array)$fields;

        // 分组，如果是relation的查询条件，需要放在前面build
        $relation_condition = $model_condition = [];
        foreach ($conditions as $field => $value) {
            list($relation_name, $relation_key) = array_pad(explode('.', $field, 2), 2, null);
            if ($relation_name && $relation_key) {
                $relation_condition[$field] = $value;
            } else {
                $model_condition[$field] = $value;
            }
        }

        // 存在关联查询，自己查询字段中，需要加入主键查询
        $primary = null;
        // 首先设置relation查询，不能放在后面执行
        if ($relations = $this->getRelations($model, $fields, $relation_condition)) {
            $primary = $model->getKeyName();
            $model   = $model->with($relations);
        }

        // 判断是否有关联模型的统计操作
        $model = $this->addRelationCountSelect($model, $fields, $relation_condition);
        unset($relations, $relation_name, $relation_filters);

        $model = $this->select($model, $fields, $table, $columns, $primary);
        return $this->handleConditionQuery($model_condition, $model, $table, $columns);
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
                $condition[$key] = $value = $this->filterCondition($value);
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
        if ($order_by = Arr::pull($condition, 'order')) {
            $query = $this->orderBy($query, $order_by, $table, $columns);
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
        foreach ($condition as $column => $bind_value) {
            // or 查询
            if (strtolower($column) === 'or' && is_array($bind_value) && $bind_value) {
                $query = $query->where(function ($query) use ($bind_value, $table, $columns) {
                    $this->conditionQuery($bind_value, $query, $table, $columns, true);
                });

                continue;
            }

            // 字段直接查询 field1 => value1
            if (isset($columns[$column])) {
                $query = $this->handleFieldQuery($query, $table . '.' . $column, $bind_value, $or);
                continue;
            }

            // 表达式查询 field1:neq => value1
            list($field, $expression) = array_pad(explode(':', $column, 2), 2, null);
            if ($field && $expression) {
                $query = $this->handleExpressionConditionQuery($query, [$table . '.' . $field, $expression, $bind_value], $or);
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
                    $query = $query->{$strMethod}($query, $bind_value);
                }

                continue;
            }

            // scope 自定义查询
            try {
                $query = $query->{$column}($bind_value);
            } catch (Exception $e) {
                try {
                    $column = ucfirst(Str::camel($column));
                    $query = $query->{$column}($bind_value);
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
            if (in_array($expression, ['In', 'NotIn', 'Between', 'NotBetween'])) {
                $strMethod = $or ? 'orWhere' . $expression : 'where' . $expression;
                $query->{$strMethod}($column, (array)$value);
            } else {
                $strMethod = $or ? 'orWhere' : 'where';
                if (in_array($expression, ['LIKE', 'NOT LIKE'])) {
                    $value = '%' . (string)$value . '%';
                }

                $query->{$strMethod}($column, $expression, $value);
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
     * 获取关联表集合
     *
     * @param model        $model              查询的model
     * @param string|array $fields             查询的字段
     * @param array        $relation_condition 关联查询的条件
     *
     * @return array
     */
    protected function getRelations($model, $fields, $relation_condition)
    {
        // relation查询时，合并SQL，优化语句
        $relations = $relation_fields = [];
        if ($fields) {
            foreach ($fields as $field => $val) {
                if (!is_int($field)) {

                    // 这里查询字段需要处理下 比如: user_name => userName
                    $field = Str::camel($field);

                    // Model对象存在这个方法
                    if (method_exists($model, $field)) {
                        $relations[$field]       = [];
                        $relation_fields[$field] = $val;
                    }
                }
            }

            unset($field, $field);
        }

        // 关联查询 roles.user_id = 1
        if ($relation_condition) {
            foreach ($relation_condition as $relation_key => $relation_value) {

                // 获取第一个 . 出现的位置，可能存在 users.parent.status=1 的情况
                $index = strpos($relation_key, '.');
                if ($index === false) {
                    continue;
                }

                // 这里查询字段需要处理下 比如: user_name => userName
                $relation_name  = Str::camel(substr($relation_key, 0, $index));
                $relation_field = substr($relation_key, $index + 1);

                // 如果relation存在
                if (method_exists($model, $relation_name)) {
                    // 未初始化时先初始化为空数组
                    if (!isset($relations[$relation_name])) {
                        $relations[$relation_name] = [];
                    }

                    $relations[$relation_name][$relation_field] = $relation_value;
                }
            }
        }

        // 当前model实际要绑定的relation
        $bind_relations = [];
        if ($relations) {
            foreach ($relations as $relation_name => $relation_filters) {
                $bind_relations[$relation_name] = $this->buildRelation(
                    array_merge(
                        $this->getRelationDefaultFilters($model, $relation_name),
                        (array)$relation_filters
                    ),
                    (array)Arr::get($relation_fields, $relation_name)
                );
            }
        }

        return $bind_relations;
    }

    /**
     * 获取关联查询的默认查询条件
     *
     * @param model  $model         查询的model
     * @param string $relation_name 关联查询字段
     *
     * @return array
     */
    private function getRelationDefaultFilters($model, $relation_name)
    {
        // 添加relation的默认条件，默认条件数组为“$relationFilters"的public属性
        $filter_attribute = $relation_name . 'Filters';
        if (isset($model->$filter_attribute) && is_array($model->$filter_attribute)) {
            return $model->$filter_attribute;
        }

        $relation_data = [];
        try {
            // 由于PHP类属性区分大小写，而relation_count字段为小写，利用反射将属性转为小写，再进行比较
            if (!$pros = (new ReflectionClass($model))->getDefaultProperties()) {
                foreach ($pros as $name => $val) {
                    if (strtolower($name) == strtolower($filter_attribute) && is_array($val)) {
                        $relation_data = $val;
                    }
                }
            }
        } catch (Exception $e) {
        }

        return $relation_data;
    }

    /**
     * 添加关联查询
     *
     * @param array $relation_filters 关联查询的条件
     * @param array $relation_fields  关联查询的字段信息
     *
     * @return Closure
     */
    private function buildRelation($relation_filters, $relation_fields)
    {
        return function ($query) use ($relation_filters, $relation_fields) {
            // 获取relation的表字段
            /* @var $model Model */
            /* @var $query Relation */
            $model   = $query->getRelated();
            $columns = $this->getTableColumns($model);
            $table   = $model->getTable();

            // relation绑定
            if ($relations = $this->getRelations($model, $relation_fields, $relation_filters)) {
                $query = $query->with($relations);
            }

            // 判断是否有关联模型的统计操作
            if ($relation_fields) {
                $query = $this->addRelationCountSelect($query, $relation_fields, $relation_filters);
            }

            $query = $this->select($query, $relation_fields, $table, $columns, $model->getKeyName());
            return $this->handleConditionQuery($relation_filters, $query, $table, $columns);
        };
    }

    /**
     * 添加关联查询的统计
     *
     * @param mixed|model $model            查询的model
     * @param array       $fields           查询的字段信息
     * @param array       $relation_filters 关联查询的字段信息
     *
     * @return mixed
     */
    private function addRelationCountSelect($model, $fields, $relation_filters)
    {
        $filters = [];
        if ($relation_filters) {
            foreach ($relation_filters as $filter => $value) {
                list($a, $b) = array_pad(explode('.', $filter, 2), 2, null);
                if ($a && $b) {
                    $a = strtolower($a);
                    if (!isset($filters[$a])) {
                        $filters[$a] = [];
                    }
                    $filters[$a][$b] = $value;
                }
            }

            unset($filter, $value);
        }

        $relations_count = [];
        if ($fields) {
            foreach ($fields as $__k => $__f) {
                if (is_int($__k) && is_string($__f)) {
                    $count_key     = substr($__f, -6);
                    $relation_name = strtolower(substr($__f, 0, -6));
                    if ($count_key == '_count' && method_exists($model->getModel(), $relation_name)) {
                        // 当前模型的关联模型
                        $sub_model = $model->getModel()->$relation_name()->getRelated();
                        $columns   = $this->getTableColumns($sub_model);
                        /* @var $sub_model Model */
                        $table = $sub_model->getTable();

                        // 关联模型的查询条件
                        $cur_filters = array_merge(
                            $this->getRelationDefaultFilters($model->getModel(), $relation_name),
                            (array)Arr::get($filters, $relation_name)
                        );

                        $relations_count[$relation_name] = function ($query) use ($cur_filters, $columns, $table) {
                            return $this->handleConditionQuery($cur_filters, $query, $table, $columns);
                        };
                    }
                }

                unset($count_key, $relation_name, $__k, $__f, $sub_model, $columns, $table);
            }
        }

        if ($relations_count) {
            $model = $model->withCount($relations_count);
        }

        return $model;
    }

    /**
     *
     * 根据运行环境上报错误
     *
     * @param Exception $e
     *
     * @return mixed|string
     */
    private function getError(Exception $e)
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
     * 查询字段信息
     *
     * @param mixed|model          $query        查询对象
     * @param array                $fields       查询的字段
     * @param string               $table        表名称
     * @param array                $columns      表字段信息
     * @param string|boolean|array $relation_key 关联表主键
     *
     * @return mixed
     */
    private function select($query, $fields, $table, $columns = [], $relation_key = false)
    {
        $select     = [];
        $use_select = true;
        foreach ($fields as $i => $field) {
            if ($field == '*') {
                continue;
            }

            if (is_int($i) && is_string($field)) {
                $select[] = isset($columns[$field]) ? $table . '.' . $field : $field;
                if (substr($field, -6) === '_count') {
                    $use_select = false;
                    break;
                }
            } elseif ($field instanceof Expression) {
                $select[] = $field;
            }
        }

        // 需要查询和查询字段存在的情况下查询字段
        if ($use_select && $select) {
            // 关联查询必须添加自己的组件在里面
            if ($relation_key && !in_array($relation_key, $fields)) {
                array_push($select, $table . '.' . $relation_key);
            }

            return $query->select($select);
        }

        return $query;
    }

    /**
     * 排序查询
     *
     * @param mixed|model $query    查询对象
     * @param string      $order_by 排序信息
     * @param string      $table    表名称
     * @param array       $columns  表字段信息
     *
     * @return mixed
     */
    private function orderBy($query, $order_by, $table, $columns)
    {
        if ($orders = explode(',', $order_by)) {
            foreach ($orders as $order) {
                $order = trim($order);
                list($k, $v) = array_pad(explode(' ', preg_replace('/\s+/', ' ', $order)), 2, null);
                if ($k && in_array(strtolower($v), ['', 'asc', 'desc'])) {
                    if (!isset($columns[$k])) {
                        $table = null;
                    }

                    $query = $query->orderBy($table ? $table . '.' . $k : $k, $v ?: 'desc');
                }
            }
        }

        return $query;
    }

    /**
     * 获取传入的当个字段信息
     *
     * @param $mixed_value
     *
     * @return string
     */
    private function firstKey($mixed_value)
    {
        if (is_array($mixed_value)) {
            $mixed_value = Arr::get(array_values($mixed_value), 0);
        }

        return (string)$mixed_value;
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
}
