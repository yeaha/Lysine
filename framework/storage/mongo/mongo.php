<?php
namespace Lysine\Storage;

use Lysine\IStorage;

class Mongo extends IStorage {
    private $handle;

    public function __construct(array $config) {
        if (!extension_loaded('mongo'))
            throw new \RuntimeException('Need mongo extension!');

        list($dsn, $options) = self::parseConfig($config);
        $this->handle = new \Mongo($dsn, $options);
    }

    public function __get($prop) {
        return $this->handle->$prop;
    }

    public function __call($fn, $args) {
        return call_user_func_array(array($this->handle, $fn), $args);
    }

    static public function parseConfig(array $config) {
        if (isset($config['dsn'])) {
            $dsn = $config['dsn'];
        } elseif (isset($config['servers']) && is_array($config['servers'])) {
            $dsn = 'mongodb://'. implode(',', $config['servers']);
        } else {
            $server = isset($config['server']) ? $config['server'] : ini_get('mongo.default_host');
            $port = isset($config['port']) ? $config['port'] : ini_get('mongo.default_port');
            $dsn = sprintf('mongodb://%s:%s', $server, $port);
        }

        $options = (isset($config['options']) && is_array($config['options']))
                 ? $config['options']
                 : array();

        return array($dsn, $options);
    }
}
