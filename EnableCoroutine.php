<?php declare(strict_types=1);
/**
 * Server setting enable_coroutine选项设置为false时，将不会默认创建协程
 * test1() 和 test2()方法仅有一个会生效， 因为一个PHP程序只能启动一个服务
 * Date: 2020/1/7
 * Time: 21:59
 */

class EnableCoroutine
{
    public function test1()
    {
         $http = new swoole_http_server('192.168.1.102', 9001);
         $http->set(['enable_coroutine'=>false]);

         //不开启协程时，在程序中如果想用协程，需要使用go语句或coroutine::create语句
         $http->on('request', function($request, $response){
             if ($request->server['request_uri'] == '/coro') {
                 go(function() use($response){
                    co::sleep(0.2);
                    $response->header('Content-Type', 'text/plain');
                    $response->end('this is coroutine function response data');
                 });
             } else {
                 $response->header('Content-Type', 'text/plain');
                 $response->end('enable coroutine is false');
             }
         });
         $http->start();
    }

    public function test2()
    {
        $http = new swoole_http_server('192.168.1.102', 9001);

        //此处即使不显式写出来，默认也是开启协程的。
        $http->set(['enable_coroutine'=>true]);

        $http->on('request', function($request, $response){

            //co::sleep()语句只有在coroutine内部才不会出错，此处不出错，说明程序默认在onrequest事件时已开启协程
            co::sleep(0.2);
            $response->header('Content-Type', 'text/plain');
            $response->end('enable coroutine is true');
        });
        $http->start();
    }
}

$obj = new EnableCoroutine();
//$obj->test1();
$obj->test2();