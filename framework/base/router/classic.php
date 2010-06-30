<?php
namespace Lysine\Application\Router;

use Lysine as ly;
use Lysine\Application\IRouter;

class Classic implements IRouter {
    protected $base_namespace;

    protected function match($url) {
        if (!$base_namespace = $this->base_namespace) {
            $base_namespace = ly\cfg('app', 'router', 'base_namespace');
            if (!$base_namespace) $base_namespace = 'Controller';
            $this->base_namespace = $base_namespace;
        }

        $class = str_replace('/', '\\', trim($url, '/'));
        if (!$class) $class = 'index';
        return $base_namespace .'\\'. $class;
    }

    public function dispatch($url, array $params = array()) {
        $class = $this->match($url);
        if (!class_exists($class))
            throw new ly\Request_Exception('Page Not Found', 404);

        $req = ly\req();
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
            throw new ly\Request_Exception('Not Acceptable', 406);
        $resp = call_user_func_array(array($handle, $fn), $args);

        // response数据以引用方式传递给postRun
        // 这里有机会对输出结果进行进一步处理
        if (method_exists($handle, 'postRun')) call_user_func(array($handle, 'postRun'), &$resp);

        return $resp;
    }
}
