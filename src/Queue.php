<?php
namespace Lead\Queue;

use RuntimeException;

class Queue
{
    public static $_brokers = [];

    public static function connection($name, $connection = null)
    {
        if (func_num_args() === 1) {
            if (!isset(static::$_brokers[$name])) {
                throw new RuntimeException("Undefined broker connection `'{$name}'`.");
            }
            return static::$_brokers[$name];
        }
        static::$_brokers[$name] = $connection;
    }

    public static function reset()
    {
        static::$_brokers[$name] = [];
    }
}
