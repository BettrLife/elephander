<?php

namespace Elephander;

/** Turns PHP errors into exceptions.  Each error level will be turned into a
    corresponding exception. */
class Exceptionizer {
	const NoWarnings = false;
	const IncludeWarnings = true;

	protected static $errorMap = [
		E_ERROR             => 'Error',
		E_PARSE             => 'Parse',
		E_COMPILE_ERROR     => 'CompileError',
		E_USER_ERROR        => 'UserError',
		E_CORE_ERROR        => 'CoreError',
		E_RECOVERABLE_ERROR => 'RecoverableError',
	];
	protected static $warningMap = [
		E_WARNING           => 'Warning',
		E_CORE_WARNING      => 'CoreWarning',
		E_COMPILE_WARNING   => 'CompileWarning',
		E_USER_WARNING      => 'UserWarning',
	];
	// What about Deprecated or Notice?

	protected static $fullMap;

	protected static function getBits($arr) {
		return array_reduce(array_keys($arr), function ($x, $y) { return $x | $y; }, 0);
	}

	/** Internal function that converts an error into an appropriately-typed
	    exception and throws it. */
	static function handleError($m, $file, $line, $context, $level) {
		$errClass = __CLASS__.'\\'.self::$fullMap[$level];
		throw new $errClass($m[1], 0, $level, $file, $line);
	}

	/** Registers this with an ErrorHandler.  Will register as a late-binding
	    handler, so other error handlers take precedence. */
	static function registerWith(ErrorHandler $eh, $includeWarnings = self::NoWarnings) {
		self::$fullMap = self::$fullMap ?: (self::$errorMap + self::$warningMap);
		$errors = self::getBits(self::$errorMap);
		if ($includeWarnings) $errors |= self::getBits(self::$warningMap);
		$eh->addErrorHandler($errors, "/^(.*)$/", [__CLASS__, 'handleError'], ErrorHandler::Late);
	}
}

require_once __DIR__.'/Exceptionizer/exceptions.inc.php';
