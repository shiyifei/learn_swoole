<?php
swoole_timer_tick(2000, function($timer_id){
    echo "timer_id:{$timer_id},tick-2000ms \n";
});