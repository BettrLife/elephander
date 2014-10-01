<?php

namespace Elephander;
use PHPUnit_Framework_TestCase;

class ErrorHandlerTest extends PHPUnit_Framework_TestCase {
	public function testCallsHandlers() {
		$eh = new ErrorHandler();
		$eh->addErrorHandler(E_USER_WARNING, '/test msg/', function () {
				$this->assertEquals(true, true); // Handler was called
				return true;
			});
		$eh->registerSelf();
		trigger_error("test msg", E_USER_WARNING);
	}

	public function testCallsEarlyFirst() {
		$calledEarly = false;
		$eh = new ErrorHandler();
		$eh->addErrorHandler(E_USER_WARNING, '/test early first/', function () use (&$calledEarly) {
				$this->assertEquals(false, $calledEarly);
				$calledEarly = true;
			}, ErrorHandler::Early);
		$eh->addErrorHandler(E_USER_WARNING, '/test early first/', function () use (&$calledEarly) {
				$this->assertEquals(true, $calledEarly);
				return true;
			}, ErrorHandler::Normal);
		$eh->registerSelf();
		trigger_error("test early first", E_USER_WARNING);
	}

	public function testCallsLateLast() {
		$calledLate = false;
		$eh = new ErrorHandler();
		$eh->addErrorHandler(E_USER_WARNING, '/test late/', function () use (&$calledLate) {
				$this->assertEquals(false, $calledLate);
			}, ErrorHandler::Normal);
		$eh->addErrorHandler(E_USER_WARNING, '/test late/', function () use (&$calledLate) {
				$this->assertEquals(false, $calledLate);
				$calledLate = true;
				return true;
			}, ErrorHandler::Late);
		$eh->registerSelf();
		trigger_error("test late last", E_USER_WARNING);
		$this->assertEquals(true, $calledLate);
	}
}
