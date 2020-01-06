<?php 

$server = new swoole_http_server("192.168.56.102", 9002);

$server->on("request", function($request, $response) {

    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        return $response->end();
    }

    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    $client->connect("192.168.56.102", 9000, 0.5);

    //调用connect将触发协程切换
    $client->send("hello world from swoole");

    //调用recv将触发协程切换
    $ret = $client->recv();
    $response->header("Content-Type", "text/plain");
    $response->end($ret);

    $client->close();
});

$server->start();

