<?php
namespace Lysine\Db;

use Lysine\Db\Pool;
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
     * 连接池中的数据库连接名
     *
     * @var string
     * @static
     * @access protected
     * @see Lysine\Db\Pool
     */
    static protected $adapter_name;

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
            'where' => 'is_deleted = 0',
            'limit' => 1
        ),
        'books' => array(
            'class' => 'Book',
            'source_key' => 'id',
            'target_key' => 'author_id',
            'where' => array('is_deleted = ?', 0),
            'order' => 'create_time DESC',
            'dispatcher' => array(
                'group' => 'book',
                'by_column' => 'pk', // string or array
            ),
        ),
        'orders' => array(
            'getter' => 'getOrders',
        ),
        */
    );

    /**
     * 数据库连接
     *
     * @var IAdapter
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
     * 得到引用数据的adapter
     *
     * @param array $referer_config
     * @access protected
     * @return IAdapter
     */
    protected function getRefererAdapter(array $referer_config) {
        if (!isset($referer_config['dispatcher'])) return null;

        $dconfig = $referer_config['dispatcher'];
        if (!isset($dconfig['group']))
            throw new \UnexpectedValueException('Please specify referer dispatcher group name');
        $args = array($dconfig['group']);

        $column = isset($dconfig['by_column'])
                ? $dconfig['by_column']
                : static::$primary_key;
        array_splice($args, count($args), 0, $column);

        return call_user_func_array(array(Pool::instance(), 'dispatch'), $args);
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
            throw new \InvalidArgumentException('Undefined activerecord referer name['. $name .']');

        $config = $referer[$name];
        if (isset($config['getter'])) {
            $getter = $config['getter'];
            if (!method_exists($this, $getter))
                throw new \UnexpectedValueException('Activerecord referer getter['. $getter .'] not exist');

            $result = $this->$getter();
            $this->referer_result[$name] = $result;
            return $result;
        }

        if (isset($config['class'])) {
            $class = $config['class'];
            if (!is_subclass_of($class, 'Lysine\Db\ActiveRecord'))
                throw new \UnexpectedValueException('Activerecord referer class must be subclass of Lysine\Db\ActiveRecord');

            $select = forward_static_call(array($class, 'select'), $this->getRefererAdapter($config));
            $adapter = $select->getAdapter();

            if (isset($config['source_key'], $config['target_key'])) {
                $target_key = $adapter->qcol($config['target_key']);
                $where = "{$target_key} = ?";
                $bind = $this->get($config['source_key']);
                $select->where($where, $bind);
            } else {
                throw new \UnexpectedValueException('MUST specify activerecord referer source_key AND target_key');
            }

            if (isset($config['where'])) {
                if (is_array($config['where'])) {
                    call_user_func_array(array($select, 'where'), $config['where']);
                } else {
                    $select->where($config['where']);
                }
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
     * @param IAdapter $adapter
     * @access public
     * @return self
     */
    public function setAdapter(IAdapter $adapter) {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * 得到数据库连接
     *
     * @access public
     * @return IAdapter
     */
    public function getAdapter() {
        if (!$this->adapter) $this->adapter = Pool::instance()->getAdapter(static::$adapter_name);
        return $this->adapter;
    }

    /**
     * 保存进数据库
     *
     * @param boolean $refresh 保存成功后重新获取数据
     * @access public
     * @return self
     */
    public function save($refresh = true) {
        $pk = static::$primary_key;
        $adapter = $this->getAdapter();
        $table_name = static::$table_name;

        $row = $this->row;
        // 没有任何字段被改动过，而且主键值不为空
        // 说明这是从数据库中获得的数据，而且没改过，不需要保存
        if (!$this->dirty_row && !$row[$pk]) return $this;

        $this->fireEvent('before save');

        if ($row[$pk]) {
            $method = 'update';
            // 有被改动过的主键值
            // 说明是新建数据，然后指定的主键，需要insert
            // 这个类的主键是不允许update的
            // 所以不可能出现主键值被改了，需要update的情况
            if (in_array($pk, $this->dirty_row)) $method = 'insert';
        } else {
            // 没有主键值，肯定是新建数据，需要insert
            $method = 'insert';
        }

        if ($method == 'insert') {
            $this->fireEvent('before insert');

            if ($affected = $adapter->insert($table_name, $this->row)) {
                if (!isset($row[$pk]))
                    $this->set($pk, $adapter->lastId($table_name, $pk), /* direct */true);

                $this->fireEvent('after insert');
            }
        } else {
            $this->fireEvent('before update');

            $col = $adapter->qcol($pk);
            $affected = $adapter()->update($table_name, $row, "{$col} = ?", $row[$pk]);

            if ($affected) $this->fireEvent('after update');
        }

        $this->fireEvent('after save');

        if ($refresh AND $affected) $this->refresh();
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
        if ($affected = $adapter->delete(static::$table_name, "{$pk} = ?", $id)) {
            $this->row = $this->dirty_row = $this->referer_result = array();
            $this->adapter = null;
        }

        $this->fireEvent('after destroy');

        return $affected;
    }

    /**
     * 从数据库内重新获取值
     *
     * @access public
     * @return self
     */
    public function refresh() {
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
     * @param IAdapter $adapter
     * @static
     * @access public
     * @return Select
     */
    static public function select(IAdapter $adapter = null) {
        if (!$adapter) $adapter = Pool::instance()->getAdapter(static::$adapter_name);

        $class = get_called_class();
        $processor = function($row) use ($class, $adapter) {
            $ar = $row ? new $class($row, true) : new $class(array(), false);
            $ar->setAdapter($adapter);
            return $ar;
        };

        $select = $adapter->select(static::$table_name)
                          ->setProcessor($processor)
                          ->setKeyColumn(static::$primary_key);
        return $select;
    }

    /**
     * 根据主键值得到对象
     *
     * @param mixed $id
     * @param IAdapter $adapter
     * @static
     * @access public
     * @return ActiveRecord
     */
    static public function find($id, IAdapter $adapter = null) {
        $select = static::select($adapter);

        if (is_array($id)) {
            return $select->whereIn($pk, $id)->get();
        } else {
            return $select->where("{$pk} = ?", $id)->get(1);
        }
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
