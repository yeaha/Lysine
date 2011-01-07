<?php
namespace Lysine\Utils {
    use Lysine\Error;

    // 对php curl函数的封装
    // 方便其它类继续封装，并且满足OOP恶趣味;)
    class Curl {
        static public $return_transfer = true;
        static public $return_header = false;
        static public $verbose = false;

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

        public function setUrl($url) {
            $this->url = $url;
            return $this;
        }

        public function setOption($option, $value = null) {
            if (is_array($option)) {
                foreach ($option as $opt => $val) {
                    if (is_array($val) && isset($this->options[$opt])) {
                        $this->options[$opt] = array_merge($this->options[$opt], $val);
                    } else {
                        $this->options[$opt] = $val;
                    }
                }
            } else {
                $this->options[$option] = $value;
            }
            return $this;
        }

        public function getInfo($info = null) {
            if (!$this->handler) return false;
            return ($info === null) ? curl_getinfo($this->handler) : curl_getinfo($this->handler, $info);
        }

        public function exec() {
            if (!$this->handler) $this->handler = curl_init();

            $handler = $this->handler;
            $options = $this->options;

            if ($this->url && !isset($options[CURLOPT_URL]))
                $options[CURLOPT_URL] = $this->url;

            if (!isset($options[CURLOPT_RETURNTRANSFER]) && static::$return_transfer)
                $options[CURLOPT_RETURNTRANSFER] = true;
            if (!isset($options[CURLOPT_HEADER]) && static::$return_header)
                $options[CURLOPT_HEADER] = true;
            if (!isset($options[CURLOPT_VERBOSE]) && static::$verbose)
                $options[CURLOPT_VERBOSE] = true;

            curl_setopt_array($handler, $options);

            $this->result = curl_exec($handler);
            $this->error = ($this->result === false)
                         ? array(curl_error($handler), curl_errno($handler))
                         : array('', 0);

            return $this->result;
        }

        public function close() {
            if ($this->handler) {
                curl_close($this->handler);
                $this->handler = null;
            }
            return $this;
        }
    }
}

namespace Lysine\Utils\Curl {
    use Lysine\Error;
    use Lysine\MVC\Response;

    class Http extends \Lysine\Utils\Curl {
        // 使用post方法模拟put delete
        static public $method_emulate = false;

        public function setAuth($user, $passwd, $type = CURLAUTH_ANY) {
            $this->setOption(array(
                CURLOPT_USERPWD => $user .':'. $passwd,
                CURLOPT_HTTPAUTH => $type,
            ));
            return $this;
        }

        protected function isSuccess() {
            $code = $this->getResponseCode();
            return ($code >= 200 && $code < 400);
        }

        public function getResponseCode() {
            return $this->getInfo(CURLINFO_HTTP_CODE);
        }

        public function send($method, array $params = array()) {
            $method = strtoupper($method);
            $options = array();

            if ($method == 'GET' || $method == 'HEAD') {
                if ($params) {
                    $url = $this->url ?: $this->options[CURLOPT_URL];
                    $url .= '?'. http_build_query($params);
                    $options[CURLOPT_URL] = $url;
                }
                if ($method == 'GET') {
                    $options[CURLOPT_HTTPGET] = true;
                } else {
                    $options[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                    $options[CURLOPT_HEADER] = true;
                    $options[CURLOPT_NOBODY] = true;
                }
            } elseif ($method == 'POST') {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $params;
            } else {
                if (static::$method_emulate) {
                    $options[CURLOPT_HTTPHEADER][] = 'X-HTTP-METHOD-OVERRIDE: '. $method;
                    $options[CURLOPT_POSTFIELDS] = $params;
                } else {
                    $options[CURLOPT_CUSTOMREQUEST] = $method;
                    // 数组必须用http_build_query转换为字符串
                    // 否则会使用multipart/form-data而不是application/x-www-form-urlencoded
                    if ($params) $options[CURLOPT_POSTFIELDS] = http_build_query($params);
                }
            }

            $this->setOption($options);

            $this->exec();
            if (!$this->isSuccess())
                throw new HttpError($this->getResponseCode());

            return $this->result;
        }

        public function head(array $params = array()) {
            return $this->send('HEAD', $params);
        }

        public function get(array $params = array()) {
            return $this->send('GET', $params);
        }

        public function post(array $params) {
            return $this->send('POST', $params);
        }

        public function put(array $params) {
            return $this->send('PUT', $params);
        }

        public function delete() {
            return $this->send('DELETE');
        }
    }

    class HttpError extends Error {
        public function __construct($code) {
            $message = Response::httpStatus($code);
            parent::__construct($message, $code);
        }
    }
}
