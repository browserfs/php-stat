<?php

	namespace browserfs\phpstat\Parser;

	class PHPAbstractClass extends PHPClass {

		public function __toString() {

			$result = 'abstract ' . parent::__toString();

			return $result;

		}

	}