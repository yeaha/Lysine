<?php
namespace Lysine\Utils {
    use Lysine\Error;

    // 对php curl函数的封装
    // 方便其它类继续封装，并且满足OOP恶趣味;)
    class Curl {
        protected $handler;
        protected $url;
        protected $options = array();
        protected $error = array('', 0);
        protected $result;

        public function __construct($url = null) {
            if (!extension_loaded('curl')) throw Error::require_extension('curl');
            if ($url) $this->url = $url;
        }

        public function __destruct() {
            $this->close();
        }

        public function __clone() {
            if ($this->handler) $this->handler = curl_copy_handle($this->handler);
        }

        public function __get($key) {
            return $this->$key;
        }

        public function reset() {
            $this->close();
            $this->handler = null;
            $this->options = array();
            $this->error = array('', 0);
            $this->result = null;

            return $this;
        }

        public function setOption($option, $value = null) {
            if (is_array($option)) {
                $this->options = array_merge($this->options, $option);
            } else {
                $this->options[$option] = $value;
            }
            return $this;
        }

        public function getInfo($info = 0) {
            if (!$this->handler) return false;
            return curl_getinfo($this->handler, $info);
        }

        public function exec() {
            if (!$this->handler) $this->handler = curl_init();

            $handler = $this->handler;
            $options = $this->options;

            if ($this->url && !isset($options[CURLOPT_URL]))
                $options[CURLOPT_URL] = $this->url;
            curl_setopt_array($handler, $options);

            $this->result = curl_exec($handler);
            $this->error = ($this->result === false)
                         ? array(curl_error($handler), curl_errno($handler))
                         : array('', 0);

            return $this->result;
        }

        public function close() {
            if ($this->handler) curl_close($this->handler);
            return $this;
        }
    }
}

namespace Lysine\Utils\Curl {
    class Http extends \Lysine\Utils\Curl {
        // 使用post方法模拟put delete
        static public $method_emualte = false;

        protected function isSuccess() {
            $code = $this->getInfo(CURLINFO_HTTP_CODE);
            return ($code >= 200 && $code < 400);
        }

        public function send($method, array $params = array()) {
        }

        public function get(array $params = array()) {
            return $this->send('GET', $params);
        }

        public function post(array $params = array()) {
            return $this->send('POST', $params);
        }

        public function put(array $params = array()) {
            return $this->send('PUT', $params);
        }

        public function delete(array $params = array()) {
            return $this->send('DELETE', $params);
        }
    }
}
