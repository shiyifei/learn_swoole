<?php
/**
 * 在Swoole4中可以使用channel实现协程间的通信、依赖管理、协程同步。
 * 基于channel可以很容易地实现Golang的sync.WaitGrup功能。
 */

class WaitGroup
{
    private $count =0;
    private $chan;

    /**
     * 初始化要等待的协程总数和通道长度
     * @param [type] $num [description]
     */
    public function add($num)
    {   
        $this->chan = new chan($num);
        $this->count = $num;
    }

    /**
     * 表示协程任务已完成
     * @return function [description]
     */
    public function done()
    {
        $this->chan->push(true);
    }

    /**
     * 等待所有任务完成恢复当前协程的执行
     * @return void
     */
    public function wait()
    {
        while($this->count--) {
            $this->chan->pop();
        }
    }
}


go(function(){
    $wg = new WaitGroup();
    $result = [];

    //添加两个协程任务
    $wg->add(2);


    go(function() use($wg, &$result){
        $cli = new Swoole\Coroutine\Http\Client('www.taobao.com', 443, true);
        $cli->setHeaders([
            'Host' => "www.taobao.com",
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
        ]);
        $cli->set(['timeout'=>1]);
        $cli->get('/index.php');

        $result['taobao'] = $cli->statusCode;
        $cli->close();
        $wg->done();
    });



    go(function() use($wg, &$result) {
        $cli = new Swoole\Coroutine\Http\Client('www.baidu.com', 443, true);
        $cli->setHeaders([
            'Host' => "www.baidu.com",
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
        ]);
        $cli->set(['timeout' => 1]);
        $cli->get('/index.php');
        $result['baidu'] = $cli->statusCode;
        $cli->close();
        $wg->done();
    });

    //等待协程任务执行完成
    $wg->wait();

    var_dump($result);


});