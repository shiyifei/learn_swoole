<?php declare(strict_types=1);

/**
 * swoole http server的onreceive事件默认是开启协程的。
 * 当代码执行到connect()和recv()函数时，底层会触发进行协程切换，此时可以去处理其他的事件或者接受新的请求。
 * 当此客户端连接成功或者后端服务回包后，底层会恢复协程上下文，代码逻辑继续从切换点开始恢复执行。
 * 开发者整个过程不需要关心整个切换过程。
 *
 * 协程组件
    TCP/UDP Client：Swoole\Coroutine\Client
    HTTP/WebSocket Client：Swoole\Coroutine\HTTP\Client
    HTTP2 Client：Swoole\Coroutine\HTTP2\Client
    Redis Client：Swoole\Coroutine\Redis
    Mysql Client：Swoole\Coroutine\MySQL
    PostgreSQL Client：Swoole\Coroutine\PostgreSQL

    在协程Server中需要使用协程版Client，可以实现全异步Server
    其他程序中可以使用go关键词手工创建协程
    同时Swoole提供了协程工具集：Swoole\Coroutine，提供了获取当前协程id，反射调用等能力。
 */


class LearnCoroutine 
{
	/**
	*
	*@throws
	*/
	public function httpServer()
	{
		$http = new Swoole\Http\Server('192.168.56.102', 9500);

		$http->on('request', function($request, $response) {
			$client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);

			//调用connect将触发协程切换上下文
			$client->connect('192.168.56.102', 18310, 0.5);
			$client->send("list areyouok \r\n\r\n");

			//调用recv将触发协程切换上下文
			$ret = $client->recv();

			$response->header('Content-Type', 'text/plain');
			$response->end($ret);
			$client->close();
		});

		$http->start();
	}
}


$obj = new LearnCoroutine();
$obj->httpServer();
