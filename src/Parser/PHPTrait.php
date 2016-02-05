<?php

	namespace browserfs\phpstat\Parser;

	class PHPTrait extends PHPClass {

		public function __toString() {

			$result = parent::__toString();

			$result = preg_replace( '/^class /', 'trait ', $result );

			return $result;

		}

	}