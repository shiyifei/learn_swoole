<?php declare(strict_types=1);


class LearnCoroutine 
{
	/**
	*
	*@throws
	*/
	public function httpServer()
	{
		$http = new Swoole\Http\Server('192.168.1.102', 9500);

		$http->on('request', function($request, $response) {

			// $response->end("what are you doing now?");


			$client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);

			//调用connect将触发协程切换上下文
			$client->connect('192.168.1.102', 80, 0.5);
			$client->send("hello world from swoole");

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
