<?php
error_reporting(E_ALL);
/**
 * 在最新的4.x版本中，协程取代了异步回调，作为我们推荐使用的编程方式。
 * 协程解决了异步回调编程困难的问题。
 * 使用协程可以以传统同步编程的方法编写代码，底层自动切换为异步IO，既保证了编程的简单性，又可借助异步IO，提升系统的并发能力。
 *
 * 以下的代码编写与同步阻塞模式的程序完全一致的。但是底层自动进行了协程切换处理，变为异步IO
 * 因此：
        服务器可以应对大量并发，每个请求都会创建一个新的协程，执行对应的代码
        某些请求处理较慢时，只会引起这一个请求被挂起，不影响其他请求的处理
 */

function test_mysql() {
    $db = new Swoole\Coroutine\MySQL();
    $db->connect(array('host'=>'192.168.56.102', 'user'=>'root', 
        'password'=>'SYF!123mysql', 'database'=>'test'));
    $data = $db->query('select * from users limit 10');
    return json_encode($data);
}

function test_redis() {
    $db = new Swoole\Coroutine\Redis();
    $db->connect('192.168.56.102', 6379);
    $db->set('what', 'I am learning swoole knowledge');
    $result = $db->get('what');
    return $result;
}

$serv = new Swoole\Http\Server('192.168.56.102', 9002);
$serv->on('request', function($request, $response){
    var_dump($request->server['request_uri']);
    switch($request->server['request_uri']) {
        case '/mysql':
            $output = test_mysql();
            $response->end($output);
            break;
        case '/redis':
            $output = test_redis();
            $response->end($output);
            break;
        case '/favicon.ico':
            $response->end('ok');
            break;
        default:
            break;
    }
    
});
$serv->start();



