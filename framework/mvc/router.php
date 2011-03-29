<?php
/**
 * 路由名字空间配置说明
 *
 * 所有的controller都使用Action这个namespace
 * $config['app']['router']['namespace'] = 'Action';
 * 对应结果
 * /login == Action\login
 * /admin/login == Action\admin\login
 * /other/login == Action\other\login
 *
 * 不同路径对应不同名字空间的controller
 * $config['app']['router']['namespace'] = array(
 *     '__default' => 'Controller',
 *     'admin' => 'Admin\Controller',
 *     'other' => 'Other\Action',
 * );
 * 对应结果
 * /login == Controller\login
 * /admin/login == Admin\Controller\login
 * /other/login == Other\Action\login
 *
 * 如果没有配置，或者没有找到，默认使用"Controller"
 */
namespace Lysine\MVC;

use Lysine\HttpError;
use Lysine\MVC\Response;

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
        $args = func_get_args();
        $params = is_array(end($args)) ? array_pop($args) : array();
        $actions = is_array(reset($args)) ? array_shift($args) : $args;

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
     * 默认Controller namespace
     *
     * @var string
     * @access protected
     */
    protected $default = 'Controller';

    /**
     * Controller类的名字空间配置
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
    protected $dispatch_rewrite = array();

    /**
     * 构造函数
     *
     * @param array $config
     * @access public
     * @return void
     */
    public function __construct(array $config = null) {
        $config = ($config ?: cfg('app', 'router')) ?: array();

        if (isset($config['rewrite'])) $this->dispatch_rewrite = $config['rewrite'];
        if (isset($config['namespace'])) $this->namespace = $config['namespace'];
    }

    /**
     * 得到url对应的controller namespace
     *
     * @access public
     * @return string
     */
    public function getNamespace($url) {
        $default = $this->default;
        $namespace = $this->namespace;
        if (!$namespace) return array($default, $url);
        if (!is_array($namespace)) return array($namespace, $url);

        if (isset($namespace['__default__'])) {
            $default = $namespace['__default__'];
            unset($namespace['__default__']);
        }

        // 匹配到namespace的url需要把匹配部分去掉
        // /admin/abc 匹配到 Admin\Controller
        // 如果不去掉/admin
        // 后面的match返回的controller class就是Admin\Controller\admin\abc
        // 正确的应该是Admin\Controller\abc
        foreach ($namespace as $start_with => $ns) {
            $regex = '#^(/*)'. $start_with .'(/(.+)?)?$#';
            if (!preg_match($regex, $url)) continue;

            $url = preg_replace('#^(/*)'. $start_with .'#', '', $url);
            return array(rtrim($ns, '\\'), $url);
        }

        return array($default, $url);
    }

    /**
     * 设定url regex => controller映射关系
     *
     * @param array $rewrite
     * @access public
     * @return Lysine\MVC\Router
     */
    public function setDispatchRewrite(array $rewrite) {
        $this->dispatch_rewrite = $rewrite;
        return $this;
    }

    /**
     * 解析url，返回对应的controller
     * 先尝试正则路由匹配
     * 再根据url组装Controller类的名字加载
     *
     * @param string $url
     * @access protected
     * @return array
     */
    protected function match($url) {
        foreach ($this->dispatch_rewrite as $re => $class) {
            if (preg_match($re, $url, $match)) {
                if (DEBUG) \Lysine\logger('mvc')->debug('Found url rewrite rule: '. $re);
                return array($class, array_slice($match, 1));
            }
        }

        // url: /user/login
        // controller: \Controller\User\Login
        list($namespace, $url) = $this->getNamespace($url);

        $class = str_replace('/', '\\', trim($url, '/')) ?: 'index';
        $class = $namespace .'\\'. $class;
        return array($class, array());
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
        if (DEBUG) $logger = \Lysine\logger('mvc');

        $url = strtolower(rtrim($url, '/'));
        if (DEBUG) $logger->debug('Dispatch url:'. $url);

        list($class, $args) = $this->match($url);
        if (DEBUG) $logger->debug('Match url controller as '. $class);

        if (!$class || !class_exists($class)) throw HttpError::page_not_found(array('controller' => $class));

        if ($params) $args = array_merge($args, $params);
        fire_event($this, BEFORE_DISPATCH_EVENT, array($url, $class, $args));

        $controller = new $class();
        if (method_exists($controller, '__before_run')) {
            // 如果__before_run返回了内容，就直接完成动作
            // 可以在这里进行某些阻断操作
            // 正常的内容不应该通过这里输出
            if ($resp = call_user_func_array(array($controller, '__before_run'), $args))
                return ($resp instanceof Response) ? $resp : resp()->setBody($resp);
        }

        $request = req();
        $method = $request->method();
        // head方法除了不输出数据之外，和get方法没有区别
        if ($method == 'head') $method = 'get';

        if (DEBUG) {
            $log = 'Call controller ['. $class .'] method ['. $method .']';
            if ($args) $log .= ' with '. json_encode($args);
            $logger->info($log);
        }

        // 执行controller动作并返回结果
        // 不检查method是否存在，用is_callable()
        // 保留__call()重载方法的方式
        if (!is_callable(array($controller, $method)))
            throw HttpError::method_not_allowed(array(
                'url' => $url,
                'controller' => $class,
            ));
        $resp = call_user_func_array(array($controller, $method), $args);

        // 这里有机会对输出结果进行进一步处理
        if (method_exists($controller, '__after_run')) $controller->__after_run($resp);

        fire_event($this, AFTER_DISPATCH_EVENT, array($url, $class, $args, $resp));

        return ($resp instanceof Response) ? $resp : resp()->setBody($resp);
    }
}
