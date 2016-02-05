<?php

	namespace browserfs\phpstat\Parser;

	class PHPInterface {

		protected $name = null;
		protected $extends = null;
		protected $implements = null;

		protected $properties = [];
		protected $constants = [];
		protected $methods = [];

		public function __construct( $className, $extends, $implements ) {
			$this->name = $className;
			$this->extends = $extends;
			$this->implements = $implements;

		}

		public function addConstant( $constantDefinition ) {
			$this->constants[] = $constantDefinition;
		}

		public function addProperty( $propertyDefinition ) {
			$this->properties[] = $propertyDefinition;
		}

		public function addMethod( $methodDefinition ) {
			$this->methods[] = $methodDefinition;
		}

		public function propertyToString( $propertyDefinition ) {
			
			$result = '';

			switch ( $propertyDefinition['type'] ) {
				case 0:
					$result .= 'public ';
					break;
				case 1:
					$result .= 'public ';
					break;
				case 2:
					$result .= 'private ';
					break;
				case 4:
					$result .= 'protected ';
					break;
				case 12:
					$result .= 'private static ';
					break;
				case 10:
					$result .= 'protected static ';
					break;
				case 9:
					$result .= 'public static ';
					break;
				default:
					$result .= '<unknown_property> ';
					break;
			}

			$result .= '$' . $propertyDefinition['name'];

			if ( $propertyDefinition['default'] ) {
				$result .= ' = ' . $propertyDefinition['default'];
			}

			return '    ' . $result;

		}

		public function constantToString( $constantDefinition ) {

			return '    const ' . $constantDefinition['name'] . ' = ' . $constantDefinition['value'];
		}

		public function methodToString( $methodDefinition ) {
			
			$result = '';

			switch ( $methodDefinition['type'] ) {
				case 0:
					$result .= 'public function ';
					break;
				case 1: 
					$result .= 'public function '; 
					break;
				case 2: 
					$result .= 'protected function '; 
					break;
				case 4: 
					$result .= 'private function '; 
					break;
				case 12:
					$result .= 'private static function '; 
					break;
				case 10:
					$result .= 'protected static function '; 
					break;
				case 9: 
					$result .= 'public static function '; 
					break;
				case 20:
					$result .= 'private abstract function ';
					break;
				case 18:
					$result .= 'protected abstract function ';
					break;
				case 17:
					$result .= 'public abstract function ';
					break;
				case 28:
					$result .= 'private static abstract function ';
					break;
				case 26:
					$result .= 'protected static abstract function ';
					break;
				case 25:
					$result .= 'public static abstract function ';
					break;
				default:
					$result .= '<unknown_method> function ';
					break;

			}

			$result .= $methodDefinition['name'];

			$result .= '(';

			$args = [];

			foreach ( $methodDefinition['args'] as $argument ) {
				$args[] = (
					$argument['type']
						? $argument['type'] . ' '
						: ''
				) . (
					( $argument['byref'] ? '&' : '' ) . '$' . $argument['name']
				) . (
					$argument['default']
						? ' = ' . $argument['default']
						: ''
				);
			}

			$result .= count( $args )
				? implode( ", ", $args )
				: '';

			$result .= ')';

			return '    ' . $result;
		}

		public function __toString() {

			$out = 'interface ' . $this->name;

			if ( count( $this->extends ) ) {
				$out .= "\n" . '    extends ' . implode( ', ', $this->extends );
			}

			if ( count( $this->implements ) ) {
				$out .= "\n" . '    implements ' . implode( ", ", $this->implements );
			}

			$out .= "\n{";

			$out = [ $out ];

			// BEGIN: CONSTANTS

			$sortd = [];

			foreach ( $this->constants as $constant ) {
				$sortd[] = $this->constantToString( $constant );
			}

			sort( $sortd );

			foreach ( $sortd as $item ) {
				$out[] = $item . ';';
			}

			if ( count( $this->constants ) ) {
				$out[] = '';
			}

			// BEGIN: PROPERTIES

			$sortd = [];

			foreach ( $this->properties as $property ) {
				$sortd[] = $this->propertyToString( $property );
			}

			sort( $sortd );

			foreach ( $sortd as $property ) {
				$out[] = $property . ';';
			}

			if ( count( $this->properties ) ) {
				$out[] = '';
			}

			// BEGIN: METHODS

			$sortd = [];

			foreach ( $this->methods as $method ) {
				$sortd[] = $this->methodToString( $method );
			}

			sort( $sortd );

			foreach ( $sortd as $method ) {
				$result = $method ;

				if ( !preg_match('/\\}$/', $result ) ) {
					$result .= ";";
				}

				$out[] = $result . "\n";
			}

			$out[] = "}";

			return implode( "\n", $out );


		}


	}