<?php

	namespace browserfs\phpstat;

	use \PhpParser\ParserFactory;
	use \PhpParser\Error;

	class Parser {

		protected $fileName = null;

		protected $namespace = null;

		protected $functions = [];
		protected $classes   = [];
		protected $interfaces= [];

		/**
		 * Class constructor. Parses a fileName.
		 * @param fileName - string - path to the php file to be parsed
		 */
		public function __construct( $fileName ) {

			if ( !is_string( $fileName ) || !strlen( $fileName ) ) {
				throw new \browserfs\Exception( 'Invalid argument. Expected a non-empty string!' );
			}

			if ( !file_exists( $fileName ) ) {
				throw new \browserfs\Exception( 'File ' . $fileName . ' not found!' );
			}

			if ( !is_readable( $fileName ) ) {
				throw new \browserfs\Exception( 'File ' . $fileName . ' is not readable!' );
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

				$argType = $argument->getType();

				switch ( $argType ) {

					case 'Expr_ConstFetch':
						return implode( '\\', $argument->name->parts );
						break;

					default:
						throw new \Exception('Unknown argument default value literal type: ' . $argType );
						break;

				}

			}
		}

		protected function serializeStatements( $statements ) {
			$statements = serialize( $statements );
			$statements = preg_replace( '/s\\:13\\:"[\\S]{3}attributes";a:2:\\{s\\:9\\:"startLine";i\\:[0-9]+;s:7\\:"endLine";i:[0-9]+;\}/', '', $statements );
			return md5($statements);
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

			//throw new \Exception('Add function!');

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

					default:

						throw new \Exception('Invalid node type: ' . $nodeType );

						break;
				}

			}

		}

		protected function parseInterfaceMember( $member ) {
			
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

					return $result;

					break;

				default:

					throw new \Exception('Unknown interface member type: ' . $memberType );
					break;

			}

		}

		protected function addInterface( $interface ) {

			//print_r( $interface );

			$interfaceName = $interface->name;

			$extends = [];

			if ( is_array( $interface->extends ) ) {

				foreach ( $interface->extends as $superInterface ) {
					$extends[] = $this->getSuperInterfaceName( $superInterface );
				}

			}

			$members = [];

			if ( is_array( $interface->stmts ) ) {

				foreach ( $interface->stmts as $member ) {
					
					$members[] = $this->parseInterfaceMember( $member );

				}

			}

			$this->interfaces[] = $interface = [
				'name' => $interfaceName,
				'extends' => $extends,
				'members' => $members
			];

			print_r( $interface );

		}

		protected function addClass( $class ) {

		}

		protected function handleStatement( $statement ) {
			
			$stmtName = $statement->getType();

			switch ( $stmtName ) {

				case 'Stmt_Namespace':

					$this->handleNamespace( $statement );

					break;

				case 'Expr_FuncCall':
				case 'Expr_Assign':
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

				default:
					throw new \Exception('Unknown statement type: "' . $stmtName . '"' );
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

				throw new \browserfs\Exception('Failed to parse file: ' . $this->fileName . ': ' . $e->getMessage(), 1, $e );

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
				? "\n    " . implode( ",\n    ", $args ) . "\n"
				: "";

			$out .= ') => { <' . $functionRec[ 'body' ] . '> }';

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

			$out = [];

			foreach ( $this->interfaces as $interface ) {
				$out[] = $this->serializeInterfaceSignature( $interface );
			}

			foreach ( $this->functions as $function ) {
				$out[] = $this->serializeFunctionSignature( $function );
			}

			return implode( "\n\n", $out );

		}


	}

