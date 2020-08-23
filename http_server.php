<?php
$arrIp = swoole_get_local_ip();
$localIp = $arrIp['enp0s3'];
$server = new swoole_http_server($localIp, 9502);

$server->on("request", function($request, $response) use($localIp) {

    //使用 Chrome 浏览器访问服务器，会产生额外的一次请求，/favicon.ico，可以在代码中响应 404 错误。
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->end();
        return;
    }

    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    //调用connect将触发协程切换
    $client->connect($localIp, 9001, 0.5);

    //向tcp服务器发送信息
    $client->send("hello world from swoole");

    //调用recv将触发协程切换
    $ret = $client->recv();
    $response->header("Content-Type", "text/plain");
    $response->end($ret);

    $client->close();
});

$server->start();

