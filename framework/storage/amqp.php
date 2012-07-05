<?php // README: Advanced Message Queue Protocol Service
namespace Lysine\Storage;

use \AMQPChannel;
use \AMQPConnection;
use \AMQPEnvelope;
use \AMQPExchange;
use \AMQPQueue;

if (!extension_loaded('amqp'))
    throw Error::require_extension('amqp');

class AMQP implements \Lysine\IStorage {
    private $connection;
    private $channel;

    public function __construct(array $config) {
        $this->connection = new AMQPConnection( self::prepareConfig($config) );
    }

    public function connection() {
        return $this->connection;
    }

    public function channel($new = false) {
        if (!$new && $this->channel)
            return $this->channel;

        $connection = $this->connection;

        if (!$connection->isConnected())
            $connection->connect();

        return $this->channel = new AMQPChannel($connection);
    }

    public function exchange_declare($name, $type = null, $flag = null, $arguments = null) {
        $exchange = new AMQPExchange($this->channel());
        $exchange->setName($name);

        $exchange->setType($type ?: AMQP_EX_TYPE_DIRECT);

        if ($flag !== null)
            $exchange->setFlags($flag);

        if ($arguments !== null)
            $exchange->setArguments($arguments);

        $exchange->declare();
        return $exchange;
    }

    public function queue_declare($name, $flag = null, $arguments = null) {
        $queue = new AMQPQueue($this->channel());
        $queue->setName($name);

        if ($flag !== null)
            $queue->setFlags($flag);

        if ($arguments !== null)
            $queue->setArguments($arguments);

        $queue->declare();
        return $queue;
    }

    static private function prepareConfig(array $config) {
        return array(
            'host' => isset($config['host']) ? $config['host'] : ini_get('amqp.host'),
            'vhost' => isset($config['vhost']) ? $config['vhost'] : ini_get('amqp.vhost'),
            'port' => isset($config['port']) ? $config['port'] : ini_get('amqp.port'),
            'login' => isset($config['login']) ? $config['login'] : ini_get('amqp.login'),
            'password' => isset($config['password']) ? $config['password'] : ini_get('amqp.password'),
        );
    }
}
