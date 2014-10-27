<?php

namespace Elephander;
use InvalidArgumentException, Exception;
use ReflectionMethod;

/** Class for checking values against type hints, even when those hints don't
    correspond to PHP classes. */
class TypeHintHandler {
	protected $checkers = [];
	protected $converters = [];

	/** Registers a name as a valid type hint, along with functions to check the
	    values meet the type specifications, and convert values to the type. */
	public function registerTypeHint($type, $checker, $converter = null) {
		list($type, $parentType) = preg_split('/\\s*:\\s*/', "$type:");
		if ($parentType) {
			$checker = function ($x) use ($parentType, $checker) {
				return $this->checkers[$parentType] && $checker($x);
			};
			if (is_null($converter)) {
				$converter = isset($this->converters[$parentType])
					? $this->converters[$parentType]
					: null;
			}
		}
		$this->checkers[$type] = $checker;
		if (!is_null($converter)) $this->converters[$type] = $converter;
	}

	/** Registers the given alias as being identical to the given type, including
	    having the same type modifier. */
	public function registerTypeAlias($type, $alias) {
		$this->checkers[$alias] = $this->checkers[$type];
		if (isset($this->converters[$type])) {
			$this->converters[$alias] = $this->converters[$type];
		}
	}

	/** Returns a checker or a converter for the given type.  First checks for
	    one explicitly defined for the type, then attempts to load the class
	    specified by the type hint and checks again for an explicitly defined -er
	    for the type, then looks for a wildcard typehint defined to an enclosing
	    namespace if the type is namespaced, then finally gives up and returns
	    null.

	    The class load is so classes can self-register type hints, rather than
	    needing to maintain them all in this (or a specifically included) file,
	    which could easily become unweildy.  Note however that merely autoloading
	    a subclass does not trip the autoloader for a parent class, and thus you
	    cannot rely on tripping the autoloader for wildcard typehints. */
	protected function getErForType($kind, $type) {
		$array = $this->{"{$kind}ers"};
		if (isset($array[$type])) return $array[$type];

		// No explicit handler, trip the autoloader machinery then look for an
		// explicit -er again
		if (
			!class_exists($type, false)
			&& !interface_exists($type, false)
			&& !trait_exists($type, false)
		) {
			spl_autoload_call($type);
			if (isset($array[$type])) return $array[$type];
		}

		// Check for a whole-namespace -er
		$namespaces = explode('\\', $type);
		while (array_pop($namespaces) && count($namespaces)) {
			$wildns = join('\\', $namespaces).'\\*';
			if (isset($array[$wildns])) return $array[$wildns];
		}
		return null;
	}

	/** Checks if $value is consistent with $type.  Does not do conversion, for
	    which see cast(). */
	public function check($type, $value) {
		$let = new DynamicScope(['*type-hint*' => $type]);
		return call_user_func($this->getErForType('check', $type), $value);
	}

	/** Runs $value through the type converter for $toType.  This should
	    generally happen automagically, but is necessary for optional parameters
	    due to PHP quirks. */
	public function cast($toType, $value) {
		$let = new DynamicScope(['*type-hint*' => $toType]);
		return call_user_func($this->GetErForType('convert', $toType), $value);
	}

	/** Gets the name of a parameter from a method.  If unable to do so, returns
	    the parameter's position. */
	protected function getParameterName($class, $method, $pos) {
		try {
			$ref = new ReflectionMethod($class, $method);
			return $ref->getParameters()[$pos-1]->getName();
		} catch (Exception $e) { }
		return $pos;
	}

	/** The internal function which gets called by ErrorHandler. */
	function handleError($m) {
		list($_, $i, $class, $method, $hint, $type) = $m;
		if ('none' === $type) {
			$argname = $this->getParameterName($class, $method, $i);
			throw new ArgumentNotProvided("$class::$method", $argname, $hint);
		}
		$backtrace = debug_backtrace(null, 4);
		$argval = $backtrace[3]['args'][$i-1];
		if (!is_null($this->getErForType('check', $hint))) {
			if ($this->check($hint, $argval)) {
				if (!is_null($this->getErForType('convert', $hint))) {
					$backtrace[3]['args'][$i-1] = $this->cast($hint, $argval);
				}
				return true;
			} else {
				$argname = $this->getParameterName($class, $method, $i);
				throw new ArgumentNotOfType("$class::$method", $argname, $hint, $argval);
			}
		}
		if (class_exists($hint, false)) return false;
		throw new Exception("Method $class::$method uses an unknown type hint $hint");
	}

	/** Registers this instance with the given ErrorHandler. */
	public function registerWith(ErrorHandler $eh) {
		return $eh->addErrorHandler(
			E_RECOVERABLE_ERROR,
			'/^Argument (\\d)+ passed to (?:([\\w\\\\]+)::)?(\\w+)\\(\\) must be an instance of ([\\w\\\\]+), ([\\w\\\\]+) given/',
			[$this, 'handleError']);
	}
}


// Exceptions!

class ArgumentNotOfType extends InvalidArgumentException {
	function __construct($method, $argName, $expectedType, $argVal) {
		list($this->method, $this->argName, $this->expectedType, $this->argVal) = func_get_args();
		parent::__construct("{$argName}'s '$argVal' is not of the expected type $expectedType in $method");
	}
}

class ArgumentNotProvided extends InvalidArgumentException {
	function __construct($method, $argName, $expectedType) {
		list($this->method, $this->argName, $this->expectedType) = func_get_args();
		parent::__construct("{$argName} must be $expectedType, but was not provided to $method");
	}
}
