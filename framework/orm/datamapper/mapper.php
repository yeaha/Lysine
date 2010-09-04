<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM\DataMapper\Data;
use Lysine\ORM\DataMapper\Meta;
use Lysine\Storage\Pool;

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
     * 保存新的领域模型数据到存储服务
     *
     * @param Data $data
     * @abstract
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    abstract public function create(Data $data);

    /**
     * 更新领域模型数据到存储服务
     *
     * @param Data $data
     * @abstract
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    abstract public function update(Data $data);

    /**
     * 在存储服务中删除领域模型数据
     *
     * @param Data $data
     * @abstract
     * @access public
     * @return boolean
     */
    abstract public function delete(Data $data);

    /**
     * 根据主键生成领域模型实例
     *
     * @param mixed $key
     * @abstract
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    abstract public function find($key);

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
        return Meta::factory($this->class);
    }

    /**
     * 获得存储服务连接实例
     *
     * @access public
     * @return Lysine\IStorage
     */
    public function getStorage() {
        return Pool::instance()->get(
            $this->getMeta()->getStorage()
        );
    }

    /**
     * 把从存储中获得的数据转化为属性数组
     *
     * @param array $record
     * @access public
     * @return array
     */
    public function recordToProps(array $record) {
        $props = array();
        foreach ($this->getMeta()->getPropOfField() as $field => $prop)
            $props[$prop] = $record[$field];

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
        $fop = $this->getMeta()->getFieldOfProp();

        foreach ($props as $prop => $val) {
            if (!isset($fop[$prop])) continue;
            $record[$fop[$prop]] = $val;
        }

        return $record;
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

        $mapper_class = get_called_class();
        if (!isset(self::$instance[$class])) self::$instance[$class] = new $mapper_class($class);
        return self::$instance[$class];
    }
}
