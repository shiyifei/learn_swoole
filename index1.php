<?php

$s = new Co\Scheduler();
$s->add(function(){
    Co::sleep(1);
    go(function(){
        echo "this is Coroutine 2 \n";
    });
});
$s->start();