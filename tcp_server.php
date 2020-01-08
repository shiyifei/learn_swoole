<?php
/**
 * 在Server程序中如果需要执行很耗时的操作，比如一个聊天服务器发送广播，Web服务器中发送邮件。
 * 如果直接去执行这些函数就会阻塞当前进程，导致服务器响应变慢。
 * Swoole提供了异步任务处理的功能，可以投递一个异步任务到TaskWorker进程池中执行，不影响当前请求的处理速度。
 * 基于一个TCP服务器，只需要增加onTask和onFinish2个事件回调函数即可。
 * 另外需要设置task进程数量，可以根据任务的耗时和任务量配置适量的task进程。
 * 在异步IO的程序中，不得使用sleep/usleep/time_sleep_until/time_nanosleep。
 */

//创建server对象, 监听9000端口
$serv = new swoole_server('192.168.56.102', 9000);

//设置异步任务的工作进程数量
$serv->set(['worker_num'=>2, 'task_worker_num'=>4]);

//监听连接进入事件
$serv->on('connect', function($serv, $fd) {
    echo "Client:connect. \n";
});

//此事件在Worker进程/Task进程启动时发生
$serv->on('WorkerStart', function($serv, $workerId) {
    // var_dump(get_included_files()); //此数组中的文件表示进程启动前就加载了，所以无法reload
});

//监听数据发送事件
$serv->on('receive', function($serv, $fd, $from_id, $data) {
    echo "fd={$fd},from_id={$from_id}, data={$data}\n";

    //注意:onReceive事件中执行了sleep函数，server在100秒内无法再收到任何客户端请求。
    // sleep(100); 

    // var_dump($data);    
    //传入的data是有换行符的，所以要用trim()函数
    if (trim($data) == 'send mail') {
        echo "arrive here, send mail \n";
        $task_id = $serv->task($data);
        echo "Dispatch AsyncTask:task id={$task_id}\n";
    } else {
        //向连接的客户端发送数据，发送过程是异步的，底层会自动监听可写，将数据逐步发送给客户端
        //send操作具有原子性,多个进程同时调用send向同一个TCP连接发送数据，不会发生数据混杂。
        $ret = $serv->send($fd, 'Server:' . $data);
        //发送成功$ret会返回true
        if (!$ret) {
            echo 'send data failed:'.$serv->getLastError(); //发送失败时,使用getLastError()方法得到失败的错误码
        }
    }

    $info = $serv->stats();
    var_dump($info);

    //可以主动关闭客户端连接
    //$serv->close($serv->worker_id);
    //$serv->close($fd);
});

//处理异步任务
$serv->on('task', function($serv, $task_id, $from_id, $data){
    echo "worker_pid:{$serv->worker_pid}, New AsyncTask[id={$task_id}]".PHP_EOL;

    $info = $serv->stats();
    var_dump($info);

    //返回任务执行的结果
    //Server->finish是可选的。如果Worker进程不关心任务执行的结果，不需要调用此函数
    //在onTask回调函数中return字符串，等同于调用finish
    $serv->finish("{$data}->OK");
});

//处理异步任务的结果
$serv->on('finish', function($serv, $task_id, $data){
    echo "worker_pid:{$serv->worker_pid},AsyncTask[$task_id] Finish: {$data}".PHP_EOL;
});


//监听连接关闭事件
$serv->on('close', function($serv, $fd){
    echo "Client:close. \n";
});

//增加一个监听端口，监听成功后可以在port对象上单独设置事件回调函数和运行参数
$port = $serv->addListener('192.168.56.102', 9001, SWOOLE_SOCK_TCP);
$port->on('receive', function($serv, $fd, $from_id, $data) {
    echo "fd={$fd},from_id={$from_id}, data={$data}\n";
});

/**
 * 增加一个用户进程，用户进程实现了广播功能，循环接收管道信息，并发给服务器的所有连接
 */
$process = new Swoole\Process(function($process) use($serv){
    while (true) {
        $msg = $process->read();//接收主进程发送来的信息
        foreach ($serv->connections as $conn) {
            $serv->send($conn, $msg);
        }
    }
});
$serv->addProcess($process);

/*$serv->on('receive', function($serv, $fd, $reactor_id, $data) use($process){
    //群发收到的消息
    $process->write($data);
});*/

//启动服务器
$serv->start();