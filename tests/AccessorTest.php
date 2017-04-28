<?php

namespace PhpEarth\Swoole\Tests;

use PHPUnit\Framework\TestCase;
use PhpEarth\Swoole\Accessor;

class Foo
{
    private $bar = 'baz';

    public function getBar()
    {
        return $this->bar;
    }

    private function resetBar()
    {
        $this->bar = 'reset';
    }
}

class AccessorTest extends TestCase
{
    public $var;

    public function testSet()
    {
        $foo = new Foo();

        $this->assertEquals($foo->getBar(), 'baz');

        Accessor::set($foo, 'bar', 'tab');

        $this->assertEquals($foo->getBar(), 'tab');

        Accessor::call(function() {
            $this->resetBar();
        }, $foo);

        $this->assertEquals($foo->getBar(), 'reset');
    }
}
