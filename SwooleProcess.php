<?php
/**
 * 在 swoole 4.3.0版本中，process支持了协程，可以直接使用协程相关API,也可以使用协程调度的方式来实现进程间通信
 * 注意事项
    主进程中不要使用协程，否则在协程空间内，可能无法fork子进程。主进程内还是继续使用同步阻塞或者异步的模式来管理进程
    两个子进程之间实际上只需要一个管道即可完成通信
    管道类型是SOCK_STREAM时，多个进程同时写入一个进程的管道，可能存在风险，可能会产生数据错乱。
    存在交叉写入时可以使用SOCK_DGRAM格式的管道，或者进行加锁
 */


class SwooleProcess
{
    /**
     * 4.3.0版本Process类的构造方法增加了第四个参数，传入true表示开启协程。
     * 开启协程后，可以直接调用协程相关API，无需自行创建。
     * @return [type] [description]
     */
    public function enableCoroutine()
    {
        $proc = new \swoole_process(function(){
            co::sleep(2);
            echo 'process start successfully'."\n";
        },false, 1, true);
        $proc->start();

        //回收结束运行的子进程
        swoole_process::wait(true);
    }

    /**
     * 4.3.0版本新增了Process::exportSocket可以将管道导出为一个Swoole\Coroutine\Socket对象，
     * 通过读写此Socket就可以实现通信。
     * @return [type] [description]
     */
    public function processCommunication()
    {
        $proc1 = new \swoole_process(function(swoole_process $proc){

            $socket = $proc->exportSocket();
            echo 'in procese 1, receive message:'.$socket->recv();
            $socket->send("hello proc2 \n");
            echo "proc1 stop \n";

        }, false, 1, true);

        assert($proc1->start());

        $proc2 = new \swoole_process(function(swoole_process $proc) use($proc1) {

            Co::sleep(0.01);
            $socket = $proc1->exportSocket();
            $socket->send("hello proc1 \n");
            echo 'in process 2, receive message:'.$socket->recv();
            echo "proc2 stop \n";

        }, false, 1, true);

        assert($proc2->start());

        swoole_process::wait(true);
        swoole_process::wait(true);
    }
}

$obj = new SwooleProcess();
$obj->enableCoroutine();
$obj->processCommunication();

