<?php 

use Swoole\Coroutine;

class Context 
{

    protected static $pool = [];

    public static function cid():int {
        return Coroutine::getuid();
    }

    public static function get($key, int $cid = null) {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid < 0) {
            return null;
        }
        if (isset(self::$pool[$cid][$key])) {
            return self::$pool[$cid][$key];
        }
        return null;
    }

    public static function put($key, $item, int $cid = null) {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid > 0) {
            self::$pool[$cid][$key] = $item;
        }
        return $item;
    }

    public static function delete($key, int $cid = null) {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid > 0) {
            unset(self::$pool[$cid][$key]);
        }
    }

    public static function destruct(int $cid = null) {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid > 0) {
            unset(self::$pool[$cid]);
        }
    }
}