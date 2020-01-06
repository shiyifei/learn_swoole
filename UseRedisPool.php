<?php  declare(strict_types=1);
/**
 * 如何在多个协程间共用同一个协程客户端，这里以redis协程客户端为例
 * 与同步阻塞程序不同，协程是并发处理请求的，
 * 因此同一时间可能会有很多个请求在并行处理，一旦共用客户端连接，就会导致不同协程之间发生数据错乱。
 * Date: 2020/1/5
 * Time: 13:05
 */
include_once('RedisPool.php');

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

class UseRedisPool
{
    public function __construct()
    {
        $redisPool = new RedisPool();
        $server = new Swoole\Http\Server('192.168.1.102', 9501);
        $server->set([
            //如果开启异步安全重启，则需要在workerExit事件中释放连接池
            'reload_async' => true,
        ]);
        $server->on('start', function(Server $server){
            echo "server is started, pid:" . $server->master_pid;
        });
        $server->on('workerExit', function(Server $server) use($redisPool){
            $redisPool->destruct();
        });

        $server->on('request', function(Request $request, Response $response) use($redisPool) {
            echo 'request is coming.'."\n";
            $redis = $redisPool->get();
            if (empty($redis)) {
                $response->end('connect redis error');
                return;
            }
            $key = $request->get['key'];
            var_dump($key);
            $value = $redis->get($key);
            var_dump($key, $value);
            $info = 'key:'.$key.',value='.$value;
            $response->end($info);

            $redisPool->put($redis);
        });

        $server->start();
    }
}

$obj = new UseRedisPool();