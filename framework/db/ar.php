<?php
namespace Lysine\Db;

use Lysine\Db;
use Lysine\Utils\Events;

abstract class ActiveRecord {
    /**
     * 对应的数据库表名
     *
     * @var string
     * @static
     * @access protected
     */
    static protected $table_name;

    /**
     * 主键字段名
     *
     * @var string
     * @static
     * @access protected
     */
    static protected $primary_key;

    /**
     * 字段行为定义
     *
     * @var array
     * @static
     * @access protected
     */
    static protected $row_config = array();

    /**
     * 引用关系定义
     *
     * @var array
     * @static
     * @access protected
     */
    static protected $referer = array(
        /*
        'author' => array(
            'class' => 'Author',
            'source_key' => 'author_id',
            'target_key' => 'id',
            'limit' => 1
        ),
        'books' => array(
            'class' => 'Book',
            'source_key' => 'id',
            'target_key' => 'author_id',
            'order' => 'create_time DESC',
        ),
        'orders' => array(
            'getter' => 'getOrders',
        ),
        */
    );

    /**
     * 数据库连接
     *
     * @var Adapter
     * @access protected
     */
    protected $adapter;

    /**
     * 从数据库得到的数据
     *
     * @var array
     * @access protected
     */
    protected $row = array();

    /**
     * 保持被改变过的字段名
     *
     * @var array
     * @access protected
     */
    protected $dirty_row = array();

    /**
     * 保存引用关系结果
     *
     * @var array
     * @access protected
     */
    protected $referer_result = array();

    /**
     * 构造函数
     *
     * @param array $row
     * @access public
     * @return void
     */
    public function __construct(array $row = array(), $from_db = false) {
        $events = Events::instance();
        $events->addEvent($this, 'before init', array($this, '__before_init'));
        $events->addEvent($this, 'after init', array($this, '__after_init'));

        $events->addEvent($this, 'before save', array($this, '__before_save'));
        $events->addEvent($this, 'after save', array($this, '__after_save'));

        $events->addEvent($this, 'before insert', array($this, '__before_insert'));
        $events->addEvent($this, 'after insert', array($this, '__after_insert'));

        $events->addEvent($this, 'before update', array($this, '__before_update'));
        $events->addEvent($this, 'after update', array($this, '__after_update'));

        $events->addEvent($this, 'before destroy', array($this, '__before_destroy'));
        $events->addEvent($this, 'after destroy', array($this, '__after_destroy'));

        $this->fireEvent('before init');

        $this->row = $row;
        if (!$from_db) $this->dirty_row = array_keys($row);

        $this->fireEvent('after init');
    }

    /**
     * 解构函数
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        Events::instance()->clearEvent($this);
    }

    /**
     * 得到主键的值
     *
     * @access public
     * @return mixed
     */
    public function id() {
        return $this->get(static::$primary_key);
    }

    /**
     * 设置字段的值
     *
     * @param string $key
     * @param mixed $val
     * @access public
     * @return void
     */
    public function __set($key, $val) {
        $this->set($key, $val);
    }

    /**
     * 得到字段的值
     * 或者引用的数据
     *
     * @param string $key
     * @access public
     * @return mixed
     */
    public function __get($key) {
        $val = $this->get($key);
        if ($val !== false) return $val;

        if (array_key_exists($key, static::$referer))
            return $this->getReferer($key);

        return false;
    }

    /**
     * 设置字段的值
     *
     * @param mixed $col
     * @param mixed $val
     * @param boolean $direct
     * @access public
     * @return self
     */
    public function set($col, $val = null, $direct = false) {
        if (is_array($col)) {
            $direct = (boolean)$val;
        } else {
            $col = array($col => $val);
        }

        $pk = static::$primary_key;
        while (list($key, $val) = each($col)) {
            if ($key == $pk && $this->row[$pk]) {
                trigger_error(__CLASS__ .': primary key refuse update', E_USER_WARNING);
            } else {
                $this->row[$key] = $val;
                if (!$direct) $this->dirty_row[] = $key;
            }
        }
        if (!$direct) $this->dirty_row = array_unique($this->dirty_row);

        return $this;
    }

    /**
     * 得到字段的值
     *
     * @param string $col
     * @access public
     * @return mixed
     */
    public function get($col) {
        if (array_key_exists($col, $this->row)) return $this->row[$col];
        return false;
    }

    /**
     * 得到引用的数据
     *
     * @param string $name
     * @access protected
     * @return mixed
     */
    protected function getReferer($name) {
        if (array_key_exists($name, $this->referer_result))
            return $this->referer_result[$name];

        $referer = static::$referer;
        if (!isset($referer[$name]))
            throw new \InvalidArgumentException('Invalid referer name['. $name .']');

        $config = $referer[$name];
        if (isset($config['getter'])) {
            $result = call_user_func(array($this, $config['getter']));
            $this->referer_result[$name] = $result;
            return $result;
        }

        if (isset($config['class'])) {
            $class = $config['class'];
            if (!is_subclass_of($class, 'Lysine\Db\ActiveRecord'))
                throw new \UnexpectedValueException('Referer class must be subclass of Lysine\Db\ActiveRecord');

            $select = forward_static_call(array($class, 'select'));
            if (isset($config['source_key'], $config['target_key'])) {
                $where = "{$config['target_key']} = ?";
                $bind = $this->get($config['source_key'], false);
                $select->where($where, $bind);
            }
            if (isset($config['limit'])) $select->limit($config['limit']);
            if (isset($config['order'])) $select->order($config['order']);

            $result = $select->get();
            $this->referer_result[$name] = $result;
            return $result;
        }
    }

    /**
     * 设置数据库连接
     *
     * @param Adapter $adapter
     * @access public
     * @return self
     */
    public function setAdapter(Adapter $adapter) {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * 得到数据库连接
     *
     * @access public
     * @return Adapter
     */
    public function getAdapter() {
        if (!$this->adapter) $this->adapter = Db::connect();
        return $this->adapter;
    }

    /**
     * 保存进数据库
     *
     * @access public
     * @return self
     */
    public function save() {
        $pk = static::$primary_key;
        $adapter = $this->getAdapter();
        $table_name = static::$table_name;

        $row = $this->row;
        if (!$this->dirty_row && !$row[$pk]) return $this;

        $this->fireEvent('before save');

        if ($row[$pk]) {
            $method = 'update';
            if (in_array($pk, $this->dirty_row)) $method = 'insert';
        } else {
            $method = 'insert';
        }

        if ($method == 'insert') {
            $this->fireEvent('before insert');

            if ($affect = $adapter->insert($table_name, $this->row)) {
                if (!isset($row[$pk]))
                    $this->set($pk, $adapter->lastInsertId($table_name), true);

                $this->fireEvent('after insert');
            }
        } else {
            $this->fireEvent('before update');

            $col = $adapter->qcol($pk);
            $affect = $adapter()->update($table_name, $row, "{$col} = ?", $row[$pk]);

            if ($affect) $this->fireEvent('after update');
        }

        $this->fireEvent('after save');

        if ($affect) $this->refersh();
        return $this;
    }

    /**
     * 从数据库删除
     *
     * @access public
     * @return boolean
     */
    public function destroy() {
        if (!$id = $this->id()) return false;

        $this->fireEvent('before destroy');

        $adpater = $this->getAdapter();
        $pk = $adapter->qcol(static::$primary_key);
        if ($affect = $adapter->delete(static::$table_name, "{$pk} = ?", $id)) {
            $this->row = $this->dirty_row = $this->referer_result = array();
            $this->adapter = null;
        }

        $this->fireEvent('after destroy');

        return $affect;
    }

    /**
     * 从数据库内重新获取值
     *
     * @access public
     * @return self
     */
    public function refersh() {
        if (!$id = $this->id()) return $this;

        $adapter = $this->getAdapter();

        $sql = sprintf(
            'select * from %s where %s = ?',
            $adapter->qtab(static::$table_name),
            $adapter->qcol(static::$primary_key)
        );

        $row = $adapter->execute($sql, $id)->getRow();

        if ($row) {
            $this->row = $row;
            $this->dirty_row = array();
        }
        return $this;
    }

    /**
     * 监听事件
     *
     * @param string $name
     * @param callable $callback
     * @access public
     * @return void
     */
    public function addEvent($name, $callback) {
        Events::instance()->addEvent($this, $name, $callback);
    }

    /**
     * 触发事件
     *
     * @param string $name
     * @access protected
     * @return void
     */
    protected function fireEvent($name) {
        Events::instance()->fireEvent($this, $name);
    }

    /**
     * 转换为数组
     *
     * @access public
     * @return array
     */
    public function toArray() {
        return $this->row;
    }

    /**
     * 从数据库内查询
     *
     * @param Adapter $adapter
     * @static
     * @access public
     * @return Select
     */
    static public function select(Adapter $adapter = null) {
        if (!$adapter) $adapter = Db::connect();

        $class = get_called_class();
        $processor = function($row) use ($class, $adapter) {
            $ar = $row ? new $class($row, true) : new $class(array(), false);
            $ar->setAdapter($adapter);
            return $ar;
        };

        $select = $adapter->select(static::$table_name)
                          ->setProcessor($processor);
        return $select;
    }

    /**
     * 根据主键值得到对象
     *
     * @param mixed $id
     * @param Adapter $adapter
     * @static
     * @access public
     * @return ActiveRecord
     */
    static public function find($id, Adapter $adapter = null) {
        $pk = static::$primary_key;
        return static::select($adapter)->where("{$pk} = ?", $id)->get(1);
    }

    public function __before_init() {}
    public function __after_init() {}
    public function __before_save() {}
    public function __after_save() {}
    public function __before_insert() {}
    public function __after_insert() {}
    public function __before_update() {}
    public function __after_update() {}
    public function __before_destroy() {}
    public function __after_destroy() {}
}
