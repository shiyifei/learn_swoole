<?php
/**
 * sleep函数会使进程陷入睡眠阻塞
 */

function printChar1($count) {
    for ($i=0; $i<$count; ++$i) {
        $max = ord('a')+26;
        $char = ord('a');
        for (; $char < $max; $char++) {
            printf("%c", $char);
        }
    }
    echo "\n";
}

function printChar2($count) {
    for ($i=0; $i<$count; ++$i) {
        $max = ord('A')+26;
        $char = ord('A');
        for (; $char < $max; $char++) {
            printf("%c", $char);
        }
    }
    echo "\n";
}

//未使用协程的效率
$begin = microtime(true);
printChar1(500);
printChar2(500);

echo "time interval:".round((microtime(true)-$begin)*1000,2)."ms ********************************** \n";

//开启两个协程，检查使用协程后的效率
Swoole\Runtime::enableCoroutine();
$chan = new chan(2);
$begin = microtime(true);
go(function()use($chan){
    printChar1(500);
    $chan->push(['a'=>1]);
});
go(function()use($chan){
    printChar2(500);
    $chan->push(['b'=>1]);
});

go(function() use($chan, $begin) {
    $result = [];
    for ($i=0; $i<2; ++$i) {
        $result += $chan->pop();
    }
    echo "use coroutine, time interval:".round((microtime(true)-$begin)*1000,2)."ms ============= \n";
});


//以下是使用多个协程 并发请求外部接口的例子
$chanReq = new chan(2);
$begin = microtime(true);
go(function() use($chanReq, $begin){
    $result = [];
    for ($i=0;$i<2;++$i) {
        $result += $chanReq->pop();
    }
    echo "concurrent request,result:".json_encode($result).", time interval:".round((microtime(true)-$begin)*1000,2)."ms ######### \n";
});

go(function() use ($chanReq){
    $cli = new Swoole\Coroutine\Http\Client('www.qq.com', 80);
    $cli->set(['timeout'=>5]);
    $cli->setHeaders(['Host'=>'www.qq.com', 'User-Agent'=>'Chrome/49.0.2587.3',
        'Accept'=>'text/html,application/xhtml+xml,application/xml', 'Accept-Encoding'=>'gzip',]);
    $ret = $cli->get('/');

    //如果$cli->body响应内容过大，这里用http状态码来进行测试
    $chanReq->push(['www.qq.com'=>$cli->statusCode]);
    $cli->close();

});

go(function() use($chanReq){
    $cli = new Swoole\Coroutine\Http\Client('www.163.com', 80);
    $cli->set(['timeout'=>5]);
    $cli->setHeaders(['Host'=>'www.163.com', 'User-Agent'=>'Chrome/49.0.2587.3',
        'Accept'=>'text/html,application/xhtml+xml,application/xml', 'Accept-Encoding'=>'gzip',]);
    $ret = $cli->get('/');

    //如果$cli->body响应内容过大，这里用http状态码来进行测试
    $chanReq->push(['www.163.com'=>$cli->statusCode]);
    $cli->close();
});

