<?php 
use Some\Namespace\Class1;
use Another\Class2;

class DependencyTest extends Class1 {
    public function test(Class2 $param) {
        return new Class1();
    }
}