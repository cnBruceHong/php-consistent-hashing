<?php
/**
 * Created by PhpStorm.
 * User: hongxu
 * Date: 2017/7/28
 * Time: 17:44
 */

// turn on the strict mode in PHP7+
declare(strict_types=1);

/**
 * Class ConsistentHashing
 */
class ConsistentHashing
{
    /**
     * you can set the number of virtual node,
     * more virtual nodes you set, the balance of the ConsistentHashing is better
     *
     * @var int
     */
    protected $replicate = 0;

    /**
     * In order to remove the node easily,
     * this array save the set of the cache position of each node.
     *
     * @var array
     */
    protected $nodes = [];

    /**
     * save the cache position
     *
     * @var array
     */
    public $cachePosition = [];

    /**
     * you know, this is a constructor.
     *
     * @param int $replicate
     */
    public function __construct(int $replicate = 3)
    {
        $this->replicate = $replicate;
    }

    /**
     * use crc32 to generate the hash and return a 32bit integer
     *
     * @param string $key
     * @return int
     */
    public static function hash(string $key): int
    {
        return (int)sprintf("%u", crc32($key));
    }

    /**
     * add a node to the instance
     *
     * @param string $node
     *
     * @return bool
     */
    public function addNode(string $node): bool
    {
        if (strlen($node) < 1) {
            return false;
        }

        for ($i = 1; $i <= $this->replicate; $i++) {
            $positionKey = self::hash($node . $i);
            $this->nodes[$node][] = $positionKey;
            $this->cachePosition[$positionKey] = $node;
        }

        return true;
    }

    /**
     * remove the failed node from the instance
     *
     * @param string $node
     * @return bool
     */
    public function delNode(string $node): bool
    {
        if (strlen($node) < 1) {
            return false;
        }

        $failedPosition = $this->nodes[$node];
        unset($this->nodes[$node]);

        foreach ($failedPosition as $item) {
            unset($this->cachePosition[$item]);
        }

        return true;
    }

    /**
     * look for the node which the key has saved on
     *
     * @param string $key
     *
     * @return bool|string
     */
    public function lookUp(string $key)
    {
        if (strlen($key) < 1) {
            return false;
        }

        $key = self::hash($key);
        ksort($this->cachePosition);

        foreach ($this->cachePosition as $position => $node) {
            if ($key <= $position) {
                return $node;
            }
        }

        return current($this->cachePosition);
    }
}

// ok, let's begin to test our class
$hits = [
    "192.168.1.1" => 0,
    "192.168.1.2" => 0,
    "192.168.1.3" => 0,
];

$server = new ConsistentHashing(1000);
$server->addNode("192.168.1.1");
$server->addNode("192.168.1.2");
$server->addNode("192.168.1.3");

// you can stop a node manually, and then see what happen
$server->delNode("192.168.1.3");

// we need large dates to mock it
for ($i = 0; $i < 100000; $i++) {
    $hits[$server->lookUp((string)(rand(0, 9999) . time()))] += 1;
}

print_r($hits);

echo "Save on:" . $server->lookUp((string)(rand(0, 9999) . time())) . PHP_EOL;
echo "Save on:" . $server->lookUp((string)(rand(0, 9999) . time())) . PHP_EOL;
echo "Save on:" . $server->lookUp((string)(rand(0, 9999) . time())) . PHP_EOL;
echo "Save on:" . $server->lookUp((string)(rand(0, 9999) . time())) . PHP_EOL;
echo "Save on:" . $server->lookUp((string)(rand(0, 9999) . time())) . PHP_EOL;
