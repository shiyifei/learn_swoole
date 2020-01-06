<?php

$s = new Co\Scheduler();
$s->add(function(){
    file_get_contents("http://192.168.56.102:9501/start");
});
$s->start();


$server = new Swoole\Server;
$server->on('Receive', function(){

});
$server->start();

$s = new Co\Scheduler();
$s->add(function(){
    file_get_contents("http://192.168.56.102:9501/stop");
});

$s->start();

