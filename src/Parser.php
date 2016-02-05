<?php

	namespace browserfs\phpstat;

	use \PhpParser\ParserFactory;
	use \PhpParser\Error;

	class Parser {

		protected $fileName = null;

		protected $namespace = null;

		protected $defines   = [];
		protected $functions = [];
		protected $classes   = [];
		protected $interfaces= [];
		protected $traits    = [];


		/**
		 * Class constructor. Parses a fileName.
		 * @param fileName - string - path to the php file to be parsed
		 */
		public function __construct( $fileName ) {

			if ( !is_string( $fileName ) || !strlen( $fileName ) ) {
				throw new \Exception( 'Invalid argument. Expected a non-empty string!' );
			}

			if ( !file_exists( $fileName ) ) {
				throw new \Exception( 'File ' . $fileName . ' not found!' );
			}

			if ( !is_readable( $fileName ) ) {
				throw new \Exception( 'File ' . $fileName . ' is not readable!' );
			}

			$buffer = file_get_contents( $fileName );

			$this->fileName = $fileName;

			$this->parse( $buffer );

		}

		protected function handleNamespace( $namespace ) {

			$this->namespace = implode('\\', $namespace->name->parts );

			foreach ( $namespace->stmts as $statement ) {
				$this->handleStatement( $statement );
			}

			$this->namespace = null;
		}

		protected function getArgumentType( $argument ) {
			
			switch ( true ) {
			
				case empty( $argument ):
					return '';
					break;
			
				default:
			
					if ( is_string( $argument ) ) {
						return $argument;
					}

					$argType = $argument->getType();

					switch ( $argType ) {

						case 'Name_FullyQualified':
							return '\\' . implode( '\\', $argument->parts );
							break;

						case 'Param':
							return $argument->type;
							break;

						default:
							throw new \Exception( 'Unknown argument type: ' . $argType );
							break;
					}

					break;
			}
		}

		protected function getArgumentDefaultValueLiteral( $argument ) {
			if ( empty( $argument ) ) {

				return '';

			} else {

				return $this->stringifyConstantValue( $argument );

			}
		}

		protected function dump( $node ) {

			if ( $node === null ) {
				return 'null';
			}

	        if ($node instanceof \PhpParser\Node) {

	        	$nodeType =  $node->getType();

	            $r = $nodeType . '(';

	            foreach ($node->getSubNodeNames() as $key) {
	                $r .= "\n    " . $key . ': ';
	                $value = $node->$key;
	                if (null === $value) {
	                    $r .= 'null';
	                } elseif (false === $value) {
	                    $r .= 'false';
	                } elseif (true === $value) {
	                    $r .= 'true';
	                } elseif (is_scalar($value)) {
	                    $r .= $value;
	                } else {
	                    $r .= $this->dump($value);
	                }
	            }

	        } elseif (is_array($node)) {

	            $r = '[';
	            foreach ($node as $key => $value) {
	                $r .=  $key . ':';
	                if (null === $value) {
	                    $r .= 'null';
	                } elseif (false === $value) {
	                    $r .= 'false';
	                } elseif (true === $value) {
	                    $r .= 'true';
	                } elseif (is_scalar($value)) {
	                    $r .= $value;
	                } else {
	                    $r .= $this->dump($value);
	                }
	            }

	        } else {

	        	throw new \Exception('Expected Node or scalar but got: ' . json_encode( $node ) );

	        }

	        return $r . ")";


		}

		protected function serializeStatements( $statements ) {

			return md5( $this->dump( $statements ) );

			return $statements != '40cd750bba9870f18aada2478b24840a' ? $statements : 'empty';
		}

		protected function addFunction( $function ) {

			$functionName = $function->name;

			if ( empty( $functionName ) ) {
				return;
			}

			$functionArgs = [];

			foreach ( $function->params as $argument ) {
				$functionArgs[] = [
					'name' => $argument->name,
					'type' => $this->getArgumentType( $argument->type ),
					'default' => $this->getArgumentDefaultValueLiteral( $argument->default ),
					'byref' => $argument->byRef
				];
			}

			$functionBody = $this->serializeStatements( $function->stmts );

			$this->functions[] = $added = [
				'name' => $this->namespace === null ? '\\' . $functionName : '\\' . $this->namespace . '\\' . $functionName,
				'args' => $functionArgs,
				'body' => $functionBody
			];

		}

		protected function getSuperInterfaceName( $node ) {

			if ( empty( $node ) ) {

				return '';

			} else {

				$nodeType = $node->getType();

				switch ( $nodeType ) {

					case 'Name':

						return $this->namespace === null
							? '\\' . implode( '\\', $node->parts )
							: '\\'. $this->namespace . '\\' . implode( '\\', $node->parts );

						break;

					case 'Name_FullyQualified':

						return '\\' . implode( '\\', $node->parts );

						break;

					case 'Expr_Variable':
						return '$' . $node->name;
						break;

					default:

						throw new \Exception('Invalid node type: ' . $nodeType );

						break;
				}

			}

		}

		protected function stringifyConstFetch( $constFetch ) {

			$type = $constFetch->name->getType();

			switch ( $type ) {

				case 'Name':
					return implode( '??', $constFetch->name->parts );
					break;

				default:
					throw new \Exception('Unknown const fetch type: ' . $type );
					break;
			}

		}

		protected function stringifyConstantValue( $constantValueNode ) {

			if ( $constantValueNode === null ) {
				return 'null';
			}

			$nodeType = $constantValueNode->getType();

			switch ( $nodeType ) {

				case 'Scalar_LNumber':
				case 'Scalar_String':
					return json_encode( $constantValueNode->value );
					break;
				case 'Expr_ConstFetch':
					return $this->stringifyConstFetch( $constantValueNode );
					break;
				case 'Expr_ClassConstFetch':
					return $this->getSuperInterfaceName( $constantValueNode->class ) . '::' . $constantValueNode->name;
					break;
				default:
					return '<' . $nodeType . ' ' . $this->serializeStatements( isset( $constantValueNode->statements ) ? $constantValueNode->statements : [] ) . ' >';
					break;

			}
		}

		protected function parseInterfaceMember( $member, &$targetInterface ) {
			
			if ( empty( $member ) ) {
				throw new \Exception('Invalid argument!');
			}

			$memberType = $member->getType();

			switch ( $memberType ) {

				case 'Stmt_ClassMethod':

					$result = [
						'name' => $member->name,
						'type' => $member->type,
						'args' => [],
					];

					if ( isset( $member->params ) && is_array( $member->params ) ) {
						foreach ( $member->params as $param ) {
							$result['args'][] = [
								'name' => $param->name,
								'type' => $this->getArgumentType( $param ),
								'default' => $this->getArgumentDefaultValueLiteral( $param->default ),
								'byref' => $param->byRef
							];
						}
					}

					$targetInterface->addMethod( $result );

					break;

				case 'Stmt_ClassConst':

					$result = [
						'name'  => $member->consts[0]->name,
						'value' => $this->stringifyConstantValue( $member->consts[0]->value )
					];

					$targetInterface->addConstant( $result );

					break;

				case 'Stmt_Property':

					$result = [
						'type' => $member->type,
						'name' => $member->props[0]->name,
						'default' => $this->stringifyConstantValue( $member->props[0]->default )
					];

					$targetInterface->addProperty( $result );

					break;

				default:

					throw new \Exception('Unknown interface member type: ' . $memberType );
					break;

			}

		}

		protected function parseClassMember( $member, &$targetClass ) {
			
			if ( empty( $member ) ) {
				throw new \Exception('Invalid argument!' );
			}

			$memberType = $member->getType();

			switch ( $memberType ) {

				case 'Stmt_ClassMethod':

					$result = [
						'name' => $member->name,
						'type' => $member->type,
						'args' => [],
						'body' => $this->serializeStatements( $member->stmts )
					];

					if ( isset( $member->params ) && is_array( $member->params ) ) {
						foreach ( $member->params as $param ) {
							$result['args'][] = [
								'name' => $param->name,
								'type' => $this->getArgumentType( $param ),
								'default' => $this->getArgumentDefaultValueLiteral( $param->default ),
								'byref' => $param->byRef
							];
						}
					}

					$targetClass->addMethod( $result );

					break;

				default:
					$this->parseInterfaceMember( $member, $targetClass );
					break;
			}

		}

		protected function addInterface( $interface ) {

			//print_r( $interface );

			$interfaceName = $this->namespace === null ? '\\' . $interface->name : '\\' . $this->namespace . '\\' . $interface->name;

			$extends = [];

			if ( is_array( $interface->extends ) ) {

				foreach ( $interface->extends as $superInterface ) {
					$extends[] = $this->getSuperInterfaceName( $superInterface );
				}

			}

			$implements = [];

			$result = new Parser\PHPInterface( $interfaceName, $extends, $implements );

			if ( is_array( $interface->stmts ) ) {

				foreach ( $interface->stmts as $member ) {
					
					$this->parseInterfaceMember( $member, $result );

				}

			}

			$this->interfaces[] = $result;

		}

		protected function addClass( $class ) {

			$className = $this->namespace === null ? '\\' . $class->name : '\\' . $this->namespace . '\\' . $class->name;

			$extends = [];

			if ( is_object( $class->extends ) ) {
				$extends[] = $this->getSuperInterfaceName( $class->extends );
			}

			$implements = [];

			if ( is_array( $class->implements ) ) {
				foreach ( $class->implements as $superClass ) {
					$implements[] = $this->getSuperInterfaceName( $superClass );
				}
			}

			$result = $class->type == 16 
				? new Parser\PHPAbstractClass( $className, $extends, $implements )
				: new Parser\PHPClass( $className, $extends, $implements );

			if ( is_array( $class->stmts ) ) {

				foreach ( $class->stmts as $member ) {
					$this->parseClassMember( $member, $result );
				}

			}

			$this->classes[] = $result;

		}

		protected function addTrait( $trait ) {

			$traitName = $this->namespace === null ? '\\' . $trait->name : '\\' . $this->namespace . '\\' . $trait->name;

			$extends = [];

			$implements = [];

			$result = new Parser\PHPTrait( $traitName, $extends, $implements );

			if ( is_array( $trait->stmts ) ) {

				foreach ( $trait->stmts as $member ) {
					$this->parseClassMember( $member, $result );
				}

			}

			$this->traits[] = $result;

		}

		protected function discoverStatement( $statement ) {

			if ( !empty( $statement ) ) {

				if ( isset( $statement->stmts ) && is_array( $statement->stmts ) )  {

					foreach ( $statement->stmts as $substatement ) {
						$this->handleStatement( $substatement );
					}

				}

			}

		}

		protected function addFunctionCall( $statement ) {
			// we're interested if the function call is a "define"

			$funcName = $this->getSuperInterfaceName( $statement->name );

			if ( $funcName !== '\\define' ) {
				return;
			}
			
			$constVarType = $statement->args[0]->value->getType();

			if ( $constVarType != 'Scalar_String' ) {
				return;
			}

			$constValue = $this->stringifyConstantValue( $statement->args[1]->value );

			$this->defines[] = [
				'name' => $statement->args[0]->value->value,
				'value' => $constValue
			];
		}

		protected function handleStatement( $statement, $inRoot = false ) {
			
			$stmtName = $statement->getType();

			switch ( $stmtName ) {

				case 'Stmt_Namespace':

					$this->handleNamespace( $statement );

					break;

				case 'Stmt_Function':

					$this->addFunction( $statement );

					break;

				case 'Stmt_Interface':

					$this->addInterface( $statement );

					break;

				case 'Stmt_Class':

					$this->addClass( $statement );

					break;

				case 'Stmt_Trait':

					$this->addTrait( $statement );

					break;

				case 'Expr_FuncCall':
					$this->addFunctionCall( $statement );

					// intentionally unbreaked

				default:

					//throw new \Exception('Unknown statement type: "' . $stmtName . '"' );

					$this->discoverStatement( $statement );

					break;

			}
		}

		protected function parse( $buffer ) {

			try {

				$parser = ( new ParserFactory )->create( ParserFactory::PREFER_PHP7 );

				$stmts = $parser->parse( $buffer );

				foreach ( $stmts as $statement ) {

					$this->handleStatement( $statement );

				}

			} catch ( Error $e ) {

				throw new \Exception('Failed to parse file: ' . $this->fileName . ': ' . $e->getMessage(), 1, $e );

			}

		}

		protected function serializeFunctionSignature( $functionRec ) {

			$out = 'function ' . $functionRec['name'] . ' (';

			$args = [];

			foreach ( $functionRec['args'] as $arg ) {
				$args[] = ( $arg['type'] ? $arg['type'] . ' ' : '' ) 
					. ( $arg['byref'] ? '&' : '' ) 
					. '$' . $arg['name'] 
					. ( $arg['default'] ? ' = ' . $arg['default'] : '' );
			}

			$out .= count( $args ) > 0
				? implode( ", ", $args )
				: "";

			$out .= ")\n{ /* " . $functionRec[ 'body' ] . " */ }";

			return $out;

		}

		protected function serializeInterfaceSignature( $interfaceRec ) {

			$out = 'interface ' . $interfaceRec['name'];

			if ( is_array( $interfaceRec['extends'] ) && count( $interfaceRec['extends'] ) > 0 ) {
				$out .= "\n" . 'extends   '  . implode( ",\n          ", $interfaceRec['extends'] );
			}

			$out .= "\n" . '{' . "\n";

			$out .= "}";

			return $out;

		}

		public function __toString() {

			return implode( "\n\n", $this->toArray() );

		}

		public function toArray() {

			$out = [];

			foreach ( $this->defines as $define ) {
				$out[] = 'define ' . $define['name'] . '  = ' . $define['value'] . ';';
			}

			foreach ( $this->functions as $function ) {
				$out[] = $this->serializeFunctionSignature( $function );
			}

			foreach ( $this->interfaces as $interface ) {
				$out[] = $interface->__toString();
			}

			foreach ( $this->traits as $trait ) {
				$out[] = $trait->__toString();
			}

			foreach ( $this->classes as $class ) {
				$out[] = $class->__toString();
			}


			return $out;

		}


	}

