<?php
namespace Lysine\Storage;

use Lysine\IStorage;

class Mongo extends \Mongo implements IStorage {
    public function __construct(array $config) {
        list($dsn, $options) = self::parseConfig($config);
        parent::__construct($dsn, $options);
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
