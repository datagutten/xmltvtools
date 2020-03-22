<?php


namespace datagutten\xmltv\tools\exceptions;


use Exception;

class InvalidFileNameException extends XMLTVException
{
	public function __construct($file, $code = 0, Exception $previous = null) {
		$message = sprintf('Invalid file name: %s', $file);
		parent::__construct($message, $code, $previous);
	}
}