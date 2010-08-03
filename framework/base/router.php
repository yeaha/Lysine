<?php
namespace Lysine;

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
    protected $base_namespace;

    /**
     * url regex => controller 映射
     * 使用正则表达式匹配当前请求的url
     * dispatch到对应的controller
     *
     * @var array
     * @access protected
     */
    protected $map = array();

    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $cfg = cfg('app', 'router');
        $cfg = is_array($cfg) ? $cfg : array();

        $this->map = isset($cfg['map']) ? $cfg['map'] : array();

        $this->base_namespace = isset($cfg['base_namespace'])
                              ? $cfg['base_namespace']
                              : 'Controller';
    }

    /**
     * 正则匹配查询
     *
     * @param string $url
     * @access protected
     * @return array
     */
    protected function match($url) {
        foreach ($this->map as $re => $class) {
            if (preg_match($re, $url, $match))
                return array($class, array_slice($match, 1));
        }

        $class = str_replace('/', '\\', trim($url, '/'));
        if (!$class) $class = 'index';
        return array($this->base_namespace .'\\'. $class, array());
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
        list($class, $args) = $this->match($url);

        if (!class_exists($class))
            throw new Request_Exception('Page Not Found', 404);

        Events::instance()->fireEvent($this, 'before dispatch', $class, $args);

        if ($params) $args = array_merge($args, $params);

        $req = req();
        $method = $req->method();

        $fn = $method;
        if ($req->isAJAX()) {
            if (method_exists($class, 'ajax_'. $method)) {
                $fn = 'ajax_'. $method;
            } elseif (method_exists($class, 'ajax')) {
                $fn = 'ajax';
            }
        }

        $handle = new $class();
        $handle->app = app();
        $handle->req = $req;

        if (method_exists($handle, 'preRun')) {
            // 如果preRun返回了内容，就直接完成动作
            // 可以在这里进行某些阻断操作
            // 正常的内容不应该通过这里输出
            $resp = call_user_func_array(array($handle, 'preRun'), $args);
            if ($resp) return $resp;
        }

        // 不使用method_exists()检查，用is_callable()
        // 保留__call()重载方法的方式
        if (!is_callable(array($handle, $fn)))
            throw new Request_Exception('Not Acceptable', 406);
        $resp = call_user_func_array(array($handle, $fn), $args);

        // 这里有机会对输出结果进行进一步处理
        if (method_exists($handle, 'postRun')) {
            $result = call_user_func(array($handle, 'postRun'), $resp);
            if ($result) $resp = $result;
        }

        Events::instance()->fireEvent($this, 'after dispatch', $class, $args, $resp);

        return $resp;
    }
}
