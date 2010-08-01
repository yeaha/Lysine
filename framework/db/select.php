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
    protected $adapter;

    protected $from;
    protected $cols = array();
    protected $where = array();
    protected $group;
    protected $having;
    protected $order;
    protected $limit;
    protected $offset;

    protected $union;

    protected $key_column;

    protected $processor;

    /**
     * 构造函数
     *
     * @param IAdapter $adapter
     * @access public
     * @return void
     */
    public function __construct(IAdapter $adapter) {
        $this->adapter = $adapter;
    }

    /**
     * 直接调用查询结果对象
     *
     * 获得结果的第2行
     * $select->getCols(1)
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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function where($where, $bind = null) {
        $args = func_get_args();
        $this->where[] = call_user_func_array(array($this->adapter, 'parsePlaceHolder'), $args);
        return $this;
    }

    /**
     * sql GROUP
     *
     * @param string $group_by
     * @access public
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function offset($offset) {
        $this->offset = abs((int)$offset);
        return $this;
    }

    /**
     * sql UNION
     *
     * @param mixed $relation
     * @param boolean $all
     * @access public
     * @return self
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

        $where = $where ? '('. implode(') AND (', $where) .')' : '';

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
            $result = new Coll($sth->getAll($this->key_column));
            return $processor ? $result->each($processor) : $result;
        }
    }

    /**
     * 删除数据
     *
     * @access public
     * @return integer
     */
    public function delete() {
        list($where, $bind) = $this->compileWhere();

        // 在这里，不允许没有任何条件的delete
        if (!$where)
            throw new \UnexpectedValueException('Must specify WHERE condition before delete');

        return $this->adapter->delete($this->from, $where, $bind);
    }

    /**
     * 更新数据
     *
     * @param array $set
     * @access public
     * @return integer
     */
    public function update(array $set) {
        list($where, $bind) = $this->compileWhere();

        // 在这里，不允许没有任何条件的update
        if (!$where)
            throw new \UnexpectedValueException('Must specify WHERE condition before update');

        return $this->adapter->update($this->from, $set, $where, $bind);
    }
}
