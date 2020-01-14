<?php
/**
 * 协程需要创建单独的内存栈，
 * 在PHP-7.2版本中底层会分配8K的stack来存储协程的变量，zval的尺寸为16字节，
 * 因此8K的stack最大可以保存512个变量。
 * 协程栈内存占用超过8K后ZendVM会自动扩容。
 */

//全局开启协程化
Swoole\Runtime::enableCoroutine();

include('context.php');

class CoroutineDemo
{
    private $server;

    public function __construct()
    {
        $this->server = ['host'=>'192.168.56.102', 'user'=>'root', 'password'=>'SYF!123mysql', 'database'=>'test'];
    }

    /**
     * 如果开启了swoole.use_shortname，可以直接使用go关键词创建新的协程
     * @return void
     */
    public function createCoroutine(): void
    {
        go(function(){
            $db = new Co\MySQL();   
            defer(function() use($db) {
                $db->close();
            });
            $db->connect($this->server);
            $name = 'wangming'.mt_rand(1,1000);
            $ret = $db->query("insert into user(`id`) values('{$name}')");
            echo 'insert result:'.var_export($ret, true)."\n";
            if ($ret === false) {
                echo 'insert error:'.$db->error.',error number:'.$db->errno."\n";
            }
            $result = $db->query('select * from user order by id desc limit 0,3');
            var_dump($result);
            echo "\n";
        });
    }

    /**
     * 测试协程嵌套
     * 子协程会优先执行，子协程执行完毕或挂起是，将重新回到父协程向下执行代码
     * 子协程挂起后，如果父协程退出，不影响子协程的执行
     * @return [type] [description]
     */
    public function testNesting()
    {
        go(function(){
            co::sleep(0.2);
            go(function(){
                co::sleep(0.4); //本条语句执行时，会导致子协程挂起，继续执行父协程的代码
                echo "child coroutine end \n";
            });
            echo "coroutine end \n";
        });
    }

    /**
     * sleep操作会导致协程挂起
     * @return [type] [description]
     */
    public function nesting2()
    {
        go(function() {
            go(function () {                
                go(function () {
                    co::sleep(0.8);
                    echo "co[3] end\n";
                });
                co::sleep(0.4);
                echo "co[2] end\n";
            });
            co::sleep(0.8);
            echo "co[1] end\n";
        });
    }

    /**
     * 测试程序出异常时，defer语句是否生效
     * @return [type] [description]
     * @throws Exception
     */
    public function testDefer()
    {
        echo "in ".__METHOD__."\n";

        go(function(){
            echo "in ".__METHOD__.",111\n";

            $info = Context::get('info', Co::getuid());
            defer(function(){
                Context::delete('info', Co::getuid());
            });
            echo "arrive 222\n";
            // throw new Exception('something wrong');
            echo "arrive here\n";
        });
    }

    /**
     * 测试coroutine::yield()方法
     * @return [type] [description]
     */
    public function testYield()
    {
        $cid = go(function(){
            echo "in testYield() co 1 start \n";
            co::yield();
            echo "in testYield() co 1 end \n";
        });

        go(function() use($cid){
            echo "in testYield() co 2 start \n";
            co::sleep(0.5);
            co::resume($cid);
            echo "in testYield() co 2 end \n";
        });

    }

    /**
     * 遍历当前进程内的所有协程
     * @return [type] [description]
     */
    public function testList()
    {
        $coros = co::list();
        $coroutines = iterator_to_array($coros);
        echo "in testList(), all coroutines:".var_export($coroutines, true)."\n";
    }
}

$obj = new CoroutineDemo();
$obj->createCoroutine();
$obj->testNesting();
$obj->nesting2();
$obj->testDefer();
$obj->testYield();
$obj->testList();

/*
** 结果：
array(1) {
  [0]=>
  array(6) {
    ["id"]=>
    string(1) "3"
    ["name"]=>
    string(5) "user3"
    ["age"]=>
    string(2) "93"
    ["password"]=>
    string(0) ""
    ["user_desc"]=>
    string(4) "desc"
    ["createtime"]=>
    string(19) "2019-12-25 16:47:28"
  }
}

coroutine end 
co[1] end
child coroutine end 
co[2] end
co[3] end

 */
