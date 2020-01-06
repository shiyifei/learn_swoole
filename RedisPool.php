<?php declare(strict_types=1);
/**
 * Redis连接池
 */

class RedisPool
{
    private $pool;
    private $available = true;

    public function __construct()
    {
        $this->pool = new SplQueue();
    }

    public function put($redis)
    {
        $this->pool->push($redis);
    }

    public function get()
    {
        //有空闲连接且连接池处于可用状态
        if ($this->available && count($this->pool) > 0) {
            return $this->pool->pop();
        }

        //无空闲连接，创建一个新连接
        $redis = new Swoole\Coroutine\Redis();
        $ret = $redis->connect('127.0.0.1', 6379);
        if (empty($ret)) {
            return false;
        } else {
            return $redis;
        }
    }

    public function destruct() {
        //连接池销毁，置不可用状态，防止新的客户端进入常驻连接池，导致服务期无法平滑退出
        $this->available = false;

        while (!$this->pool->isEmpty()) {
            $this->pool->pop();
        }
    }



}