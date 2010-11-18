<?php
/**
 * 这个redis类使用了Predis库实现
 * 这里仅仅重新包装构造函数，以适应Storage\Pool的构造要求
 *
 * Predis源代码在 https://github.com/nrk/predis/
 * 使用这个类之前需要先自行载入Predis
 *
 * storage pool配置样例：
 *
 * array(
 *     'storage' => array(
 *         'pool' => array(
 *             'redis' => array(
 *                 'class' => 'Lysine\Storage\Redis',
 *                 'parameters' => array(
 *                     'host' => '127.0.0.1',
 *                     'port' => 6379,
 *                     'database' => 12
 *                 ),
 *                 'clientOptions' => array(
 *                     // 参考Predis
 *                 ),
 *             ),
 *         ),
 *     ),
 * );
 */
namespace Lysine\Storage;

use Lysine\IStorage;

class Redis extends \Predis\Client implements IStorage {
    public function __construct(array $config) {
        $parameters = isset($config['parameters']) ? $config['parameters'] : null;
        $clientOptions = isset($config['clientOptions']) ? $config['clientOptions'] : null;

        parent::__construct($parameters, $clientOptions);
    }
}
