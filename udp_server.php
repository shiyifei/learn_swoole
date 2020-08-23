<?php

$arrIp = swoole_get_local_ip();
$localIp = $arrIp['enp0s3'];

//创建server对象
$serv = new swoole_server($localIp, 9501, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

//监听数据发送事件
$serv->on('Packet', function($serv, $data, $clientInfo) {
    echo "data={$data}\n";
    var_dump($clientInfo);
    $serv->sendto($clientInfo['address'], $clientInfo['port'], 'Server:' . $data);
    
});

//启动服务器
$serv->start();