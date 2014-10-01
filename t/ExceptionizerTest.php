<?php

namespace Elephander;
use PHPUnit_Framework_TestCase;

class ExceptionizerTest extends PHPUnit_Framework_TestCase {
	/** @expectedException \Elephander\Exceptionizer\UserError */
	public function testTriggersUserError() {
		$eh = new ErrorHandler();
		$eh->registerSelf();
		Exceptionizer::registerWith($eh);
		trigger_error("user error", E_USER_ERROR);
	}

	/** @expectedException \Elephander\Exceptionizer\UserWarning */
	public function testTriggersUserWarning() {
		$eh = new ErrorHandler();
		$eh->registerSelf();
		Exceptionizer::registerWith($eh, Exceptionizer::IncludeWarnings);
		trigger_error("user warning", E_USER_WARNING);
	}
}
