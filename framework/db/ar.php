<?php
namespace Lysine\Db;

use Lysine\Db as Db;
use Lysine\Utils\Events;

abstract class ActiveRecord extends Events {
    /**
     * 数据库连接定义在config数据中的路径
     *
     * @var array
     * @access protected
     */
    protected $adapter_path;

    /**
     * 数据库连接
     *
     * @var Adapter
     * @access protected
     */
    protected $adapter;

    /**
     * 对应的数据库表名
     *
     * @var string
     * @access protected
     */
    protected $table_name;

    /**
     * 主键字段名
     *
     * @var string
     * @access protected
     */
    protected $primary_key;

    /**
     * 字段行为定义
     *
     * @var array
     * @access protected
     */
    protected $row_config = array();

    /**
     * 从数据库得到的数据
     *
     * @var array
     * @access protected
     */
    protected $row = array();

    /**
     * 设置后未保存过的数据
     *
     * @var array
     * @access protected
     */
    protected $dirty_row = array();

    /**
     * 引用关系定义
     *
     * @var array
     * @access protected
     */
    protected $referer = array(
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
        if ($from_db) {
            $this->row = $row;
        } else {
            $this->dirty_row = $row;
        }
    }

    /**
     * 得到主键的值
     * 不包括刚刚设置尚未保存的主键值
     *
     * @access public
     * @return mixed
     */
    public function id() {
        return isset($this->row[$this->primary_key])
             ? $this->row[$this->primary_key]
             : false;
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
        $this->set($col, $val);
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

        if (array_key_exists($key, $this->referer))
            return $this->getReferer($key);

        return false;
    }

    /**
     * 设置字段的值
     *
     * @param mixed $col
     * @param mixed $val
     * @access public
     * @return self
     */
    public function set($col, $val = null) {
        // TODO: 根据row_config的情况更新
        if (is_array($col)) {
            $this->dirty_row = array_merge($this->dirty_row, $col);
        } else {
            $this->dirty_row[$col] = $val;
        }
        return $this;
    }

    /**
     * 得到字段的值
     * 未保存的值或者已经保存的值
     *
     * @param string $col
     * @access public
     * @return mixed
     */
    public function get($col) {
        if (array_key_exists($col, $this->dirty_row)) return $this->dirty_row[$col];
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

        if (!isset($this->referer[$name]))
            throw new \InvalidArgumentException('Invalid referer name['. $name .']');

        $config = $this->referer[$name];
        if (isset($config['gettter'])) {
            $result = call_user_func(array($this, $config['getter']));
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
        if (!$this->adapter) {
            $path = $this->adapter_path ? $this->adapter_path : Db::getDefaultPath();
            $this->adapter = Db::connect($path);
        }
        return $this->adapter;
    }

    /**
     * 保存进数据库
     *
     * @access public
     * @return self
     */
    public function save() {
        if (!$this->dirty_row) return $this;

        $this->fireEvent('before save', $this);

        $method = $this->id() ? 'update' : 'insert';

        $pk = $this->primary_key;
        $adapter = $this->getAdapter();
        if ($method == 'insert') {
            $this->fireEvent('before insert', $this);

            if ($affect = $adapter->insert($this->table_name, $this->dirty_row)) {
                if (isset($this->dirty_row[$pk])) {
                    $this->row[$pk] = $this->dirty_row[$pk];
                } else {
                    $this->row[$pk] = $adapter->lastInsertId($this->table_name);
                }

                $this->fireEvent('after insert', $this);
            }
        } else {
            $this->fireEvent('before update', $this);

            $col = $adapter->qcol($pk);
            $affect = $adapter()->update($this->table_name, $this->dirty_row, "{$col} = ?", $this->row[$pk]);

            if ($affect) $this->fireEvent('after update', $this);
        }

        $this->fireEvent('after save', $this);

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
    }

    /**
     * 从数据库内重新获取值
     *
     * @access public
     * @return self
     */
    public function refersh() {
        $id = $this->id();
        if (!$id) return $this;

        $adapter = $this->getAdapter();

        $sql = sprintf(
            'select * from %s where %s = ?',
            $adapter->qtab($this->table_name),
            $adapter->qcol($this->primary_key)
        );

        $row = $adapter->execute($sql, $id)->getRow();

        if ($row) {
            $this->row = $row;
            $this->dirty_row = array();
        }
        return $this;
    }

    /**
     * 转换为数组
     *
     * @access public
     * @return array
     */
    public function toArray() {
        return array_merge($this->row, $this->dirty_row);
    }

    /**
     * 从数据库内查询
     *
     * @static
     * @access public
     * @return Select
     */
    static public function select() {
        $class = get_called_class();
        $processor = function($row) use ($class) {
            return $row ? new $class($row, true) : new $class();
        };

        $select = $this->getAdapter()
                       ->select($this->table_name)
                       ->setProcessor($processor);
        if ($args = func_get_args()) call_user_func_array(array($select, 'where'), $args);
        return $select;
    }

    /**
     * 根据主键值得到对象
     *
     * @param mixed $id
     * @static
     * @access public
     * @return ActiveRecord
     */
    static public function find($id) {
        return static::select()->where("{$this->primary_key} = ?", $id)->get(1);
    }
}
