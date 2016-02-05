<?php

	namespace browserfs\phpstat\Parser;

	class PHPClass extends PHPInterface {

		public function methodToString( $methodDefinition ) {

			$result = parent::methodToString( $methodDefinition );

			// on abstact methods we don't compute method body
			if ( preg_match( '/[\\s]+abstract[\\s+]/', $result ) ) {
				return $result;
			}

			$result .= "\n    { /* " . $methodDefinition['body'] . " */ }";

			return $result;

		}

		public function __toString() {

			$result = parent::__toString();

			$result = preg_replace( '/^interface /', 'class ', $result );

			return $result;

		}

	}