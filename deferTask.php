<?php
/**
 * 由于Go语言没有提供析构方法，PHP对象则有析构函数,使用__destruct()可以实现Go风格的defer
 *
 * 基于PHP对象析构方法实现的defer更灵活，
 * 如果希望改变执行的时机，甚至可以将DeferTask对象赋值给其他生命周期更长的变量，defer任务的执行可以延长生命周期
 * 默认情况下与Go的defer完全一致，在函数退出时自动执行
 */

class DeferTask
{
    private $tasks;

    public function add(callable $fn)
    {
        $this->tasks[] = $fn;
    }

    function __destruct()
    {
        $tasks = array_reverse($this->tasks);
        foreach ($tasks as $fn) {
            $fn();
        }
    }
}


$obj = new DeferTask();
$obj->add(function(){
    echo "this is task 1\n";
});
$obj->add(function(){
    echo "this is task 2 \n";
});


