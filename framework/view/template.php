<?php
/**
 * 模板视图渲染类
 *
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
class Ly_View_Template {
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
    protected $inherit_file;

    /**
     * 视图数据
     *
     * @var array
     * @access protected
     */
    protected $vars = array();

    /**
     * 当前区域名
     *
     * @var string
     * @access protected
     */
    protected $current_block;

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

    public function __construct(array $config) {
        while (list($key, $val) = each($config))
            $this->$key = $val;
    }

    /**
     * clone后要清除已有的继承关系
     *
     * @access public
     * @return void
     */
    public function __clone() {
        $this->inherit_file = null;
    }

    /**
     * 指定视图数据
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
     * 清除视图数据
     *
     * @access public
     * @return self
     */
    public function clean() {
        $this->vars = array();
        return $this;
    }

    /**
     * 获得真正的视图文件名
     *
     * @param string $file
     * @access protected
     * @return string
     */
    protected function findFile($file) {
        return $file;
    }

    /**
     * 生成视图渲染结果
     *
     * @param mixed $file
     * @access public
     * @return string
     */
    public function fetch($file) {
        $file = $this->findFile($file);

        ob_start();
        extract($this->vars, EXTR_REFS);
        include $file;
        $output = ob_get_clean();

        // 如果没有继承其它视图，就直接输出结果
        if (!$this->inherit_file) return $output;

        // clone一个当前的template对象
        // clone对象会包含所有原来的配置信息
        // 同时也会包含本次运行产生的block生成数据
        // 用这些数据从inherit file得到结果
        $tpl = clone $this;
        return $tpl->fetch($this->inherit_file);
    }

    /**
     * 包含其它视图
     *
     * @param string $file
     * @param array $vars
     * @access protected
     * @return void
     */
    protected function includes($file, array $vars = null) {
        extract($this->vars, EXTR_REFS);
        if ($vars) extract($this->vars, EXTR_REFS);
        include $this->findFile($file);
    }

    /**
     * 指定继承的视图
     *
     * @param string $file
     * @access protected
     * @return void
     */
    protected function inherit($file) {
        $this->inherit_file = $file;
    }

    /**
     * 区域开始
     *
     * @param string $name
     * @param string $config
     * @access protected
     * @return void
     */
    protected function block($name, $config = 'replace') {
        $this->block_config[$name] = $config;
        $this->current_block = $name;
        ob_start();
    }

    /**
     * 区域结束
     *
     * @access protected
     * @return void
     */
    protected function endblock() {
        $output = ob_get_clean();

        $block_name = $this->current_block;
        // 是否有继承上来的block
        if (isset($this->blocks[$block_name])) {
            if ($this->block_config[$block_name] == 'append') {
                $output .= $this->blocks[$block_name];
            } else {    // 默认用继承上来的下层block覆盖上层block
                $output = $this->blocks[$block_name];
            }
        }

        // 如果继承了其它视图，把输出内容放到$this->blocks内
        if ($this->inherit_file) {
            $this->blocks[$block_name] = $output;
            $this->current_block = null;
        } else {    // 否则直接输出
            echo $output;
        }
    }
}
