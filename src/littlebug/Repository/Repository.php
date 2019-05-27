<?php

namespace Littlebug\Repository;

use Illuminate\Support\Facades\DB;
use Littlebug\Traits\ResponseTrait;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Exception;
use ReflectionClass;
use Closure;
use Littlebug\Helpers\Helper;
use \Illuminate\Database\Query\Expression;

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
    private $paginate_style = 'default';

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
        if (!is_array($data) || !$data) {
            return $this->error('操作失败');
        }

        try {

            if (!$model = $this->model->create($data)) {
                return $this->error('操作失败');
            }

            return $this->success($model->toArray());
        } catch (Exception $e) {
            return $this->error($this->getError($e));
        }
    }

    /**
     * 修改数据
     *
     * @param array|mixed $update_condition 修改的查询条件
     * @param array       $update_data      修改的数据
     *
     * @return array
     */
    final public function update($update_condition, $update_data)
    {
        // 根据pk更新单条记录
        if (is_scalar($update_condition) && preg_match('/^\d+$/', $update_condition)) {
            $update_condition = [$this->model->getKeyName() => $update_condition];
        }

        // 过滤非法字段，禁止更新PK
        $columns = $this->getTableColumns();
        foreach ($update_data as $k => $v) {
            if ($k == $this->model->getKeyName() || !isset($columns[$k])) {
                unset($update_data[$k]);
            }
        }

        // 空值判断
        if (empty($update_data)) {
            return $this->error('未指定要更新的字段信息');
        }

        // 只能单表查询
        if (is_array($update_condition) && $update_condition) {
            foreach ($update_condition as $k => $v) {
                if (!isset($columns[$k])) {
                    unset($update_condition[$k]);
                }
            }
        }

        if (!$update_condition) {
            return $this->error('未指定当前更新的条件');
        }

        try {
            /* @var $model mixed */
            $model = $this->model->newInstance();
            $rows  = $model->where($update_condition)->update($model->fill($update_data)->getAttributes());

            // 更新成功要调用清除缓存方法
            if ($rows && method_exists($this, 'clearCache')) {
                $this->clearCache($update_condition);
            }

            return $this->success($rows, '更新成功');
        } catch (Exception $e) {
            return $this->error($this->getError($e));
        }
    }

    /**
     * 删除数据
     *
     * @param mixed|array $id_or_array 删除的条件
     *
     * @return array
     */
    final public function delete($id_or_array)
    {
        // id 转换
        if (is_scalar($id_or_array) && preg_match('/^\d+$/', $id_or_array)) {
            $filters = [$this->model->getKeyName() => $id_or_array];
        } else {
            $filters = $id_or_array;
        }

        try {
            // 如果在后面，数据被删除，无法获取数据结果，导致缓存无法清除
            if (method_exists($this, 'clearCache')) {
                $this->clearCache($id_or_array);
            }

            $affected_rows = $this->model->where($filters)->delete();
            return $this->success($affected_rows, '操作成功');
        } catch (Exception $e) {
            return $this->error($this->getError($e));
        }
    }

    /***
     *
     * 自定义sql返回pager对象(eg:select a.*,b.*,c.* from a,b,c where a.id =b.a_id and b.aid=c.id)
     *
     * @param       $items
     * @param       $total
     * @param int   $per_page
     * @param int   $curr_page
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function getPager($items, $total, $per_page = 10, $curr_page = 1, array $options = [])
    {
        if (!$options) {
            $options = [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ];
        }
        return new LengthAwarePaginator($items, $total, $per_page, $curr_page, $options);
    }

    /**
     * 查询一条数据
     *
     * @param array|mixed $filters 查询条件
     * @param array       $fields  查询字段
     *
     * @return mixed
     */
    public function find($filters, $fields = [])
    {
        // 如果是单个数值，则自动转换为PK条件查询
        if (!is_array($filters)) {
            $filters = [$this->model->getKeyName() => (int)$filters];
        }

        /* @var $item Model|object|static|null */
        if ($item = $this->setModelCondition($filters, $fields)->first()) {
            return $item->toArray();
        }

        return false;
    }

    /**
     *
     * 获取一条记录的单个字段结果
     *
     * @param mixed|array $filters 查询条件
     * @param string      $field   获取的字段
     *
     * @return bool|mixed
     */
    public function findBy($filters, $field)
    {
        // 如果误传数组的话 取数组第一个值
        if (is_array($field)) {
            $field = Arr::get($field, 0);
        }

        $item = $this->find($filters, [$field]);
        return Arr::get($item, $field, false);
    }

    /**
     *
     * 获取结果集里的单个字段所有值的数组
     *
     * @param mixed|array $filters 查询条件
     * @param string      $field   获取的字段
     *
     * @return array
     */
    public function findAllBy($filters, $field)
    {
        // 如果误传数组的话 取数组第一个值
        if (is_array($field)) {
            $field = Arr::get($field, 0);
        }

        $data = $this->all($filters, [$field]);
        return array_column($data, $field);
    }

    /**
     * 查询所有记录
     *
     * @param array|mixed $filters 查询条件
     * @param array       $fields  查询字段
     *
     * @return array
     */
    public function all($filters, $fields = [])
    {
        return $this->setModelCondition($filters, $fields)->get()->toArray();
    }

    /**
     * 过滤查询中的空值 查询所有记录
     *
     * @param array|mixed $filters 查询条件
     * @param array       $fields  查询字段
     *
     * @return array
     */
    public function filterFindAll($filters, $fields = [])
    {
        return $this->all($this->filterCondition($filters), $fields);
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
        $this->paginate_style = $style;
        return $this;
    }

    /**
     * 获取列表
     *
     * @param array $filters
     * @param int   $page_size
     * @param array $fields
     * @param int   $cur_page
     *
     * @return mixed
     */
    public function lists($filters = [], $fields = [], $page_size = 10, $cur_page = null)
    {
        $model = $this->setModelCondition($filters, $fields);
        if ($this->paginate_style == 'simple') {
            $paginate = $model->simplePaginate($page_size, ['*'], 'page', $cur_page);
        } else {
            $paginate = $model->paginate($page_size, ['*'], 'page', $cur_page);
        }

        /* @var $paginate Paginator */
        return [
            'items' => $paginate->items(),
            'pager' => $paginate,
        ];
    }

    /**
     * 获取database实例
     *
     * @param string $connection
     *
     * @return ConnectionInterface
     */
    public function getDB($connection = 'default')
    {
        return DB::connection($connection);
    }

    /****
     *
     * 返回查询条件编译后的sql语句
     *
     * @param $filters
     *
     * @return mixed
     */
    public function toSql($filters)
    {
        $model = $this->setModelCondition($filters);
        return $model->toSql();
    }

    /***
     *
     * 获取统计信息
     *
     * @param $filters
     *
     * @return mixed
     */
    public function count($filters)
    {
        return $this->setModelCondition($filters)->count();
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
        return $this->setModelCondition($filters)->max($field);
    }

    /**
     * 获取主键查询条件
     *
     * @param $condition
     *
     * @return array
     */
    public function getPrimaryKeyCondition($condition)
    {
        if (is_scalar($condition)) {
            if ($this->model->getKeyType() == 'int') {
                $condition = intval($condition);
            }

            $condition = [
                $this->model->getKeyName() => $condition,
            ];
        }

        return (array)$condition;
    }

    /****
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
     * @param mixed|model $query   查询对象
     * @param array       $fields  查询的字段
     * @param string      $table   表名称
     * @param array       $columns 表字段信息
     *
     * @return mixed
     */
    private function select($query, $fields, $table, $columns = [])
    {
        $select     = [];
        $use_select = true;
        foreach ($fields as $i => $field) {
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

        if ($use_select) {
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

                    $query->orderBy($table ? $table . '.' . $k : $k, $v ?: 'desc');
                }
            }
        }

        return $query;
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
        // 设置了排序
        if ($order_by = array_pull($condition, 'order')) {
            $this->orderBy($query, $order_by, $table, $columns);
        }

        // 设置了limit
        if ($limit = array_pull($condition, 'limit')) {
            $query->limit(intval($limit));
        }

        // 设置了offset
        if ($offset = array_pull($condition, 'offset')) {
            $query->offset(intval($offset));
        }

        // 设置了分组
        if ($groupBy = array_pull($condition, 'group_by')) {
            $query->groupBy($groupBy);
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
                $this->handleFieldQuery($query, $table . '.' . $column, $bind_value, $or);
                continue;
            }

            // 表达式查询 field1:neq => value1
            list($field, $expression) = array_pad(explode(':', $column, 2), 2, null);
            if ($field && $expression) {
                $this->handleExpressionConditionQuery($query, [$table . '.' . $field, $expression, $bind_value], $or);
                continue;
            }

            // 自定义 scope 查询
            if (is_a($query, Model::class)) {
                $strMethod = 'scope' . ucfirst($column);
                if (!method_exists($query, $strMethod)) {
                    $strMethod = 'scope' . ucfirst(camel_case($column));
                    $strMethod = method_exists($query, $strMethod) ? $strMethod : null;
                }

                if ($strMethod) {
                    $query->{$strMethod}($query, $bind_value);
                }

                continue;
            }

            // scope 自定义查询
            try {
                $query->{$column}($bind_value);
            } catch (Exception $e) {
                try {
                    $column = ucfirst(camel_case($column));
                    $query->{$column}($bind_value);
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
     * 设置model 的查询信息
     *
     * @param array $conditions 查询条件
     * @param array $fields     查询字段
     *
     * @return Model|mixed
     */
    public function setModelCondition($conditions = [], $fields = [])
    {
        // 查询条件为空，直接返回
        $conditions = $this->getPrimaryKeyCondition($conditions);
        $model      = $this->model;
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

        // 首先设置relation查询，不能放在后面执行
        if ($relations = $this->getRelations($model, $fields, $relation_condition)) {
            $model = $model->with($relations);
        }

        // 判断是否有关联模型的统计操作
        $model = $this->addRelationCountSelect($model, $fields, $relation_condition);
        unset($relations, $relation_name, $relation_filters);

        $model = $this->select($model, $fields, $table, $columns);
        return $this->handleConditionQuery($model_condition, $model, $table, $columns);
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
        $relations = [];
        if ($fields) {
            foreach ($fields as $field_key => $field_val) {
                if (!is_int($field_key)) {
                    // Model对象
                    if (method_exists($model, ucfirst($field_key))) {
                        $relations[$field_key] = [];
                    }
                }
            }

            unset($field_key, $field_val);
        }

        // 关联查询 roles.user_id = 1
        if ($relation_condition) {
            foreach ($relation_condition as $relation_key => $relation_value) {
                $dot_index = strpos($relation_key, '.');
                if ($dot_index !== false) {
                    $relation_name  = substr($relation_key, 0, $dot_index);
                    $relation_field = substr($relation_key, $dot_index + 1);
                } else {
                    $relation_name  = '';
                    $relation_field = $relation_key;
                }

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
                    (array)Arr::get($fields, $relation_name)
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
            //由于PHP类属性区分大小写，而relation_count字段为小写，利用反射将属性转为小写，再进行比较
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
            $model   = $query->getRelated();
            $columns = $this->getTableColumns($model);
            $table   = $model->getTable();

            // relation绑定
            if ($relations = $this->getRelations($model, $relation_fields, $relation_filters)) {
                $query = $query->with($relations);
            }

            // 判断是否有关联模型的统计操作
            if ($relation_fields) {
                $this->addRelationCountSelect($query, $relation_fields, $relation_filters);
            }

            $this->select($query, $relation_fields, $table);
            $this->handleConditionQuery($relation_filters, $query, $table, $columns);
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
}
