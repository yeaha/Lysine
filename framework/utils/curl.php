<?php
namespace Lysine\Utils {
    use Lysine\Error;

    // 对php curl函数的封装
    // 方便其它类继续封装，并且满足OOP恶趣味;)
    class Curl {
        protected $handler;
        protected $url;
        protected $options = array();

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

            curl_setopt_array($handler, $options);

            $result = curl_exec($handler);
            if ($result === false)
                throw new Error('Curl Error: '. curl_error($handler), curl_errno($handler));

            return $result;
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

        protected function send($method, array $params = array()) {
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
                    $options[CURLOPT_NOBODY] = true;
                }
            } elseif ($method == 'POST') {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = http_build_query($params);
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

            $options[CURLOPT_RETURNTRANSFER] = true;
            $options[CURLOPT_HEADER] = true;

            $this->setOption($options);

            $result = $this->exec();
            $message = array(
                'info' => $this->getInfo()
            );

            $header_size = $message['info']['header_size'];
            $message['header'] = preg_split('/\r\n/', substr($result, 0, $header_size), 0, PREG_SPLIT_NO_EMPTY);
            $message['body'] = substr($result, $header_size);

            return $message;
        }
    }
}
