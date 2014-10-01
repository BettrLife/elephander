<?php

namespace Elephander;
use PHPUnit_Framework_TestCase;

class DynamicScopeTest extends PHPUnit_Framework_TestCase {
	public function testGetUsesInnermostScope() {
		$f1 = function ($f2, $f3) {
			$scope = new DynamicScope(['a' => 1]);
			return $f2($f3);
		};
		$f2 = function ($f3) {
			$scope = new DynamicScope(['a' => 2]);
			return $f3();
		};
		$f3 = function () {
			return DynamicScope::get('a');
		};
		$this->assertEquals(2, $f1($f2, $f3));
	}

	public function testGetWalksStack() {
		$f1 = function ($f2, $f3) {
			$scope = new DynamicScope(['a' => 1]);
			return $f2($f3);
		};
		$f2 = function ($f3) {
			$scope = new DynamicScope(['b' => 2]);
			return $f3();
		};
		$f3 = function () {
			return DynamicScope::get('a');
		};
		$this->assertEquals(1, $f1($f2, $f3));
	}

	public function testSetUsesInnermostScope() {
		$f1 = function ($f2, $f3) {
			$scope = new DynamicScope(['a' => 1]);
			$f2($f3);
			return DynamicScope::get('a');
		};
		$f2 = function ($f3) {
			$scope = new DynamicScope(['a' => 2]);
			return $f3();
		};
		$f3 = function () {
			return DynamicScope::set('a', 3);
		};
		$this->assertEquals(1, $f1($f2, $f3));
	}

	public function testSetWalksStack() {
		$f1 = function ($f2, $f3) {
			$scope = new DynamicScope(['a' => 1]);
			$f2($f3);
			return DynamicScope::get('a');
		};
		$f2 = function ($f3) {
			$scope = new DynamicScope(['b' => 2]);
			return $f3();
		};
		$f3 = function () {
			return DynamicScope::set('a', 3);
		};
		$this->assertEquals(3, $f1($f2, $f3));
	}

	public function testOutOfOrderDestructionIssuesWarning() {
		$e = set_error_handler([$this, '_handleOoODWarning'], E_USER_WARNING);
		$scope1 = new DynamicScope([]);
		$scope2 = new DynamicScope([]);
		unset($scope1);
		unset($scope2);
		restore_error_handler();
	}

	function _handleOoODWarning($errno, $errstr) {
		$this->assertEquals(E_USER_WARNING, $errno);
		$this->assertEquals("DynamicScope destroyed out of order!", $errstr);
	}
}
