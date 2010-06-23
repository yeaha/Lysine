<?php
namespace Lysine\Storage\Db;

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

    protected $pre_processor;

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    public function __call($fn, $args) {
        if (substr($fn, 0, 3) == 'get')
            $sth = $this->execute();
            return call_user_func_array(array($sth, $fn), $args);
    }

    public function from($table) {
        $this->from = $table;
        return $this;
    }

    public function adapter() {
        return $this->adapter;
    }

    public function setCols($cols = null) {
        $this->cols = is_array($cols) ? $cols : func_get_args();
        return $this;
    }

    public function addCol($col = null) {
        $cols = $this->cols;
        if (!is_array($col)) $col = func_get_args();

        while (list(, $c) = each($col)) $cols[] = $c;
        $this->cols = array_unique($cols);
        return $this;
    }

    public function where($where, $bind = null) {
        $args = func_get_args();
        $this->where[] = call_user_func_array(array($this->adapter, 'parsePlaceHolder'), $args);
        return $this;
    }

    public function group($group_by) {
        $this->group = $group_by;
        return $this;
    }

    public function having($having) {
        $this->having = $having;
        return $this;
    }

    public function order($order_by) {
        $this->order = $order_by;
        return $this;
    }

    public function limit($limit) {
        $this->limit = abs((int)$limit);
        return $this;
    }

    public function offset($offset) {
        $this->offset = abs((int)$offset);
        return $this;
    }

    public function compile() {
        $adapter = $this->adapter;

        $cols = implode(',', $adapter->qcol($this->cols));
        if (empty($cols)) $cols = '*';

        $sql = sprintf('SELECT %s FROM %s', $cols, $adapter->qtab($this->from));

        $where = $bind = array();
        foreach ($this->where as $w) {
            list($where_sql, $where_bind) = $w;
            $where[] = $where_sql;
            $bind = array_merge($bind, $where_bind);
        }

        if ($where) $sql .= sprintf(' WHERE %s', '('. implode(') AND (', $where) .')');
        if ($this->group) {
            $sql .= ' GROUP BY '. $this->group;
            if ($this->having) $sql .= ' HAVING '. $this->having;
        }

        if ($this->order) $sql .= ' ORDER BY '. $this->order;
        if ($this->limit) $sql .= ' LIMIT '. $this->limit;
        if ($this->offset) $sql .= ' OFFSET '. $this->offset;

        return array($sql, $bind);
    }

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
    public function preProcess($processor) {
        $this->pre_processor = $processor;
    }

    public function get($limit = null) {
        if (is_int($limit)) $this->limit($limit);

        $limit = $this->limit;
        $sth = $this->execute();

        $processor = $this->pre_processor;
        if ($limit === 1) {
            $result = $sth->getRow();
            return $processor ? call_user_func($processor, $result) : $result;
        } else {
            $result = $sth->getAll();
            return $processor ? array_map($processor, $result) : $result;
        }
    }
}
