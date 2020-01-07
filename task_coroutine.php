<?php declare(strict_types=1);

/**
 * Server->taskCo 并发执行task并进行协程调度
 * Co::sleep语句必须在coroutine内执行
 * sleep 语句能够执行成功，会导致其中一个任务执行失败
 */

class TaskCoroutine
{
    public function __construct()
    {   
        $server = new Swoole\Http\Server('192.168.56.102', 9000, SWOOLE_BASE);
        $server->set(['worker_num'=>1, 'task_worker_num'=>2]);

        $server->on('WorkerStart', function($serv, $worker_id){
            //Worker进程编号范围是[0, $serv->setting['worker_num']-1]
            //Task进程编号范围是[$serv->setting['worker_num'], $serv->setting['worker_num'] + $serv->setting['task_worker_num']-1]

            echo "on workerstart, pid:{$serv->worker_pid},  id:{$serv->worker_id}, worker_id={$worker_id}\n";

            //判断是否是task进程
            if ($serv->taskworker) {
               echo "this is task worker process\n";
            } else {
                echo "this is worker process \n";
            }
        });

        $server->on('Task', function(swoole_server $serv, $task_id, $worker_id, $data){
            //$serv->worker_id表示的是worker进程的编号，包括Task进程
            //$serv->worker_pid表示的是 当前Worker进程的操作系统进程ID
            echo "#{$serv->worker_id},#pid:{$serv->worker_pid},#masterPid:{$serv->master_pid},#managerPid={$serv->manager_pid}\t onTask: worker_id={$worker_id},task_id={$task_id},data:".var_export($data, true)."\n";
            if ($serv->worker_id == 1) { //这里的worker_id表示的是task进程id，task进程有两个
                sleep(1);
            }
            return $data;
        });

        $server->on('request', function($request, $response) use($server){
            echo 'reactor_num:'.$server->setting['reactor_num'].',worker_num:'.$server->setting['worker_num']."\n";
            $tasks[0] = "hello, world";
            $tasks[1] = ['data'=>1234, 'code'=> 200];
            $result = $server->taskCo($tasks, 0.5);
            $response->end('test End, Result:'.var_export($result, true));
        });

        $server->start();
    }
}

$obj = new TaskCoroutine();

/**
 * 输出结果：
 * $ php task_coroutine.php
 *  大多数时间出现的结果：
    #2   onTask: worker_id=0, task_id=1 
    #1   onTask: worker_id=0, task_id=0 
    [2020-01-07 14:57:59 *10938.0]  WARNING php_swoole_onFinish (ERRNO 2003): task[0] has expired

    有时出现的结果：
    #2,#pid:12169,#master pid:12167  onTask: worker_id=0,task_id=1,data:array (
      'data' => 1234,
      'code' => 200,
    )
    #1,#pid:12168,#master pid:12167  onTask: worker_id=0,task_id=0,data:'hello, world'
    [2020-01-07 15:38:47 *12170.0]  WARNING php_swoole_onFinish (ERRNO 2003): task[0] has expired
    #1,#pid:12168,#master pid:12167  onTask: worker_id=0,task_id=2,data:'hello, world'
    #2,#pid:12169,#master pid:12167  onTask: worker_id=0,task_id=3,data:array (
      'data' => 1234,
      'code' => 200,
    )
    [2020-01-07 15:38:57 *12170.0]  WARNING php_swoole_onFinish (ERRNO 2003): task[2] has expired

    
    一次请求，但是代码却重复执行了一次。这是要注意的问题。
    好像taskCo方法有重试的机制，执行了两轮。
 */

