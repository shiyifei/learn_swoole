<?php
$server = new Swoole\Server('192.168.56.102', 9600);
$server->on('WorkerStart', function(){
    Co::sleep(1);
    go(function(){
        echo "this is Coroutine2 \n";
    });
});
$server->on('Receive', function(){});
$server->start();