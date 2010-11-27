<?php
namespace Lysine\MVC;

use Lysine\Error;
use Lysine\HttpError;
use Lysine\MVC\Router;
use Lysine\MVC\Router_Abstract;
use Lysine\Utils\Singleton;

/**
 * Application
 *
 * @uses Singleton
 * @package MVC
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Application extends Singleton {
    /**
     * 路由器
     *
     * @var Lysine\MVC\Router_Abstract
     * @access protected
     */
    protected $router;

    /**
     * 文件搜索路径
     *
     * @var array
     * @access protected
     */
    protected $include_path = array();

    /**
     * 构造函数
     *
     * @access private
     * @return void
     */
    protected function __construct() {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * 设定路由类
     *
     * @param Router_Abstract $router
     * @access public
     * @return Lysine\MVC\Application
     */
    public function setRouter(Router_Abstract $router) {
        $this->router = $router;
        return $this;
    }

    /**
     * 获得当前使用的路由类
     *
     * @access public
     * @return Lysine\MVC\Router_Abstract
     */
    public function getRouter() {
        if (!$this->router) $this->router = new Router();
        return $this->router;
    }

    /**
     * 设定文件包含路径
     * 这里实际上不是修改php include_path设置
     * 只是为application默认的autoloader提供文件查找路径
     *
     * @param mixed $path
     * @access public
     * @return Lysine\MVC\Application
     */
    public function includePath($path) {
        if (is_array($path)) {
            $this->include_path = array_merge($this->include_path, $path);
        } else {
            $this->include_path[] = $path;
        }

        return $this;
    }

    /**
     * 自定义类的autoloader
     * 会覆盖默认的autoloader
     *
     * @param callable $loader
     * @param boolean $throw
     * @param boolean $prepend
     * @access public
     * @return Lysine\MVC\Application
     */
    public function setAutoloader($loader, $throw = true, $prepend = false) {
        if (!is_callable($loader))
            throw Error::not_callable('Application loader');

        spl_autoload_unregister(array($this, 'loadClass'));
        spl_autoload_register($loader, $throw, $prepend);
        return $this;
    }

    /**
     * 默认的autoloader
     *
     * \Controller\User => controller/user.php
     * \Controller\User\Login => controller/user/login.php
     * \Controller\User_Login => controller/user.php
     *
     * @param string $class
     * @access public
     * @return boolean
     */
    public function loadClass($class) {
        $pos = strpos($class, '_', strrpos($class, '\\'));
        $find = ($pos === false) ? $class : substr($class, 0, $pos);
        $find = str_replace('\\', '/', strtolower($find)) .'.php';

        foreach ($this->include_path as $path) {
            $file = $path .'/'. $find;
            if (!is_file($file)) continue;

            require $file;
            return class_exists($class, false) || interface_exists($class, false);
        }

        return false;
    }

    /**
     * 调用路由分发http请求
     *
     * @param string $url
     * @param array $params
     * @access protected
     * @return mixed
     */
    protected function dispatch($url, array $params = array()) {
        return $this->getRouter()->dispatch($url, $params);
    }

    /**
     * http重定向
     *
     * @param string $url
     * @param int $code
     * @access public
     * @return Lysine\MVC\Response
     */
    public function redirect($url, $code = 303) {
        return resp()->setCode($code)->setHeader('Location', $url)->setBody(null);
    }

    /**
     * 重新路由到指定的地方
     *
     * @param string $url
     * @param array $options
     * @access public
     * @return mixed
     */
    public function forward($url, array $options = null) {
        return $this->dispatch($url, $options);
    }

    /**
     * 执行application
     *
     * @access public
     * @return mixed
     */
    public function run() {
        $req = req();
        if (!in_array($req->method(), array('get', 'post', 'put', 'delete', 'head')))
            throw HttpError::not_implemented($req->method());

        $url = parse_url($req->requestUri());
        return $this->dispatch($url['path']);
    }
}
