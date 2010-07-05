<?php
namespace Lysine;

use Lysine\IRouter;
use Lysine\Utils\Events;

class Router extends Events implements IRouter {
    protected $base_namespace;
    protected $map;

    public function __construct() {
        $cfg = cfg('app', 'router');
        $cfg = is_array($cfg) ? $cfg : array();

        $this->map = isset($cfg['map']) ? $cfg['map'] : array();

        $this->base_namespace = isset($cfg['base_namespace'])
                              ? $cfg['base_namespace']
                              : 'Controller';
    }

    protected function match($url) {
        foreach ($this->map as $re => $class) {
            if (preg_match($re, $url, $match))
                return array($class, array_slice($match, 1));
        }

        $class = str_replace('/', '\\', trim($url, '/'));
        if (!$class) $class = 'index';
        return array($this->base_namespace .'\\'. $class, array());
    }

    public function dispatch($url, array $params = array()) {
        list($class, $args) = $this->match($url);

        if (!class_exists($class))
            throw new Request_Exception('Page Not Found', 404);

        $this->fireEvent('before dispatch', $class, $args);

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

        // response数据以引用方式传递给postRun
        // 这里有机会对输出结果进行进一步处理
        if (method_exists($handle, 'postRun')) call_user_func(array($handle, 'postRun'), &$resp);

        $this->fireEvent('after dispatch', $class, $args, $resp);

        return $resp;
    }
}
