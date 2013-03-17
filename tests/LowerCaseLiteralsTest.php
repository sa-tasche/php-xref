<?php

require_once dirname(__FILE__) . "/BaseLintTest.php";

class LowerCaseLiteralsTest extends BaseLintTest {

   public function testBasicLiteral() {
        $testPhpCode = '
        <?php
            echo time;                                  // warning
            echo Foo::bar();                            // ok
            function foo(Exception $a, ClassName &$b);  // ok
            $foo = array();
            echo $foo[x];                               // warning
            try {} catch (Exception $e) {};             // ok
            try {} catch (\Exception $e) {};            // ok
        ';
        $exceptedDefects = array(
            array('time', 3, XRef::WARNING),
            array('x', 7, XRef::WARNING),
        );
        $this->checkPhpCode($testPhpCode, $exceptedDefects);
    }

    public function testNamespacedNames() {
        $testPhpCode = '
        <?php
            namespace foo\bar;                  // ok
            use bar\foo as anotherFoo;          // ok
            function foo( \bar\foo\baz $x ) {}  // ok
            \Foo\Bar::baz();                    // ok
            $foo = new \Foo\Bar\Baz();          // ok
            $foo->bar();                        // ok
            echo Foo::$bar;                     // ok
            echo \Foo\Bar::$bar;                // ok
            echo ExpectedWarning;               // warning
        ';

        $exceptedDefects = array(
            array('ExpectedWarning', 11, XRef::WARNING),
        );
        $this->checkPhpCode($testPhpCode, $exceptedDefects);

        $testPhpCode = '
        <?php
            namespace foo\bar {
                use bar\foo as anotherFoo;          // ok
                function foo( \bar\foo\baz $x ) {}  // ok
                \Foo\Bar::baz();                    // ok
                $foo = new \Foo\Bar\Baz();          // ok
                $foo->bar();                        // ok
                echo Foo::$bar;                     // ok
            }
            namespace \baz\qux {                    // ok
                echo \Foo\Bar::$bar;                // ok
                echo ExpectedWarning;               // warning
            }
        ';

        $exceptedDefects = array(
            array('ExpectedWarning', 13, XRef::WARNING),
        );
        $this->checkPhpCode($testPhpCode, $exceptedDefects);
     }

    public function testTraits() {
        $testPhpCode = '
        <?php
            trait Foo {
            }
        ';
        $exceptedDefects = array(
        );
        $this->checkPhpCode($testPhpCode, $exceptedDefects);
    }

    public function testClassConstants() {
        $testPhpCode = '
        <?php
            class Foo {
                const bar = 1;      // ok
                const Baz = 2;      // ok
            }
            echo Foo::bar;          // ok
            echo Foo::Baz;          // ok
            echo ExpectedWarning;   // warning
        ';
        $exceptedDefects = array(
            array('ExpectedWarning', 9, XRef::WARNING),
        );
        $this->checkPhpCode($testPhpCode, $exceptedDefects);
     }
}

