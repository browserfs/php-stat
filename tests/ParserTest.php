<?php

	namespace foo\bar;
	
	interface Foo {

	}

	class ParserTest extends \PHPUnit_Framework_TestCase implements \foo\bar\Foo {

		protected $parser = null;
		
		public function setUp() {
			$this->parser = new \browserfs\phpstat\Parser( __DIR__ . '/sample.php' );
			echo $this->parser . "";
		}

		public function testNothing() {
			$this->assertEquals( true, true );
		}

	}
?>