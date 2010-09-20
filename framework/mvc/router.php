<?php
namespace Lysine;

use Lysine\HttpError;
use Lysine\Utils\Events;

/**
 * http路由基类
 *
 * @abstract
 * @package MVC
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class Router_Abstract {
    /**
     * 分发请求
     * 返回值可以是字符串或者实现了__toString()方法的类实例
     *
     * @param string $url
     * @param array $params
     * @access public
     * @return mixed
     */
    abstract public function dispatch($url, array $params = array());

    /**
     * url('aa', 'bb') -> /aa/bb
     * url('aa', 'bb', array('a' => 'A', 'b' => 'B')) -> /aa/bb?a=A&b=B
     * url(array('aa', 'bb'), array('a' => 'A', 'b' => 'B')) -> /aa/bb?a=A&b=B
     *
     * @param mixed $actions
     * @access public
     * @return string
     */
    static public function url($actions, $params = array()) {
        switch (func_num_args()) {
            case 1:
                if (!is_array($actions)) $actions = array($actions);
                break;
            case 2:
                if (!is_array($actions)) $actions = array($actions);

                if (!is_array($params)) {
                    array_push($actions, $params);
                    $params = array();
                }
                break;
            default:
                $actions = func_get_args();
                $count = count($actions);
                if (is_array($actions[$count - 1])) {
                    $params = array_pop($actions);
                } else {
                    $params = array();
                }
        }

        $url = '/'. implode('/', $actions);
        if ($params) $url .= '?'. http_build_query($params);
        return $url;
    }
}

/**
 * Lysine默认路由
 * webpy风格
 *
 * @uses Router_Abstract
 * @package MVC
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Router extends Router_Abstract {
    /**
     * Controller类的名字空间
     *
     * @var mixed
     * @access protected
     */
    protected $namespace;

    /**
     * url regex => controller 映射
     * 使用正则表达式匹配当前请求的url
     * dispatch到对应的controller
     *
     * @var array
     * @access protected
     */
    protected $dispatch_map = array();

    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $cfg = cfg('app', 'router');
        $cfg = is_array($cfg) ? $cfg : array();

        $this->dispatch_map = isset($cfg['map']) ? $cfg['map'] : array();

        $this->namespace = isset($cfg['namespace'])
                              ? $cfg['namespace']
                              : 'Controller';
    }

    /**
     * 返回controller所在的namespace名字
     *
     * @access public
     * @return string
     */
    public function getNamespace() {
        return $this->namespace;
    }

    /**
     * 设定url regex => controller映射关系
     *
     * @param array $map
     * @access public
     * @return Lysine\Router
     */
    public function setDispatchMap(array $map) {
        $this->dispatch_map = $map;
        return $this;
    }

    /**
     * 解析url，返回对应的controller
     *
     * @param string $url
     * @access protected
     * @return array
     */
    protected function match($url) {
        foreach ($this->dispatch_map as $re => $class) {
            if (preg_match($re, $url, $match))
                return array($class, array_slice($match, 1));
        }

        // url: /user/login
        // controller: \Controller\User\Login
        $class = str_replace('/', '\\', trim($url, '/'));
        if (!$class) $class = 'index';
        return array($this->namespace .'\\'. $class, array());
    }

    /**
     * 分发请求到对应的controller
     * 执行并返回结果
     *
     * @param string $url
     * @param array $params
     * @access public
     * @return mixed
     */
    public function dispatch($url, array $params = array()) {
        list($classname, $args) = $this->match($url);

        if (!class_exists($classname))
            throw HttpError::page_not_found($url);

        if ($params) $args = array_merge($args, $params);
        Events::instance()->fireEvent($this, 'before dispatch', $classname, $args);

        // 反射对象，检查controller类
        $class = new \ReflectionClass($classname);
        $controller = new $classname();

        $method = req()->method();
        if (req()->isAJAX()) {
            if ($class->hasMethod('ajax_'. $method)) {
                $method = 'ajax_'. $method;
            } elseif ($class->hasMethod('ajax')) {
                $method = 'ajax';
            }
        }

        if ($class->hasMethod('beforeRun')) {
            // 如果beforeRun返回了内容，就直接完成动作
            // 可以在这里进行某些阻断操作
            // 正常的内容不应该通过这里输出
            $resp = call_user_func_array(array($controller, 'beforeRun'), $args);
            if ($resp) return $resp;
        }

        // 执行controller动作并返回结果
        // 不检查method是否存在，用is_callable()
        // 保留__call()重载方法的方式
        if (!is_callable(array($controller, $method)))
            throw HttpError::not_acceptable(array(
                'controller' => $controller,
                'method' => $method,
            ));
        $resp = call_user_func_array(array($controller, $method), $args);

        // 这里有机会对输出结果进行进一步处理
        if ($class->hasMethod('afterRun')) $controller->afterRun($resp);

        Events::instance()->fireEvent($this, 'after dispatch', $classname, $args, $resp);

        return $resp;
    }
}
