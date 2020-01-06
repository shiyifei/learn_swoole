<?php declare(strict_types=1);
/**
 * 本文件演示进程隔离相关示例
 * 进程隔离时如何共享全局变量
 * Date: 2020/1/5
 * Time: 20:21
 */

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

class ProcessIsolation
{
    /**
     * 同时调用三个方法时，只有第一个服务启动能成功，为什么呢？
     */
    public function __construct()
    {
        $this->wrongShareVar1();
//        $this->wrongShareVar2();
//        $this->writeShareVar();
    }

    /**
     * 这种方式竟然能生效，在多次请求之后会发现返回值会递增
     * 每一个请求到来时都会生成一个协程，协程虽然相互隔离，但是可以共享http服务的全局变量吗？
     * 如果该服务有两个工作进程，实际上能看出效果，全局变量不会双倍增长。
     * 可以使用 ab工具测试一下， ab -n 1000 -c 2 http://192.168.1.102:9500/
     * 实际上会发现全局变量的值不固定，但是很少有超过500的情况，所以全局变量也不是一直递增的。
     * 多个worker进程之间是不会共享全局变量的。
     * 
     */
    public function wrongShareVar1()
    {
        $server = new Swoole\Http\Server('192.168.1.102', 9500);
        $server->set(['worker_num'=>2]);
        $i = 1;
        $server->on('start', function(Server $server){
            echo "111 server is started, pid:" . $server->master_pid;
        });
        $server->on('Request', function(Request $request, Response $response) {
            echo 'one request is coming'."\n";
            global $i;
            $response->end($i++);
        });
        $server->start();
    }

    /**
     * 这种共享全局变量的方式，其实无效，但是能访问到，$i一直是1
     * 在每次请求后修改全局变量的值，其实并没有改变全局变量的值
     */
    public function wrongShareVar2()
    {
        $server2 = new Swoole\Http\Server('192.168.1.102', 9501);
        global $i;
        $i = 1;
        $server2->on('start', function(Server $server){
            echo "222 server is started, pid:" . $server->master_pid;
        });
        $server2->on('Request', function(Request $request, Response $response) use($i) {
            echo 'one request is coming'."\n";
            $response->end($i++);
        });
        $server2->start();
    }

    /**
     * 这种方式是swoole提倡的共享全局变量的方式
     * Atomic数据是建立在共享内存之上的，使用add方法加1时，在其他工作进程内也是有效的
     */
    public function writeShareVar()
    {
        $server3 = new Swoole\Http\Server('192.168.1.102', 9502);
        $atomic = new Swoole\Atomic(1);
        $server3->on('start', function(Server $server){
            echo "333 server is started, pid:" . $server->master_pid;
        });
        $server3->on('Request', function(Request $request, Response $response) use($atomic) {
            echo 'one request is coming'."\n";
           $response->end($atomic->add(1));
        });
        $server3->start();

    }
}

$obj = new ProcessIsolation();

