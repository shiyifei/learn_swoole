<?php

/**
 * 在Swoole4协程编程中，某个协程的代码中抛出错误，会导致整个进程退出，进程所有协程终止执行。
 * 在协程顶层空间可以先进行一次try/catch捕获异常/错误。仅终止出错的协程。
 */
function test1()
{
    echo "In ".__METHOD__."\n";
}

$funcName = 'test';
go(function()use($funcName){
    try {
        call_user_func($funcName);
    } catch (Throwable $e) {
        var_dump($e);
    }
});

$funcName = 'test1';
go(function() use($funcName){
    try {
        call_user_func($funcName);
    } catch (Error $e) {
        var_dump($e);
    } catch (Exception $e) {
        echo 'Exception:'.$e->getMessage()."\n";
    }
});

go(function(){
    
    Co::sleep(1);
});
