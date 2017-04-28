<?php

namespace PhpEarth\Swoole;

class Accessor
{
    /**
     * Changes private or protected value of property of given object.
     *
     * @param object $object   Object for which property needs to be changed
     * @param string $property Property name
     * @param mixed $value    New value of private or protected property
     */
    public static function set($object, $property, $value)
    {
        $thief = \Closure::bind(function($obj) use ($property, $value) {
            $obj->$property = $value;
        }, null, $object);

        $thief($object);
    }

    /**
     * Binds callable for calling private and protected methods.
     *
     * @param  callable $callable
     * @param  mixed    $newThis
     * @param  array    $args
     * @param  mixed    $bindClass
     * @return void
     */
    public static function call(callable $callable, $newThis, $args = [], $bindClass = null)
    {
        $closure = \Closure::bind($callable, $newThis, $bindClass ?: get_class($newThis));
        if ($args) {
            call_user_func_array($closure, $args);
        } else {
            // Calling it directly is faster
            $closure();
        }
    }
}
