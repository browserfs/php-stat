<?php

	namespace browserfs\phpstat\Parser;

	class PHPClass {

		protected $name = null;
		protected $extends = null;
		protected $implements = null;

		protected $properties = [
			'static'   => [],
			'instance' => []
		];

		public function __construct( $className, $extends, $implements ) {
			$this->name = $className;
			$this->extends = $extends;
			$this->implements = $implements;

			echo "CREATE CLASS: ", $className, " EXTENDS ", json_encode( $extends ), ", IMPLEMENTS ", json_encode( $implements ), "\n";
		}

		/**
		 * @param string  $propertyType = enum( 'method', 'constant', 'property', 'var' );
		 * @param string  $propertyName
		 * @param boolean $isStatic
		 * @param array   $methodArgs    = null
		 * @param string  $propertyValue = '';
		 */
		public function addProperty( $isStatic, $propertyType, $propertyAccess, $propertyName, $propertyValue, $methodArgs = null ) {

		}

	}