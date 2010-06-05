<?php
/**
 * curl助手类
 */
class Ly_Helper_Curl {
    protected $default_options = array(
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
    );

    protected $response = null;

    public function __construct(array $options = null) {
        if (!extension_loaded('curl'))
            throw new Exception('Helper_Curl: curl extension must be loaded before use');

        if (!is_null($options))
            $this->default_options = array_merge($this->default_options, $options);
    }

    /**
     * 发送请求
     *
     * @param string $url
     * @param array $data
     * @param string $method
     * @param array $config
     * @access protected
     * @return boolean
     */
    protected function _request($method, $url, array $data = null, array $options = null) {
        // 清除上次请求的数据内容
        $this->response = null;

        $options = is_null($options)
                 ? $this->default_options
                 : array_merge($this->default_options, $options);

        if ($method == 'get') {
            if ($data) $url = $url .'?'. http_build_query($data);
        } elseif ($method == 'post') {
            $options[CURLOPT_POST] = 1;
            if ($data) $options[CURLOPT_POSTFIELDS] = $data;
        }

        $options[CURLOPT_URL] = $url;

        $req = curl_init();
        curl_setopt_array($req, $options);

        $result = curl_exec($req);

        if ($result === false) {
            $message = curl_error($req);
            $code = curl_errno($req);
            curl_close($req);

            throw new Exception($message, $code);
        }

        $this->response = array(
            'url' => curl_getinfo($req, CURLINFO_EFFECTIVE_URL),
            'code' => curl_getinfo($req, CURLINFO_HTTP_CODE),
            'size_upload' => curl_getinfo($req, CURLINFO_SIZE_UPLOAD),
            'size_download' => curl_getinfo($req, CURLINFO_SIZE_DOWNLOAD),
            'speed_upload' => curl_getinfo($req, CURLINFO_SPEED_UPLOAD),
            'speed_download' => curl_getinfo($req, CURLINFO_SPEED_DOWNLOAD),
            'total_time' => curl_getinfo($req, CURLINFO_TOTAL_TIME),
            'body' => $result,
        );
        curl_close($req);

        return $this->response;
    }

    /**
     * get指定url
     * 返回response body
     *
     * @param string $url
     * @param array $data
     * @access public
     * @return string
     */
    public function get($url, array $data = null) {
        $this->_request('get', $url, $data);
        $response = $this->response;
        return $response['body'];
    }

    /**
     * 向指定url post数据
     * 返回response body
     *
     * @param string $url
     * @param array $data
     * @access public
     * @return string
     */
    public function post($url, array $data) {
        $this->_request('post', $url, $data);
        $response = $this->response;
        return $response['body'];
    }

    /**
     * 用post模拟put动作
     * 用http-x-method-override重载
     * 服务器端必须支持这种重载才行
     *
     * @param string $url
     * @param array $data
     * @access public
     * @return string
     */
    public function put($url, array $data) {
        $options = array(
            CURLOPT_HTTPHERADER => array('HTTP-X-METHOD-OVERRIDE: PUT')
        );
        $this->_request('post', $url, $data, $options);
        $response = $this->response;
        return $response['body'];
    }

    /**
     * 用get模拟delete动作
     * 如果返回的http status为200则表示删除成功
     *
     * @param string $url
     * @param array $data
     * @access public
     * @return boolean
     */
    public function delete($url, array $data = null) {
        $options = array(
            CURLOPT_HTTPHERADER => array('HTTP-X-METHOD-OVERRIDE: DELETE')
        );
        $this->_request('get', $url, $data, $options);
        $response = $this->response;
        return $response['code'] == 200;
    }

    /**
     * 获得上一次请求的详细信息
     *
     * @access public
     * @return array
     */
    public function getResponse() {
        return $this->response;
    }
}
