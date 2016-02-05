<?php

	namespace browserfs\string;

	trait ezcReflectionReturnInfo {
	    function getReturnType() { /*1*/ }
	    function getReturnDescription() { /*2*/ }
	}

	define('FOO', 'BAR');

	$_GLOBALS['BAR'] = 3;

	function FFo( $bar, &$car = ["MUMU"], \Bar $bamboo = null ) {
		if ( $a === $b ) {
			return true;
		}

		echo "ZBar", "\n";
	}

	for ( $i=0, $len = 20; $i<$len; $i++ ) {
		$boo;
	}

	interface IBasic extends \IBoo, IBar {

		public function bar( $name );
		protected function moo( $arg1, array $mooarg2 );
		private function privTest( $foo );

		const CONST_TEST = null;

		public $foo = null;
		protected $bar = 2;
		private $boo = 'boo';

		public static $sfoo = [];
		public static $sbar = DEFINED_VALUE;
		protected static $scar = \ClassName::CONSTANT;


	}

/*
	abstract class AbstractClass {

	}
*/

	abstract class CBar extends \CFoo\ClassName implements FooInterface, \BarInterface {

		public function bar( $name ) {

		}

		protected function moo( $arg1, array $mooarg2 ) {

		}

		private function privTest( $foo ) {

		}

		public static function sbar( $name ) {

		}

		protected static function smoo( $arg1, array $mooarg2 ) {
			// comment
		}

		private static function sprivTest( $foo ) {
			
		}

		private abstract function privateAbstract();
		protected abstract function protectedAbstract();
		public abstract function publicAbstract();

		private abstract static function privateAbstractStatic();
		protected abstract static function protectedAbstractStatic();
		public abstract static function publicAbstractStatic();


		const CONST_TEST = null;

		public $foo = null;
		protected $bar = 2;
		private $boo = 'boo';

		public static $sfoo = [];
		public static $sbar = DEFINED_VALUE;
		protected static $scar = \ClassName::CONSTANT;


	}

	function foo() {
		$a = 2;
	}

	function foo() {
		/*asdasd */

		$a = 2;
	
	}

	class foo {
		public function b() {}
	}

	class bar {
		
		public function a() {

			$a = 2;

			return $a;

		}

	}


	class foo {
		// asdasdsad
		public function b() {}
		


		public function a() {

			$b = 2;

			return $b;

		}
	}