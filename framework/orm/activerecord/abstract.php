<?php
namespace Lysine\Orm;

use Lysine\IStorage;
use Lysine\Utils\Events;
use Lysine\Storage\Pool;

interface IActiveRecord {
    static public function find($key, IStorage $storage = null);
}

abstract class ActiveRecord implements IActiveRecord {
    static protected $storage_config;
    static protected $collection;
    static protected $primary_key;
    static protected $props_config = array(
        /*
        'orders' => array(
            'getter' => 'getOrders',
            'setter' => 'setOrders',
        ),
        'books' => array(
            'getter' => array('getBooks', true),  // 把第一次获取的结果缓存起来，不重复调用
        ),
        */
    );

    static protected $referer_config = array();

    protected $storage;
    protected $row = array();
    protected $dirty_row = array();
    protected $props = array();
    protected $referer = array();

    abstract public function getReferer($name);
    abstract public function save($refersh = true);
    abstract public function destroy();
    abstract public function refresh();

    public function __construct(array $row = array(), $from_storage = false) {
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

        $events->addEvent($this, 'before refresh', array($this, '__before_refresh'));
        $events->addEvent($this, 'after refresh', array($this, '__after_refresh'));

        $this->fireEvent('before init');

        if ($row) $this->row = $row;
        if (!$from_storage) $this->dirty_row = array_keys($row);

        $this->fireEvent('after init');
    }

    public function __destruct() {
        Events::instance()->clearEvent($this);
    }

    public function __call($fn, $args) {
        if ($fn == 'getStorage') {  // 获得当前实例的storage
            if (!$this->storage) $this->storage = static::getStorage();
            return $this->storage;
        }
    }

    public function __set($key, $val) {
        if (isset(static::$props_config[$key]['setter'])) {
            $fn = static::$props_config[$key]['setter'];
            $this->$fn($val);

            unset($this->props[$key]);  // 清除掉getter的结果
        } else {
            $this->set($key, $val);
        }
    }

    public function __get($key) {
        if (isset(static::$props_config[$key]['getter'])) {
            if (isset($this->props[$key])) return $this->props[$key];

            $config = static::$props_config[$key]['getter'];

            if (is_array($config)) {
                $fn = array_shift($config);
                $cache = array_shift($config);
            } else {
                $fn = $config;
                $cache = false;
            }

            $prop = $this->$fn();
            if ($cache) $this->props[$key] = $prop;

            return $prop;
        }

        if (isset(static::$referer_config[$key]))
            return $this->getReferer($key);

        return $this->get($key);
    }

    public function id() {
        return $this->get(static::$primary_key);
    }

    public function set($col, $val = null, $direct = false) {
        if (is_array($col)) {
            $direct = (boolean)$val;
        } else {
            $col = array($col => $val);
        }

        $pk = static::$primary_key;
        foreach ($col as $key => $val) {
            if ($key == $pk && isset($this->row[$pk]) && $this->row[$pk])
                throw new \LogicException(__CLASS__ .': primary key refuse update');

            $this->row[$key] = $val;
            if (!$direct) $this->dirty_row[] = $key;
        }
        if (!$direct) $this->dirty_row = array_unique($this->dirty_row);

        return $this;
    }

    public function get($col) {
        if (array_key_exists($col, $this->row)) return $this->row[$col];
        return false;
    }

    public function addEvent($name, $callback) {
        Events::instance()->addEvent($this, $name, $callback);
    }

    public function fireEvent($name) {
        Events::instance()->fireEvent($this, $name);
    }

    public function toArray() {
        return $this->row;
    }

    public function setStorage(IStorage $storage) {
        $this->storage = $storage;
        return $this;
    }

    static public function getStorage() {
        return Pool::instance()->get(static::$storage_config);
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

    public function __before_refresh() {}
    public function __after_refresh() {}
}
