<?php
/*
 * This file is part of Laravel Credentials.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Littlebug\Repository;

use Littlebug\Traits\Repository\CacheTrait;
use Littlebug\Traits\Repository\RepositoryResponseTrait;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use DB;
use Illuminate\Support\Str;
use Exception;
use ReflectionClass;
use Closure;

/**
 * This is the abstract repository class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
abstract class Repository
{

    use CacheTrait;
    use RepositoryResponseTrait;

    /**
     * The model to provide.
     *
     * @var Model|Builder
     */
    protected $model;

    /***
     *
     * 获取列表集合的最大长度，防止内存溢出
     * eg:不分页一次性获取所有终端的信息(大于18000多条)，内存瞬间就爆了
     *
     * @var int
     */
    private $max_collection_size = 10000;

    /**
     * 分页样式
     * @var string
     */
    private $paginate_style = 'default';

    /***
     *
     * 数据库执行方式
     *
     * @var array
     */
    private $db_modes = [
        'master' => '/*TDDL:MASTER*/',
        'slave'  => '/*TDDL:SLAVE*/',
    ];

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
        'not like'    => 'NOT LIKE'
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
    public function get($filters, $fields = [])
    {
        //直接走db的情况:
        //１強制指定force_db字段
        // ２如果自定乂了field字段,即不是全部返回所有字段

        //如果是单个数值，则自动转换为PK条件查询
        if (!is_array($filters)) {
            $filters = [
                $this->model->getKeyName() => (int)$filters,
            ];
        }

        $c = count($fields);
        if (Arr::get($filters, 'force_db', false) == true || ($c == 1 && $fields[0] == '*') || $c > 1) {
            return $this->realGet($filters, $fields);
        }

        //灵活性,有缓存配置,走缓存,否则走db
        list($result, $data, $callback) = $this->searchCache($filters);
        //缓存命中
        if ($result == $this->cache_hit) {
            return $data;
        }
        //走db
        $return_data = $this->realGet($filters, $fields);
        //如果缓存未命中,需要调用缓存设置方法
        if ($return_data && $result == $this->cache_miss && is_object($callback) && get_class($callback) == 'Closure') {
            $callback($return_data);
        }
        return $return_data;
    }

    /**
     * 查询一条数据
     *
     * @param array|mixed $filters 查询条件
     * @param array       $fields  查询字段
     *
     * @return mixed
     */
    public function one($filters, $fields = [])
    {
        return $this->setModelFilter($filters, $fields)->first();
    }

    /**
     * 查询一条数据
     *
     * @param array|mixed $filters 查询条件
     * @param array       $field   查询字段
     *
     * @return mixed
     */
    public function findOne($filters, $field = [])
    {
        return $this->get($filters, $field);
    }

    /**
     * 自动过滤查询中空值查询一条数据
     *
     * @param       $filters
     * @param array $fields
     *
     * @return mixed
     */
    public function filterFindOne($filters, $fields = [])
    {
        return $this->get($this->filterCondition($filters), $fields);
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
    public function getOne($filters, $field)
    {
        //如果误传数组的话 取数组第一个值
        if (is_array($field)) {
            $field = Arr::get($field, 0);
        }

        $item = $this->setModelFilter($filters, [$field])->first();

        return $item ? Arr::get($item, $field) : false;
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
    public function findColumn($filters, $field)
    {
        return $this->getOne($filters, $field);
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
    public function getOneAll($filters, $field)
    {
        //如果误传数组的话 取数组第一个值
        if (is_array($field)) {
            $field = Arr::get($field, 0);
        }

        $data        = $this->setModelFilter($filters, [$field])->get();
        $return_data = [];
        if ($data) {
            foreach ($data as $item) {
                if ($item) {
                    $v             = $this->getItemInfo($item, [$field]);
                    $return_data[] = array_pop($v);
                }
            }
        }

        return $return_data;
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
    public function findAllColumn($filters, $field)
    {
        return $this->getOneAll($filters, $field);
    }

    /**
     * 查询所有记录
     *
     * @param array|mixed $filters 查询条件
     * @param array       $fields  查询字段
     *
     * @return array
     */
    public function getAll($filters, $fields = [])
    {
        if (is_array($filters)) {
            // 最大查询数据条数 $this->max_collection_size = 10000
            $filters['limit'] = min($this->max_collection_size, Arr::get($filters, 'limit', $this->max_collection_size));
        }

        $data        = $this->setModelFilter($filters, $fields)->get();
        $return_data = [];
        if ($data) {
            foreach ($data as $item) {
                if ($item) {
                    $return_data[] = $this->getItemInfo($item, $fields);
                }
            }
        }

        return $return_data;
    }

    /**
     * 查询所有记录
     *
     * @param array|mixed $filters 查询条件
     * @param array       $fields  查询字段
     *
     * @return array
     */
    public function findAll($filters, $fields = [])
    {
        return $this->getAll($filters, $fields);
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
        return $this->getAll($this->filterCondition($filters), $fields);
    }

    /**
     * 查询多条数据
     *
     * @param mixed|array $filters 查询条件
     * @param array       $fields  查询字段
     *
     * @return mixed
     */
    public function all($filters, $fields = [])
    {
        if (is_array($filters)) {
            // 最大查询数据条数 $this->max_collection_size = 10000
            $filters['limit'] = min($this->max_collection_size, Arr::get($filters, 'limit', $this->max_collection_size));
        }

        return $this->setModelFilter($filters, $fields)->get();
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
    public function getList($filters = [], $fields = [], $page_size = 10, $cur_page = null)
    {
        $model = $this->setModelFilter($filters, $fields);
        if ($this->paginate_style == 'simple') {
            $paginate = $model->simplePaginate($page_size, ['*'], 'page', $cur_page);
        } else {
            $paginate = $model->paginate($page_size, ['*'], 'page', $cur_page);
        }

        //要返回的分页数据
        $result          = [];
        $result['items'] = [];
        $result['pager'] = $paginate;

        //返回数据转换
        /* @var $paginate mixed */
        if ($paginate->items()) {
            foreach ($paginate->items() as $item) {
                if ($item) {
                    $result['items'][] = $this->getItemInfo($item, $fields);
                }
            }
        }

        return $result;
    }

    /**
     * 查询数据条数和分页查询，需要自己传递 offset 和 limit
     *
     * @param array $filters 查询条件
     * @param array $fields  查询的字段
     *
     * @return array
     */
    public function findCountAndItems($filters, $fields = [])
    {
        // 处理分页搜索
        $limit = intval(isset($filters['limit']) ? Arr::pull($filters, 'limit', 10) : request()->input('page_size', 10));
        if (isset($filters['offset'])) {
            $offset = (int)Arr::pull($filters, 'offset', 0);
        } else {
            $page   = max(request()->input('page', 1), 1);
            $offset = ($page - 1) * $limit;
        }

        $model = $this->setModelFilter($filters, $fields);

        // 没有数据直接返回
        if (!$count = $model->count()) {
            return [0, []];
        }

        // 存在数据才去limit查询查询
        $return_data = [];
        if ($data = $model->offset($offset)->limit($limit)->get()) {
            foreach ($data as $item) {
                if ($item) {
                    $return_data[] = $this->getItemInfo($item, $fields);
                }
            }
        }

        return [$count, $return_data];
    }

    /**
     * 自动过滤查询条件中的空值查询分页数据
     *
     * @param array $filters   查询条件
     * @param array $fields    查询的字段信息
     * @param int   $page_size 每页数据条数
     * @param null  $cur_page  当前页面
     *
     * @return mixed
     */
    public function filterFindList($filters = [], $fields = [], $page_size = 10, $cur_page = null)
    {
        return $this->getList($this->filterCondition($filters), $fields, $page_size, $cur_page);
    }

    /**
     * 获取database实例
     * @param string $connection
     * @return ConnectionInterface
     */
    public function getDB($connection = 'default')
    {
        return DB::connection($connection);
    }

    /****
     *
     * 自定义SQL查询
     *
     * @param $sql
     * @param $binds
     * @param $connection
     *
     * @return mixed
     */
    public function getBySql($sql, $binds = [], $connection = 'default')
    {
        $ret = $this->getAllBySql($sql, $binds, $connection);
        return $ret ? Arr::get($ret, '0') : [];
    }

    public function fetchRow($sql, $binds = [], $connection = 'default')
    {
        return $this->getBySql($sql, $binds, $connection);
    }

    /****
     *
     * 获取所有记录
     *
     * @param        $sql
     * @param array  $binds
     * @param string $connection
     *
     * @return array
     */
    public function getAllBySql($sql, $binds = [], $connection = 'default')
    {
        $sql = $this->protectSelectStatement($sql);
        $ret = $this->getDB($connection)->select($sql, $binds);
        return $ret;
    }

    public function fetchAll($sql, $binds = [], $connection = 'default')
    {
        return $this->getAllBySql($sql, $binds, $connection);
    }

    /**
     *
     * 通过sql查询一条记录的一个字段
     *
     * @param string $sql        查询的sql
     * @param array  $binds      绑定的参数
     * @param string $field      查询的字段
     * @param string $connection 使用的数据库连接
     *
     * @return bool|mixed
     */
    public function getOneBySql($sql, $binds = [], $field = '', $connection = 'default')
    {
        $ret  = $this->getAllBySql($sql, $binds, $connection);
        $data = false;
        if ($ret) {
            $row = Arr::get($ret, '0');
            //如果指定字段，则返回指定内容
            if ($field) {
                $data = Arr::get($row, $field);
            } else {
                $data = array_shift($row);
            }
        }
        return $data;
    }

    /**
     *
     * 通过sql查询一条记录的一个字段
     *
     * @param string $sql        查询的sql
     * @param array  $binds      绑定的参数
     * @param string $field      查询的字段
     * @param string $connection 使用的数据库连接
     *
     * @return bool|mixed
     */
    public function fetchOne($sql, $binds = [], $field = '', $connection = 'default')
    {
        return $this->getOneBySql($sql, $binds, $field, $connection);
    }

    /**
     *
     * 通过sql查询一条记录的一个字段
     *
     * @param string $sql        查询的sql
     * @param array  $binds      绑定的参数
     * @param string $field      查询的字段
     * @param string $connection 使用的数据库连接
     *
     * @return bool|mixed
     */
    public function findColumnBySql($sql, $binds = [], $field = '', $connection = 'default')
    {
        return $this->getOneBySql($sql, $binds, $field, $connection);
    }

    /**
     *
     * 获取sql查询结果中的一个字段组成数组返回
     *
     * @param string $sql        查询的sql
     * @param array  $binds      绑定的参数
     * @param string $field      查询的字段
     * @param string $connection 使用的数据库连接
     *
     * @return array
     */
    public function getOneAllBySql($sql, $binds = [], $field = '', $connection = 'default')
    {
        $ret  = $this->getAllBySql($sql, $binds, $connection);
        $data = [];
        if ($ret) {
            foreach ($ret as $row) {
                $data[] = $field ? Arr::get($row, $field) : array_shift($row);
            }
        }
        return $data;
    }

    /**
     *
     * 获取sql查询结果中的一个字段组成数组返回
     *
     * @param string $sql        查询的sql
     * @param array  $binds      绑定的参数
     * @param string $field      查询的字段
     * @param string $connection 使用的数据库连接
     *
     * @return array
     */
    public function fetchOneAll($sql, $binds = [], $field = '', $connection = 'default')
    {
        return $this->getOneAllBySql($sql, $binds, $field, $connection);
    }

    /**
     *
     * 获取sql查询结果中的一个字段组成数组返回
     *
     * @param string $sql        查询的sql
     * @param array  $binds      绑定的参数
     * @param string $field      查询的字段
     * @param string $connection 使用的数据库连接
     *
     * @return array
     */
    public function findAllColumnBySql($sql, $binds, $field = '', $connection = 'default')
    {
        return $this->getOneAllBySql($sql, $binds, $field, $connection);
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
        $model = $this->setModelFilter($filters);
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
        $model = $this->setModelFilter($filters);
        return $model->count();
    }

    /**
     * 获取最大值
     *
     * @param $filters
     * @param $field
     *
     * @return mixed
     */
    public function findMax($filters, $field)
    {
        return $this->setModelFilter($filters)->max($field);
    }

    /**
     * 添加数据
     *
     * @param array $data 添加的数据数组
     *
     * @return array
     */
    public function create($data)
    {
        return $this->insert($data);
    }

    /**
     * 新增数据
     *
     * @param array $data 新增的数据
     *
     * @return array
     */
    final public function insert($data)
    {
        if (!is_array($data) || !$data) {
            return $this->error(t('操作失败'));
        }

        try {
            if (!$model = $this->model->create($data)) {
                return $this->error(t('操作失败'));
            }

            return $this->success($model->toArray());
        } catch (Exception $e) {
            return $this->error($this->getError($e));
        }
    }

    /**
     * 更新操作
     *
     * @param $update_condition
     * @param $update_data
     *
     * @return array
     */
    public function update($update_condition, $update_data)
    {
        return $this->modify($update_condition, $update_data);
    }

    /**
     * 支持复杂查询的修改数据
     *
     * @param array $update_condition 查询数据
     * @param array $update_data      修改数据
     *
     * @return array
     */
    public function findConditionUpdate($update_condition, $update_data)
    {
        $model = $this->setModelFilter($update_condition);
        try {
            $rows = $model->update($update_data);
            //更新成功要调用清除缓存方法
            if ($rows && method_exists($this, 'clearCache')) {
                $this->clearCache($update_condition);
            }

            return $this->success($rows, t('更新成功'));
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
    final public function modify($update_condition, $update_data)
    {
        //根据pk更新单条记录
        if (is_scalar($update_condition) && preg_match('/^\d+$/', $update_condition)) {
            $update_condition = [
                $this->model->getKeyName() => $update_condition,
            ];
        }

        //过滤非法字段，禁止更新PK
        $columns = $this->getTableColumns();
        foreach ($update_data as $k => $v) {
            if ($k == $this->model->getKeyName() || !isset($columns[$k])) {
                unset($update_data[$k]);
            }
        }

        //空值判断
        if (empty($update_data)) {
            return $this->error(t('未指定要更新的字段信息'));
        }

        if (is_array($update_condition) && $update_condition) {
            foreach ($update_condition as $k => $v) {
                if (!isset($columns[$k])) {
                    unset($update_condition[$k]);
                }
            }
        }
        if (!$update_condition) {
            return $this->error(t('未指定当前更新的条件'));
        }

        try {
            /**
             * 此处不能直接使用$this->model->fill,因为$this->model是单例
             * 这会导致批量任务的时候fill的Attributes上下文数据错乱
             * 比如先fill a=>1 b=>2 再fill c=>3
             * 那么其实第二次update的数据是a=>1 b=>2 c=>3 而非c=>3,因为单例导致了追加错误
             */
            /* @var $model mixed */
            $model = $this->model->newInstance();
            $rows  = $model->where($update_condition)->update($model->fill($update_data)->getAttributes());

            //更新成功要调用清除缓存方法
            if ($rows && method_exists($this, 'clearCache')) {
                $this->clearCache($update_condition);
            }

            return $this->success($rows, t('更新成功'));
        } catch (Exception $e) {
            return $this->error($this->getError($e));
        }
    }


    /**
     * 删除操作
     *
     * @param $id_or_array
     *
     * @return array
     */
    public function delete($id_or_array)
    {
        return $this->remove($id_or_array);
    }

    /**
     * 删除数据
     *
     * @param mixed|array $id_or_array 删除的条件
     *
     * @return array
     */
    final public function remove($id_or_array)
    {
        //id转换
        if (is_scalar($id_or_array) && preg_match('/^\d+$/', $id_or_array)) {
            $filters = [
                $this->model->getKeyName() => $id_or_array,
            ];
        } else {
            $filters = $id_or_array;
        }

        try {
            //物理删除时，清除缓存要放到执行删除操作的前面执行
            //如果在后面，数据被删除，无法获取数据结果，导致缓存无法清除
            if (method_exists($this, 'clearCache')) {
                $this->clearCache($id_or_array);
            }

            $affected_rows = $this->model->where($filters)->delete();
            return $this->success($affected_rows, t('操作成功'));
        } catch (Exception $e) {
            return $this->error($this->getError($e));
        }
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

    /***
     *
     * 获取单条信息
     *
     * @param Model $item
     * @param array $fields
     *
     * @return array
     */
    protected function getItemInfo(Model $item, $fields = [])
    {
        if (!$item) {
            return [];
        }

        // 默认为所有字段的值
        $info = $item->attributesToArray();
        if (!$fields) {
            return $info;
        }

        // 如果指定了排除字段，那么要排除那些字段
        if ($except = Arr::pull($fields, 'except')) {
            array_forget($info, $except);
        }

        // 如果指定了字段名称，则显示自定义的结果
        $rs = [];
        if (in_array('*', $fields)) {
            $rs = $info;
        }
        foreach ($fields as $key => $sub_fields) {
            if (is_int($key) && isset($info[$sub_fields])) {
                $rs[$sub_fields] = $info[$sub_fields];
            } else {

                if (!$item->$key) {
                    continue;
                }

                //查询关联模型的数据
                //一对多结果
                if ($item->$key instanceof Collection) {
                    $rs[$key] = [];
                    foreach ($item->$key as $i => $sub_item) {
                        if ($i < $this->max_collection_size) {
                            $rs[$key][] = $this->getItemInfo($sub_item, $sub_fields);
                        }
                    }

                } else {//一对一结果
                    $rs[$key] = $this->getItemInfo($item->$key, $sub_fields);
                }
            }
        }
        return $rs;
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

            if (is_empty($value)) {
                unset($condition[$key]);
            }
        }

        return $condition;
    }

    /**
     * @param      $msg
     * @param int  $code
     * @param null $prev
     *
     * @throws Exception
     */
    protected function throwException($msg, $code = 0, $prev = null)
    {
        throw new Exception($msg, $code, $prev);
    }

    /****
     *
     * 检查操作结果是否正确，否则抛出异常
     *
     * @param $ok
     * @param $msg
     *
     * @throws Exception
     */
    protected function checkError($ok, $msg)
    {
        if (!$ok) {
            $this->throwException($msg);
        }
    }

    /****
     *
     *
     * 设置查询条件
     *
     * @param array $filters
     * @param array $fields
     *
     * @return mixed
     */
    private function setModelFilter($filters = [], $fields = [])
    {
        $model   = $this->model;
        $columns = $this->getTableColumns($model);
        $table   = $model->getTable();

        //如果是单个数值，则自动转换为PK条件查询
        if (!is_array($filters)) {
            $filters = [
                $model->getKeyName() => (int)$filters,
            ];
        }
        $sql_hint = $this->getSqlHint();
        if ($sql_hint) {
            $filters['__SQL_HINT__'] = $sql_hint;
        }

        $fields = (array)$fields;

        if (!$filters) {
            return $model;
        }

        /****
         * 分组，如果是relation的查询条件，需要放在前面build
         */
        $relation_filters = $model_filters = [];
        foreach ($filters as $field => $value) {
            list($relation_name, $relation_key) = array_pad(explode('.', $field, 2), 2, null);

            if ($relation_name && $relation_key) {
                $relation_filters[$field] = $value;
            } else {
                $model_filters[$field] = $value;
            }
        }

        //首先设置relation查询，不能放在后面执行
        if ($relations = $this->getRelations($model, $fields, $relation_filters, $this)) {
            $model = $model->with($relations);
        }

        //判断是否有关联模型的统计操作
        $model = $this->addRelationCountSelect($model, $fields, $relation_filters, $this);
        unset($relations, $relation_name, $relation_filters);
        //设置查询字段
        $model = $this->addSelect($model, $fields, $table);

        return $this->addFilters($model_filters, $model, $this, $table, $columns);
    }

    /****
     *
     * relation count查询
     *
     * @param                     $model
     * @param                     $fields
     * @param                     $relation_filters
     * @param  Repository         $that
     *
     * @return mixed
     */
    private function addRelationCountSelect($model, $fields, $relation_filters, $that)
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
                    /* @var $model mixed */
                    if ($count_key == '_count' && method_exists($model->getModel(), $relation_name)) {
                        //当前模型的关联模型
                        $sub_model = $model->getModel()->$relation_name()->getRelated();

                        //count必须转换为小写，对应输出的count字段也为小写，如：users_count,storesinfo_count
                        $columns = $that->getTableColumns($sub_model);//关联模型的表字段
                        /* @var $sub_model mixed */
                        $table = $sub_model->getTable();//关联模型的表名
                        //关联模型的查询条件
                        $cur_filters = array_merge(
                            $that->getRelationDefaultFilters($model->getModel(), $relation_name), (array)Arr::get($filters, $relation_name)
                        );

                        $relations_count[$relation_name] = function ($query) use ($cur_filters, $that, $columns, $table) {
                            foreach ($cur_filters as $relation_field => $relation_value) {
                                //relation排序设置
                                if ($relation_field == 'order') {
                                    $query = $that->addOrderQuery($query, $relation_value, $table, $columns);
                                    unset($cur_filters['order']);//去除order字段
                                }

                                //字段精准匹配
                                if (isset($columns[$relation_field])) {
                                    $query = $that->addAccurateQuery($query, $table . '.' . $relation_field, $relation_value);
                                } else {
                                    //高级查询
                                    list($model_key, $model_compare) = array_pad(explode(':', $relation_field, 2), 2, null);
                                    if ($model_key && $model_compare) {
                                        $query = $that->addComplexQuery($query, $table . '.' . $model_key, $model_compare, $relation_value);
                                    }
                                }
                            }
                            return $query;

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

    /****
     *
     * 添加select语句，如果有关联_count，则不添加
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param                                    $fields
     * @param                                    $table
     *
     * @return mixed
     */
    private function addSelect($query, $fields, $table)
    {
        $use_select = true;
        foreach ($fields as $i => $filter) {
            if (is_int($i) && is_string($filter)) {
                if (substr($filter, -6) === '_count') {
                    $use_select = false;
                }
            }
        }

        //查询字段设置为所有，保证relation取值正常,而输出时根据自定义再过滤
        if ($use_select) {
            $query = $query->select([$table . '.*']);
        }

        return $query;
    }

    /****
     *
     * 添加SQL排序规则
     *
     * @param  mixed $model
     * @param string $order_string
     * @param string $table
     * @param array  $columns
     *
     * @return mixed
     */
    private function addOrderQuery($model, $order_string, $table, $columns)
    {
        if ($orders = explode(',', $order_string)) {
            foreach ($orders as $order) {
                list($k, $v) = array_pad(explode(' ', preg_replace('/\s+/', ' ', $order)), 2, null);

                if ($k && isset($columns[$k]) && in_array(strtolower($v), ['', 'asc', 'desc'])) {
                    $model = $model->orderBy($table ? $table . '.' . $k : $k, $v ?: 'desc');
                }
            }
        }

        return $model;
    }

    /****
     *
     * 添加精准的SQL查询(条件字段与表格字段撇撇)
     *
     * @param mixed $model
     * @param       $field
     * @param       $value
     *
     * @return mixed
     */
    private function addAccurateQuery($model, $field, $value)
    {
        //in查询
        if (is_array($value)) {
            $model = $model->whereIn($field, $value);
        } else {//=查询
            $model = $model->where($field, $value);
        }

        return $model;
    }

    /*****
     *
     * 复杂查询，条件字段需要解释执行
     *
     * @param $model
     * @param $field
     * @param $compare
     * @param $value
     * @param $or
     *
     * @return mixed
     */
    private function addComplexQuery($model, $field, $compare, $value, $or = false)
    {
        if ($expression = Arr::get($this->expression, strtolower($compare))) {
            if (in_array($expression, ['In', 'NotIn', 'Between', 'NotBetween'])) {
                $strMethod = $or ? 'orWhere' . $expression : 'where' . $expression;
                $model     = $model->{$strMethod}($field, (array)$value);
            } else {
                $strMethod = $or ? 'orWhere' : 'where';
                if (in_array($expression, ['LIKE', 'NOT LIKE'])) {
                    $value = '%' . (string)$value . '%';
                }

                $model = $model->{$strMethod}($field, $expression, $value);
            }
        }

        return $model;
    }

    /*****
     *
     * 获取relation集合
     *
     * @param                     $model
     * @param                     $fields
     * @param                     $relation_filters
     * @param Repository          $that
     *
     * @return array
     */
    private function getRelations($model, $fields, $relation_filters, $that)
    {
        //relation查询时，合并SQL，优化语句
        $relations = [];
        if ($fields) {
            foreach ($fields as $field_key => $field_val) {
                if (!is_int($field_key)) {
                    //Model对象
                    if (method_exists($model, ucfirst($field_key))) {
                        $relations[$field_key] = [];
                    }
                }
            }
            unset($field_key, $field_val);
        }

        /************
         *
         *
         * 关联模型的条件查询
         * relationModel.KeyName:compare
         * eg:userInfo.user_id:neq
         * eg:appInfo.status
         * eg:channelInfo.user_id:between
         *
         *
         */
        if ($relation_filters) {
            foreach ($relation_filters as $relation_key => $relation_value) {
                $dot_index = strpos($relation_key, '.');
                if ($dot_index !== false) {
                    $relation_name  = substr($relation_key, 0, $dot_index);
                    $relation_field = substr($relation_key, $dot_index + 1);
                } else {
                    $relation_name  = '';
                    $relation_field = $relation_key;
                }
                //如果relation存在
                if (method_exists($model, $relation_name)) {
                    //未初始化时先初始化为空数组
                    if (!isset($relations[$relation_name])) {
                        $relations[$relation_name] = [];
                    }
                    $relations[$relation_name][$relation_field] = $relation_value;
                }
            }
        }

        //当前model实际要绑定的relation
        $bind_relations = [];

        if ($relations) {
            foreach ($relations as $relation_name => $relation_filters) {
                $bind_relations[$relation_name] = $that->buildRelation(
                    array_merge($that->getRelationDefaultFilters($model, $relation_name), (array)$relation_filters), (array)Arr::get($fields, $relation_name), $that
                );
            }
        }

        return $bind_relations;
    }

    /****
     *
     * 获取relation的默认条件
     *
     * @param $model
     * @param $relation_name
     *
     * @return array
     */
    private function getRelationDefaultFilters($model, $relation_name)
    {
        //添加relation的默认条件，默认条件数组为“$relationFilters"的public属性
        $filter_attribute = $relation_name . 'Filters';

        if (isset($model->$filter_attribute) && is_array($model->$filter_attribute)) {
            $relation_data = $model->$filter_attribute;
        } else {
            $relation_data = [];
            try {
                //由于PHP类属性区分大小写，而relation_count字段为小写，利用反射将属性转为小写，再进行比较
                $reflect = new ReflectionClass($model);
                $pros    = $reflect->getDefaultProperties();
                foreach ($pros as $name => $val) {
                    if (strtolower($name) == strtolower($filter_attribute) && is_array($val)) {
                        $relation_data = $val;
                    }
                }
            } catch (Exception $e) {
                return $this->error($this->getError($e));
            }
        }

        return $relation_data;
    }

    /****
     * @param                     $relation_filters
     * @param                     $relation_fields
     * @param Repository          $that
     *
     * @return Closure
     */
    private function buildRelation($relation_filters, $relation_fields, $that)
    {
        return function ($query) use ($relation_filters, $relation_fields, $that) {
            //获取relation的表字段
            /* @var $query mixed */
            $columns = $that->getTableColumns($query->getRelated());
            $table   = $query->getRelated()->getTable();

            //relation绑定
            if ($relations = $that->getRelations($query->getRelated(), $relation_fields, $relation_filters, $that)) {
                $query = $query->with($relations);
            }
            //判断是否有关联模型的统计操作
            if ($relation_fields) {
                $this->addRelationCountSelect($query, $relation_fields, $relation_filters, $that);
            }
            $that->addSelect($query, $relation_fields, $table);
            $that->addFilters($relation_filters, $query, $that, $table, $columns);
        };
    }

    /*****
     *
     *
     * 条件查询
     *
     * @param                     $relation_filters
     * @param Model|Builder       $query
     * @param Repository          $that
     * @param                     $table
     * @param                     $columns
     *
     * @return mixed
     */
    private function addFilters($relation_filters, $query, $that, $table, $columns)
    {
        // 添加指定了索引
        if ($force_index = Arr::pull($relation_filters, 'force_index')) {
            $query = $query->from(DB::raw("{$this->model->getTable()} FORCE INDEX ({$force_index})"));
        }

        // 处理分组
        if ($group_by = Arr::pull($relation_filters, 'group_by')) {
            $query = $query->groupBy($group_by);
        }

        // 处理排序
        if ($order_by = Arr::pull($relation_filters, 'order')) {
            $query = $that->addOrderQuery($query, $order_by, $table, $columns);
        }

        // 查询数据条数
        if ($limit = Arr::pull($relation_filters, 'limit')) {
            $query = $query->limit($limit);
        }

        // 查询 __SQL_HINT__
        if ($raw_sql = Arr::pull($relation_filters, '__SQL_HINT__')) {
            $query = $query->whereRaw(' 1 = 1 ' . $raw_sql);
        }

        foreach ($relation_filters as $relation_field => $relation_value) {
            //Or查询
            if (strtolower($relation_field) == 'or') {
                if (is_array($relation_value) && $relation_value) {
                    $query = $query->where(
                        function ($query) use ($relation_value, $that, $table, $columns) {
                            foreach ($relation_value as $k => $v) {
                                //字段精准匹配
                                if (isset($columns[$k])) {
                                    /* @var $query \Illuminate\Database\Query\Builder */
                                    $query = is_array($v) ? $query->orWhereIn($table . '.' . $k, $v) : $query->orWhere($table . '.' . $k, $v);
                                } else {
                                    //高级查询
                                    list($model_key, $model_compare) = array_pad(explode(':', $k, 2), 2, null);
                                    if ($model_key && $model_compare) {
                                        $query = $that->addComplexQuery($query, $table . '.' . $model_key, $model_compare, $v, true);
                                    }
                                }
                            }
                        }
                    );
                }
            }

            //字段精准匹配
            if (isset($columns[$relation_field])) {
                $query = $that->addAccurateQuery($query, $table . '.' . $relation_field, $relation_value);
            } else {
                //高级查询
                list($model_key, $model_compare) = array_pad(explode(':', $relation_field, 2), 2, null);
                if ($model_key && $model_compare) {
                    $query = $that->addComplexQuery($query, $table . '.' . $model_key, $model_compare, $relation_value);
                } else {
                    //自定义scope查询
                    if (is_a($query, Model::class)) {
                        $_method = 'scope' . ucfirst($relation_field);
                        if (method_exists($query, $_method)) {
                            $query = $query->$_method($query, $relation_value);
                        } else {
                            $_camel_method = 'scope' . ucfirst(camel_case($relation_field));

                            if (method_exists($query, $_camel_method)) {
                                $query = $query->$_camel_method($query, $relation_value);
                            }
                        }

                    } else {
                        $ret = 1;
                        try {
                            $query = $query->$relation_field($relation_value);
                        } catch (Exception $e) {
                            $ret = 0;
                        }
                        if ($ret == 0) {
                            $_camel_method = ucfirst(Str::camel($relation_field));
                            try {
                                $query = $query->$_camel_method($relation_value);
                            } catch (Exception $e) {
                                $this->error($e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        return $query;
    }

    /***
     *
     * 根据运行环境上报错误
     *
     * @param Exception $e
     *
     * @return mixed|string
     */
    protected function getError(Exception $e)
    {
        //记录数据库执行错误日志
        logger()->error('db error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        return app()->environment('production') ? t("系统错误，请重试") : $e->getMessage();
    }

    /**
     * @param       $filters
     * @param array $fields
     *
     * @return array|bool
     */
    private function realGet($filters, $fields = [])
    {
        $item = $this->setModelFilter($filters, $fields)->first();
        return $item ? $this->getItemInfo($item, $fields) : false;
    }

    private function getSqlHint()
    {
        $db_mode = strtolower(env('DB_MODE'));
        //默认master，非法值也用master
        if (!$db_mode || !isset($this->db_modes[$db_mode])) {
            $db_mode = 'master';
        }
        return $this->db_modes[$db_mode];
    }

    private function protectSelectStatement($sql)
    {
        $command = substr($sql, 0, 6);
        //非查询语句，不干预
        if (strtoupper($command) != 'SELECT') {
            return $sql;
        }

        $sql_hint = $this->getSqlHint();
        if ($sql_hint) {
            $sql = $sql_hint . $sql;
        }

        return $sql;
    }

    private function searchCache($filters)
    {
        if (!method_exists($this, 'getCache')) {
            return $this->cacheNotFound();
        }

        $ret = $this->getCache($filters);
        if (!is_array($ret) || count($ret) < 2) {
            return $this->cacheNotFound();
        }
        list($get, $set) = array_pad($ret, 2, null);
        if (is_object($get) && get_class($get) == 'Closure') {
            if ($data = $get()) {
                return $this->cacheHit($data);
            } else {
                return $this->cacheMiss($set);
            }
        }

        return $this->cacheNotFound();
    }
}
