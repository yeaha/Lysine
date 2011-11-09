<?php
namespace Lysine\MVC;

use Lysine\Error;

class View {
    /**
     * 视图文件存放路径
     *
     * @var string
     * @access protected
     */
    protected $view_dir;

    /**
     * 视图文件扩展名
     *
     * @var string
     * @access protected
     */
    protected $file_ext = 'php';

    /**
     * 本视图继承的上层视图
     *
     * @var string
     * @access protected
     */
    protected $extend_file;

    /**
     * 视图数据
     *
     * @var array
     * @access protected
     */
    protected $vars = array();

    /**
     * block堆栈
     *
     * @var array
     * @access protected
     */
    protected $block_stack = array();

    /**
     * 保存每个区域生成的数据
     * 区域名为key
     *
     * @var array
     * @access protected
     */
    protected $blocks = array();

    /**
     * 每个区域的输出方式
     * append 或 replace
     *
     * @var array
     * @access protected
     */
    protected $block_config = array();

    public function __construct(array $config = null) {
        if ( !$config = ($config ?: cfg('app', 'view')) )
            throw new Error('Invalid View config');

        foreach ($config as $key => $val)
            $this->$key = $val;
    }

    /**
     * 清除所有数据
     *
     * @access public
     * @return void
     */
    public function reset() {
        $this->extend_file = null;
        $this->vars = array();

        $this->blocks = $this->block_config = $this->block_stack = array();

        return $this;
    }

    /**
     * 魔法方法
     *
     * @param string $key
     * @access public
     * @return mixed
     */
    public function __get($key) {
        return array_key_exists($key, $this->vars)
             ? $this->vars[$key]
             : false;
    }

    /**
     * 魔法方法
     *
     * @param string $key
     * @param mixed $val
     * @access public
     * @return void
     */
    public function __set($key, $val) {
        $this->set($key, $val);
    }

    /**
     * 设定视图数据
     *
     * @param string $key
     * @param mixed $val
     * @access public
     * @return self
     */
    public function set($key, $val) {
        $this->vars[$key] = $val;
        return $this;
    }

    /**
     * 获得视图数据
     *
     * @param string $key
     * @param mixed $default
     * @access public
     * @return mixed
     */
    public function get($key = null, $default = false) {
        if (is_null($key)) return $this->vars;

        if (array_key_exists($key, $this->vars))
            return $this->vars[$key];

        return $default;
    }

    /**
     * 获得真正的视图文件名
     *
     * @param string $file
     * @access protected
     * @return string
     */
    protected function findFile($file) {
        $ext = $this->file_ext ?: 'php';

        if (!is_file($file))  // 不是绝对路径
            $file = $this->view_dir .'/'. $file;

        $pathinfo = pathinfo($file);
        if (!isset($pathinfo['extension']) || $pathinfo['extension'] != $ext)
            $file .= '.'. $ext;

        if (!$fullname = realpath($file))
            throw Error::file_not_found($file);

        return $fullname;
    }

    /**
     * 生成视图渲染结果
     *
     * @param mixed $file
     * @param array $vars
     * @param boolean $reset
     * @access public
     * @return string
     */
    public function render($file, array $vars = array(), $reset = true) {
        if ($reset) $this->reset();

        foreach ($vars as $key => $val)
            $this->set($key, $val);

        $output = $this->includes($file, array(), true);

        // 如果没有继承其它视图，就直接输出结果
        if (!$extend_file = $this->extend_file)
            return $output;

        $this->extend_file = null;
        return $this->render($extend_file, array(), false);
    }

    /**
     * 包含视图文件
     *
     * @param string $file
     * @param array $vars
     * @param boolean $return_content
     * @access protected
     * @return void
     */
    protected function includes($file, array $vars = array(), $return_content = false) {
        $file = $this->findFile($file);
        $vars = $vars ? array_merge($this->vars, $vars) : $this->vars;

        ob_start();
        try {
            extract($vars);
            require $file;
        } catch (\Exception $ex) {
            while (ob_get_level()) ob_end_clean();
            throw $ex;
        }

        // 安全措施，关闭掉忘记关闭的block
        $this->endBlock($all = true);

        $output = ob_get_clean();
        if ($return_content) return $output;
        echo $output;
    }

    /**
     * 指定继承的视图
     *
     * @param string $file
     * @access protected
     * @return void
     */
    protected function extend($file) {
        $this->extend_file = $file;
    }

    /**
     * 区域开始
     * 所有的数据都会被output buffer接管
     *
     * @param string $name
     * @param string $config
     * @access protected
     * @return void
     */
    protected function block($name, $config = 'replace') {
        $this->block_config[$name] = $config;
        $this->block_stack[] = $name;
        ob_start();
    }

    /**
     * 区域结束
     * 从output buffer中返回数据
     *
     * @access protected
     * @return void
     */
    protected function endBlock($all = false) {
        if (!$this->block_stack) return false;

        while ($all)
            if (!$this->endBlock(false)) return true;

        $block_name = array_pop($this->block_stack);
        $output = ob_get_clean();

        // 是否有继承上来的block
        if (isset($this->blocks[$block_name])) {
            if ($this->block_config[$block_name] == 'append') {
                $output .= $this->blocks[$block_name];
            } else {    // 默认用继承来的下层block覆盖上层block
                $output = $this->blocks[$block_name];
            }
        }

        // 如果继承了其它视图并且没有嵌套block，把输出内容放到$this->blocks内
        if ($this->extend_file && !$this->block_stack) {
            $this->blocks[$block_name] = $output;
        } else {
            unset($this->blocks[$block_name]);
            echo $output;
        }

        return true;
    }

    /**
     * 直接显示block内容
     *
     * @param string $name
     * @access protected
     * @return void
     */
    protected function showBlock($name) {
        if (!isset($this->blocks[$name])) return false;
        echo $this->blocks[$name];
        unset($this->blocks[$name]);
    }
}
