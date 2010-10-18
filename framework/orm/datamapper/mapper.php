<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM;
use Lysine\ORM\DataMapper\Data;
use Lysine\ORM\DataMapper\Meta;
use Lysine\ORM\Registry;
use Lysine\Storage\Pool;
use Lysine\OrmError;

/**
 * 封装领域模型存储服务数据映射关系
 *
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class Mapper {
    /**
     * 实例集合
     * 每个实例对应一个领域模型类
     */
    static private $instance = array();

    /**
     * 当前实例对应的领域模型类
     *
     * @var string
     * @access protected
     */
    protected $class;

    /**
     * 领域模型元数据
     *
     * @var Lysine\ORM\DataMapper\Meta
     * @access protected
     */
    protected $meta;

    /**
     * 根据主键查找数据
     *
     * @param mixed $id
     * @abstract
     * @access protected
     * @return array
     */
    abstract protected function doFind($id);

    /**
     * 保存数据到存储服务
     *
     * @param array $record
     * @abstract
     * @access protected
     * @return mixed 新数据的主键值
     */
    abstract protected function doInsert(array $record);

    /**
     * 保存更新数据到存储服务
     *
     * @param string $id 主键值
     * @param array $record
     * @abstract
     * @access protected
     * @return boolean
     */
    abstract protected function doUpdate($id, array $record);

    /**
     * 删除指定主键的数据
     *
     * @param mixed $id
     * @abstract
     * @access protected
     * @return boolean
     */
    abstract protected function doDelete($id);

    /**
     * 构造函数
     *
     * @param string $class
     * @access private
     * @return void
     */
    private function __construct($class) {
        $this->class = $class;
    }

    /**
     * 获得领域模型元数据封装
     *
     * @access public
     * @return Lysine\ORM\DataMapper\Meta
     */
    public function getMeta() {
        if (!$this->meta) $this->meta = Meta::factory($this->class);
        return $this->meta;
    }

    /**
     * 获得存储服务连接实例
     *
     * @access public
     * @return Lysine\IStorage
     */
    public function getStorage($args = null) {
        if ($args === null) {
            $args = array();
        } else {
            $args = is_array($args) ? $args : func_get_args();
        }
        array_unshift($args, $this->getMeta()->getStorage());
        return call_user_func_array(array(Pool::instance(), 'get'), $args);
    }

    /**
     * 把从存储中获得的数据转化为属性数组
     *
     * @param array $record
     * @access public
     * @return array
     */
    public function recordToProps(array $record) {
        $prop_of_field = $this->getMeta()->getPropOfField();
        foreach ($record as $field => $value) {
            if (!isset($prop_of_field[$field])) continue;
            $props[$prop_of_field[$field]] = $value;
        }

        return $props;
    }

    /**
     * 把属性数据转换为可以保存到存储服务的数据
     *
     * @param array $props
     * @access public
     * @return array
     */
    public function propsToRecord(array $props) {
        $record = array();
        foreach ($this->getMeta()->getFieldOfProp() as $prop => $field)
            $record[$field] = $props[$prop];

        return $record;
    }

    /**
     * 根据主键返回模型
     *
     * @param mixed $id
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    public function find($id) {
        if ($id instanceof Data) return $id;
        if ($data = Registry::get($this->class, $id)) return $data;
        if (!$record = $this->doFind($id)) return false;
        return $this->package($record);
    }

    /**
     * 保存模型数据到存储服务
     *
     * @param Data $data
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    public function save(Data $data) {
        if ($data->isReadonly())
            throw new \LogicException($this->class .' is readonly');

        $data->fireEvent(ORM::BEFORE_SAVE_EVENT, $data);

        if ($data->isFresh()) {
            $data->fireEvent(ORM::BEFORE_INSERT_EVENT, $data);
            if ($result = $this->insert($data)) $data->fireEvent(ORM::AFTER_INSERT_EVENT, $data);
        } elseif ($data->isDirty()) {
            $data->fireEvent(ORM::BEFORE_UPDATE_EVENT, $data);
            if ($result = $this->update($data)) $data->fireEvent(ORM::AFTER_UPDATE_EVENT, $data);
        }

        $data->fireEvent(ORM::AFTER_SAVE_EVENT, $data);

        return $result;
    }

    /**
     * 保存新的模型数据到存储服务
     * 返回新主键
     *
     * @param Data $data
     * @access protected
     * @return mixed
     */
    protected function insert(Data $data) {
        $record = $this->propsToRecord($data->toArray());
        $primary_key = $this->getMeta()->getPrimaryKey();
        if (!$record[$primary_key]) unset($record[$primary_key]);

        try {
            if (!$id = $this->doInsert($record)) return false;
        } catch (\Exception $ex) {
            throw OrmError::insert_failed($this->class, $record, $ex);
        }

        $record[$primary_key] = $id;
        $data->__fill($this->recordToProps($record));

        Registry::set($data);
        return $id;
    }

    /**
     * 更新领域模型数据到存储服务
     *
     * @param Data $data
     * @access protected
     * @return boolean
     */
    protected function update(Data $data) {
        $record = $this->propsToRecord($data->toArray(/* only dirty */true));

        try {
            if (!$this->doUpdate($data->id(), $record)) return false;
        } catch (\Exception $ex) {
            throw OrmError::update_failed($this->class, $record, $ex);
        }

        $data->__fill($data->toArray());

        return true;
    }

    /**
     * 从存储服务删除模型数据
     *
     * @param Data $data
     * @access public
     * @return boolean
     */
    public function delete(Data $data) {
        if ($data->isReadonly())
            throw new \LogicException($this->class .' is readonly');

        if ($data->isFresh()) return true;

        $id = $data->id();

        $data->fireEvent(ORM::BEFORE_DELETE_EVENT, $data);

        try {
            if (!$this->doDelete($id)) return false;
        } catch (\Exception $ex) {
            throw OrmError::delete_failed($this->class, $id, $ex);
        }

        $data->fireEvent(ORM::AFTER_DELETE_EVENT, $id);
        Registry::remove($this->class, $id);
        return true;
    }

    /**
     * 把存储服务中获得的数据实例化为领域模型
     *
     * @param array $record
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    public function package(array $record) {
        $data_class = $this->class;
        $data = new $data_class;
        $data->__fill($this->recordToProps($record));

        Registry::set($data);
        return $data;
    }

    /**
     * 获得指定领域模型的映射关系实例
     *
     * @param mixed $class
     * @static
     * @access public
     * @return Lysine\ORM\DataMapper\Mapper
     */
    static public function factory($class) {
        if (is_object($class)) $class = get_class($class);

        if (!isset(self::$instance[$class])) {
            $mapper_class = get_called_class();
            self::$instance[$class] = new $mapper_class($class);
        }
        return self::$instance[$class];
    }
}
