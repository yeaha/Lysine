<?php
class Ly_Db_Select {
    protected $adapter;

    protected $from;
    protected $cols = array();
    protected $where = array();
    protected $group;
    protected $having;
    protected $order;
    protected $limit;
    protected $offset;

    protected $return_as = array();

    public function __construct(Ly_Db_Adapter_Abstract $adapter) {
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

    public function returnAs($class, $pre_args = null, $post_args = null) {
        $this->return_as = array($class, $pre_args, $post_args);
        return $this;
    }

    public function get($limit = null) {
        if (is_int($limit)) $this->limit($limit);

        $limit = $this->limit;
        $sth = $this->execute();

        list($as_class, $pre_args, $post_args) = $this->return_as;
        if (!$as_class) return ($limit === 1) ? $sth->getRow() : new Ly_Coll($sth->getAll());

        $result = new Ly_Coll($sth->getAll());
        $result->package($as_class, $pre_args, $post_args);
        return ($limit === 1) ? $result->shift() : $result;
    }
}
