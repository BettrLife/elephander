<?php

namespace Elephander;

/** Provides a convenient mechanism for registering multiple error handlers, any
    of which might handle a given error.

    To register an instance as the active error handler, call:
    	$errorHandler->registerSelf();

    To unregister, simply call unregisterSelf().

    Why you'd ever have more than one instance of this escapes me.
 */
class ErrorHandler {
	const Early  = 0;
	const Normal = 1;
	const Late   = 2;

	protected $handlers = [];
	protected $set = false;

	/** Adds the callback to the list of handlers to try for the given error
	    level and error message regular expression.  $callback will be passed the
	    array of matches for the given regular expression, followed by the error
	    file, line, and context. */
	public function addErrorHandler($level, $msg_regexp, $callback, $stage = self::Normal) {
		for ($i = 0; $level; $i++) {
			if ($level & 1) {
				$this->handlers[1<<$i][$stage][$msg_regexp][] = $callback;
			}
			$level >>= 1;
		}
	}

	/** The internal function which actually handles errors and calls all the
	    relevant registered handlers. */
	function _handleError($level, $message, $file, $line, $context) {
		if (!isset($this->handlers[$level])) return false;
		foreach ([ self::Early, self::Normal, self::Late ] as $stage) {
			if (!isset($this->handlers[$level][$stage])) continue;
			foreach ($this->handlers[$level][$stage] as $regexp => $handlers) {
				if (preg_match($regexp, $message, $m)) {
					foreach ($handlers as $handler) {
						$ret = call_user_func($handler, $m, $file, $line, $context, $level);
						if ($ret) return $ret;
					}
				}
			}
		}
		return false;
	}

	/** Sets this as PHP's global error handler.  If $levels is unspecified, will
	    handle all but E_STRICT. */
	public function registerSelf($levels = null) {
		if (!isset($levels)) $levels = E_ALL & ~E_STRICT;
		set_error_handler([$this, '_handleError'], $levels);
		$this->set = true;
	}

	/** Unregisters this as PHP's global error handler.

	    Uses restore_error_handler(), which means you should not call out of
	    order (so don't register A, then B, then try to unregister A--it will
	    unregister B and likely get confused later on). */
	public function unregisterSelf() {
		if ($this->set) restore_error_handler();
		$this->set = false;
	}

	/** This is mostly pointless--if our instance is the registered error
	    handler, there must be a reference keeping it alive so __destruct
	    shouldn't get called--but if I take this out I'll forget why it shouldn't
	    be necessary and end up in a long loop of adding and removing it. */
	public function __destruct() {
		if ($this->set) restore_error_handler();
	}
}
