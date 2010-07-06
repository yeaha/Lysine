<?php
namespace Lysine;

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
     * 清除所有数据
     *
     * @access public
     * @return void
     */
    public function reset() {
        $this->inherit_file = null;
        $this->vars = array();

        $this->current_block = null;
        $this->blocks = array();
        $this->block_config = array();

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
        if (!$file) throw new InvalidArgumentException('Invalid view file');

        // 没有以/开头的是相对路径，转换为view dir下的文件
        if (substr($file, 0, 1) != '/')
            $file = sprintf('%s/%s', $this->view_dir, $file);

        // 没有指定文件扩展名
        if (!pathinfo($file, PATHINFO_EXTENSION)) $file .= '.'. $this->file_ext;

        if (!is_readable($file))
            throw new \RuntimeException('View file('. $file .') is not exist or readable!');

        return $file;
    }

    /**
     * 生成视图渲染结果
     *
     * @param mixed $file
     * @access public
     * @return string
     */
    public function fetch($file, array $vars = null) {
        $file = $this->findFile($file);

        if ($vars) {
            while (list($key, $val) = each($vars))
                $this->set($key, $val);
        }

        ob_start();

        extract($this->vars);
        include $file;
        // 安全措施，关闭掉忘记关闭的block
        if ($this->current_block) $this->endblock();

        $output = ob_get_clean();

        // 如果没有继承其它视图，就直接输出结果
        if (!$this->inherit_file) return $output;

        $inherit_file = $this->findFile($this->inherit_file);

        $this->inherit_file = null;
        return $this->fetch($inherit_file);
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
        $file = $this->findFile($file);

        extract($this->vars);
        if ($vars) extract($vars);
        include $file;
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
     * 所有的数据都会被output buffer接管
     *
     * @param string $name
     * @param string $config
     * @access protected
     * @return void
     */
    protected function block($name, $config = 'replace') {
        // 如果上一个block忘记关闭，这里先关闭掉
        if ($this->current_block) $this->endblock();

        $this->block_config[$name] = $config;
        $this->current_block = $name;
        ob_start();
    }

    /**
     * 区域结束
     * 从output buffer中返回数据
     *
     * @access protected
     * @return void
     */
    protected function endblock() {
        if (!$this->current_block) return false;

        $block_name = $this->current_block;
        $this->current_block = null;

        $output = ob_get_clean();

        // 是否有继承上来的block
        if (isset($this->blocks[$block_name])) {
            if ($this->block_config[$block_name] == 'append') {
                $output .= $this->blocks[$block_name];
            } else {    // 默认用继承来的下层block覆盖上层block
                $output = $this->blocks[$block_name];
            }
        }

        // 如果继承了其它视图，把输出内容放到$this->blocks内
        if ($this->inherit_file) {
            $this->blocks[$block_name] = $output;
        } else {
            unset($this->blocks[$block_name]);
            echo $output;
        }
    }
}
