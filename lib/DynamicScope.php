<?php

namespace Elephander;

/** Dynamically scoped variables.  Requires RAII. */
class DynamicScope {
	static $scopes = [[]];
	protected $index;

	/** Creates a set of dynamically-scoped variables.  Returns an object which
	    determines the lifetime of these variables.  This object must be kept for
	    the duration of the lexical scope in which it is valid, and must be
	    destroyed at the end of said scope.

	    Keeping scopes alive beyond the lifetime of the lexical scope in which
	    they were created will do bad things. */
	function __construct($bindings) {
		$this->index = count(self::$scopes);
		self::$scopes[] = $bindings;
	}

	function __destruct() {
		array_pop(self::$scopes);
		if ($this->index !== count(self::$scopes)) {
			trigger_error("DynamicScope destroyed out of order!", E_USER_WARNING);
		}
	}

	/** Finds a variable somewhere in the stack of dynamic variables, and returns
	    its value.

	    Issues an E_USER_NOTICE if the variable does not exist. */
	public static function get($name) {
		for ($i = count(self::$scopes)-1; $i >= 0; $i--) {
			if (array_key_exists($name, self::$scopes[$i])) return self::$scopes[$i][$name];
		}
		trigger_error("Undefined dynamic variable: $name", E_USER_NOTICE);
		return null;
	}

	/** Sets the given dynamically-scoped variable to a value.  If the variable
	    does not exist at any level, it will be created and set at the global
	    level. */
	public static function set($name, $value) {
		for ($i = count(self::$scopes)-1; $i > 0; $i--) {
			if (array_key_exists($name, self::$scopes[$i])) break;
		}
		return self::$scopes[$i][$name] = $value;
	}
}
