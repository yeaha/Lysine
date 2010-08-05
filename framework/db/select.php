<?php
namespace Lysine\Db;

use Lysine\Utils\Coll;

/**
 * 这个类只管组装sql语句，并对查询结果进行简单处理
 * 尽量只做字符串处理
 *
 * @package db
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Select {
    /**
     * get()方法返回的多行数据是否使用Lysine\Utils\Coll类包装
     * 影响到所有的实例
     *
     * @var boolean
     * @access public
     * @static
     */
    static public $enableCollection = true;

    /**
     * 数据库连接
     *
     * @var IAdapter
     * @access protected
     */
    protected $adapter;

    /**
     * sql FROM
     *
     * @var string
     * @access protected
     */
    protected $from;

    /**
     * 结果字段
     *
     * @var array
     * @access protected
     */
    protected $cols = array();

    /**
     * where 条件表达式
     *
     * @var array
     * @access protected
     */
    protected $where = array();

    /**
     * sql GROUP
     *
     * @var string
     * @access protected
     */
    protected $group;

    /**
     * sql HAVING
     *
     * @var string
     * @access protected
     */
    protected $having;

    /**
     * sql ORDER BY
     *
     * @var string
     * @access protected
     */
    protected $order;

    /**
     * sql LIMIT
     *
     * @var integer
     * @access protected
     */
    protected $limit;

    /**
     * sql OFFSET
     *
     * @var integer
     * @access protected
     */
    protected $offset;

    /**
     * sql UNION
     *
     * @var mixed
     * @access protected
     */
    protected $union;

    /**
     * 返回数组中作为key的字段
     *
     * @var string
     * @access protected
     */
    protected $key_column;

    /**
     * 返回值处理器
     * get()方法返回的每条数据都会传递给处理器处理一次
     *
     * @var callback
     * @access protected
     */
    protected $processor;

    /**
     * where表达式之间的逻辑关系 AND或OR
     *
     * @var string
     * @access protected
     */
    protected $where_relation = 'AND';

    /**
     * get()方法返回的多行数据是否使用Lysine\Utils\Coll类包装
     * 只影响当前实例
     *
     * @var boolean
     * @access protected
     */
    protected $return_collection;

    /**
     * 构造函数
     *
     * @param IAdapter $adapter
     * @param boolean $return_collection
     * @access public
     * @return void
     */
    public function __construct(IAdapter $adapter, $return_collection = true) {
        $this->adapter = $adapter;
        $this->return_collection = $return_collection;
    }

    /**
     * 直接调用查询结果对象
     *
     * <code>
     * 获得结果的第2行
     * $select->getCols(1);
     * 等于
     * $select->execute()->getCols(1);
     * </code>
     *
     * @param string $fn
     * @param mixed $args
     * @access public
     * @return mixed
     */
    public function __call($fn, $args) {
        if (substr($fn, 0, 3) == 'get')
            $sth = $this->execute();
            return call_user_func_array(array($sth, $fn), $args);
    }

    /**
     * sql FROM
     *
     * @param string $table
     * @access public
     * @return Lysine\Db\Select
     */
    public function from($table) {
        $this->from = $table;
        return $this;
    }

    /**
     * 获得数据库连接
     *
     * @access public
     * @return Lysine\Db\IAdapter
     */
    public function getAdapter() {
        return $this->adapter;
    }

    /**
     * 指定结果字段
     *
     * @param mixed $cols
     * @access public
     * @return Lysine\Db\Select
     */
    public function setCols($cols = null) {
        $this->cols = is_array($cols) ? $cols : func_get_args();
        return $this;
    }

    /**
     * 增加结果字段
     *
     * @param mixed $col
     * @access public
     * @return Lysine\Db\Select
     */
    public function addCol($col = null) {
        $cols = $this->cols;
        if (!is_array($col)) $col = func_get_args();

        while (list(, $c) = each($col)) $cols[] = $c;
        $this->cols = array_unique($cols);
        return $this;
    }

    /**
     * 指定返回的查询数组中，以哪个字段的值为key
     * 注意：如果指定的字段不是主键字段，可能会造成返回数据不完整的情况
     *
     * @param string $column_name
     * @access public
     * @return Lysine\Db\Select
     */
    public function setKeyColumn($column_name) {
        $this->key_column = $column_name;
        return $this;
    }

    /**
     * 增加一个查询条件
     * 所有通过这里添加的条件都是AND关系
     *
     * 如果需要OR关系，可以这样写
     * $select->where('expr1 or expr2')
     * $select->where('expr3 or expr4')
     * 结果等于(expor1 or expr2) and (expr3 or expr4)
     *
     * @param string $where
     * @param mixed $bind
     * @access public
     * @return Lysine\Db\Select
     */
    public function where($where, $bind = null) {
        $args = func_get_args();
        $this->where[] = call_user_func_array(array($this->adapter, 'parsePlaceHolder'), $args);
        return $this;
    }

    /**
     * 添加一个子查询
     * 和其它的查询条件是and关系
     *
     * @param string $col
     * @param mixed $relation
     * @param boolean $in
     * @access protected
     * @return Lysine\Db\Select
     */
    protected function whereSub($col, $relation, $in) {
        if ($relation instanceof Select) {
            list($where, $bind) = $relation->compile();
        } else {
            if (!is_array($relation)) $relation = array($relation);
            $adapter = $this->getAdapter();
            $where = implode(',', $adapter->qstr($relation));
            $bind = array();
        }

        if ($in) {
            $where = sprintf('%s IN (%s)', $col, $where);
        } else {
            $where = sprintf('%s NOT IN (%s)', $col, $where);
        }
        $this->where[] = array($where, $bind);
        return $this;
    }

    /**
     * 添加一个WHERE IN子查询
     *
     * <code>
     * // select * from user where id in (1, 2, 3)
     * $user_select->whereIn('id', array(1, 2, 3));
     *
     * // select * from other where user_id in (select id from user where id in (1, 2, 3))
     * $other_select->whereIn('user_id', $user_select->setCols('id'));
     * </code>
     *
     * @param string $col
     * @param mixed $relation
     * @access public
     * @return Lysine\Db\Select
     */
    public function whereIn($col, $relation) {
        return $this->whereSub($col, $relation, true);
    }

    /**
     * 添加一个WHERE NOT IN子查询
     *
     * @param string $col
     * @param mixed $relation
     * @access public
     * @return Lysine\Db\Select
     */
    public function whereNotIn($col, $relation) {
        return $this->whereSub($col, $relation, false);
    }

    /**
     * 设置where表达式之间的逻辑关系
     *
     * @param string $relation
     * @access public
     * @return Lysine\Db\Select
     */
    public function setWhereRelation($relation) {
        $relation = strtoupper($relation);

        if ($relation != 'AND' AND $relation != 'OR')
            throw new \UnexpectedValueException('relation of where condition must be AND or OR');
        $this->where_relation = $relation;
        return $this;
    }

    /**
     * sql GROUP
     *
     * @param string $group_by
     * @access public
     * @return Lysine\Db\Select
     */
    public function group($group_by) {
        $this->group = $group_by;
        return $this;
    }

    /**
     * sql HAVING
     *
     * @param string $having
     * @access public
     * @return Lysine\Db\Select
     */
    public function having($having) {
        $this->having = $having;
        return $this;
    }

    /**
     * sql ORDER
     *
     * @param string $order_by
     * @access public
     * @return Lysine\Db\Select
     */
    public function order($order_by) {
        $this->order = $order_by;
        return $this;
    }

    /**
     * sql LIMIT
     *
     * @param integer $limit
     * @access public
     * @return Lysine\Db\Select
     */
    public function limit($limit) {
        $this->limit = abs((int)$limit);
        return $this;
    }

    /**
     * sql OFFSET
     *
     * @param integer $offset
     * @access public
     * @return Lysine\Db\Select
     */
    public function offset($offset) {
        $this->offset = abs((int)$offset);
        return $this;
    }

    /**
     * sql UNION
     *
     * <code>
     * // select * from table1 where id = 1
     * $select1 = $adapter->select('table1')->where('id = ?', 1);
     * $select2 = $adapter->select('table1')->where('id = ?', 2);
     *
     * // select * from table1 where id = 1
     * // union all
     * // select * from table1 where id = 2
     * $select1->union($select2);
     * </code>
     *
     * @param mixed $relation
     * @param boolean $all
     * @access public
     * @return Lysine\Db\Select
     */
    public function union($relation, $all = true) {
        $this->union = array($relation, $all);
        return $this;
    }

    /**
     * 生成sql语句的where部分
     *
     * @access protected
     * @return array
     */
    protected function compileWhere() {
        $where = $bind = array();
        foreach ($this->where as $w) {
            list($where_sql, $where_bind) = $w;
            $where[] = $where_sql;
            $bind = array_merge($bind, $where_bind);
        }

        $where = $where ? '('. implode(') '. $this->where_relation .' (', $where) .')' : '';

        return array($where, $bind);
    }

    /**
     * 生成sql语句
     *
     * @access public
     * @return array
     */
    public function compile() {
        $adapter = $this->adapter;

        $cols = implode(',', $adapter->qcol($this->cols));
        if (empty($cols)) $cols = '*';

        $sql = sprintf('SELECT %s FROM %s', $cols, $adapter->qtab($this->from));

        list($where, $bind) = $this->compileWhere();
        if ($where) $sql .= sprintf(' WHERE %s', $where);

        if ($this->group) {
            $sql .= ' GROUP BY '. $this->group;
            if ($this->having) $sql .= ' HAVING '. $this->having;
        }

        if ($this->order) $sql .= ' ORDER BY '. $this->order;
        if ($this->limit) $sql .= ' LIMIT '. $this->limit;
        if ($this->offset) $sql .= ' OFFSET '. $this->offset;

        if ($this->union) {
            list($relation, $all) = $this->union;
            // 某些数据库可能不支持union all语法
            $sql .= $all ? ' UNION ALL ' : ' UNION ';

            if ($relation instanceof Select) {
                list($relation, $relation_bind) = $relation->compile();
                $bind = array_splice($bind, count($bind), 0, $relation_bind);
            }
            $sql .= $relation;
        }

        return array($sql, $bind);
    }

    /**
     * 魔法方法
     *
     * @access public
     * @return string
     */
    public function __toString() {
        list($sql, $bind) = $this->compile();
        return $sql;
    }

    /**
     * 执行数据库查询
     * 返回db result对象
     *
     * @access public
     * @return Lysine\Db\IResult
     */
    public function execute() {
        list($sql, $bind) = $this->compile();
        return $this->adapter->execute($sql, $bind);
    }

    /**
     * 定义预处理器，所有get()方法返回的数据都会用预处理器执行一次
     * 预处理器可以是任意合法的callback或者闭包
     *
     * @param mixed $processor
     * @access public
     * @return mixed
     */
    public function setProcessor($processor) {
        $this->processor = $processor;
        return $this;
    }

    /**
     * 返回查询数据
     *
     * @param integer $limit
     * @access public
     * @return mixed
     */
    public function get($limit = null) {
        if (is_int($limit)) $this->limit($limit);

        $limit = $this->limit;
        $sth = $this->execute();

        $processor = $this->processor;
        if ($limit === 1) {
            $result = $sth->getRow();
            return $processor ? call_user_func($processor, $result) : $result;
        } else {
            $result = $sth->getAll($this->key_column);
            if ($processor) $result = array_map($processor, $result);
            if (self::$enableCollection && $this->return_collection) $result = new Coll($result);
            return $result;
        }
    }

    /**
     * 删除数据
     *
     * 注意：直接利用select删除数据可能不是你想要的结果
     * <code>
     * // 找出符合条件的前5条
     * // select * from "users" where id > 100 order by create_time desc limit 5
     * $select = $adapter->select('users')->where('id > ?', 100)->order('create_time desc')->limit(5);
     *
     * // 因为DELETE语句不支持order by / limit / offset
     * // 删除符合条件的，不仅仅是前5条
     * // delete from "users" where id > 100
     * $select->delete()
     *
     * // 如果要删除符合条件的前5条
     * // delete from "users" where id in (select id from "users" where id > 100 order by create_time desc limit 5)
     * $adapter->select('users')->whereIn('id', $select->setCols('id'))->delete();
     * </code>
     * 这里很容易犯错，考虑是否不提供delete()和update()方法
     * 或者发现定义了limit / offset就抛出异常中止
     *
     * @access public
     * @return integer
     */
    public function delete() {
        list($where, $bind) = $this->compileWhere();

        // 在这里，不允许没有任何条件的delete
        if (!$where)
            throw new \LogicException('MUST specify WHERE condition before delete');

        // 见方法注释
        if ($this->limit OR $this->offset)
            throw new \LogicException('CAN NOT DELETE while specify LIMIT or OFFSET');

        return $this->adapter->delete($this->from, $where, $bind);
    }

    /**
     * 更新数据
     * 注意事项见delete()
     *
     * @param array $set
     * @access public
     * @return integer
     */
    public function update(array $set) {
        list($where, $bind) = $this->compileWhere();

        // 在这里，不允许没有任何条件的update
        if (!$where)
            throw new \LogicException('MUST specify WHERE condition before update');

        // 见delete()方法注释
        if ($this->limit OR $this->offset)
            throw new \LogicException('CAN NOT UPDATE while specify LIMIT or OFFSET');

        return $this->adapter->update($this->from, $set, $where, $bind);
    }
}
