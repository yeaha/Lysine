<?php
namespace Lysine\Storage;

use Lysine\IStorage;

/**
 * 文件存储方式封装
 * 只封装了写，还没有读和查找，有需求再考虑
 *
 * @uses IStorage
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class File implements IStorage {
    // 文件名
    private $filename;

    private $open_mode = 'a';

    // 缓存尺寸，大于缓存后会flush到文件
    private $buffer_size = 2048;

    // 缓存
    private $buffer = array();

    // 当前内容尺寸
    private $content_size = 0;

    public function __construct(array $config) {
        $this->filename = strftime($config['filename'], time());
        if (isset($config['buffer_size'])) $this->buffer_size = (int)$config['buffer_size'];
        if (isset($config['open_mode'])) $this->open_mode = $config['open_mode'];
    }

    public function __destruct() {
        $this->flush();
    }

    public function flush() {
        if (!$this->content_size) return true;

        if (!$fp = @fopen($this->filename, $this->open_mode)) return false;

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, implode("\n", $this->buffer) ."\n");
            flock($fp, LOCK_UN);
            $this->buffer = array();
            $this->content_size = 0;
        }

        return fclose($fp);
    }

    public function write($line) {
        if (is_array($line)) {
            foreach ($line as $content) {
                $this->buffer[] = $content;
                $this->content_size += strlen($content);
            }
        } else {
            $this->buffer[] = $line;
            $this->content_size += strlen($line);
        }

        if ($this->content_size >= $this->buffer_size)
            $this->flush();
    }
}
