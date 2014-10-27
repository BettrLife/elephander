<?php

namespace Elephander;
use PHPUnit_Framework_TestCase;
use DateTime, DateTimeZone;

class TypeHintHandlerTest extends PHPUnit_Framework_TestCase {
	protected function getHandler() {
		$eh = new ErrorHandler();
		$th = new TypeHintHandler();
		$th->registerWith($eh);
		$eh->registerSelf();
		return $th;
	}

	protected function registerBool($th, $convert = true) {
		$th->registerTypeHint('boolean',
			function ($x) {
				return is_bool($x) || preg_match('/^(?:true|false|1|0|yes|no)$/i', $x);
			},
			$convert ?
				function ($x) {
					return (boolean)preg_match('/^(?:true|1|yes)$/i', $x);
				} :
				null);
	}

	public function takeABoolean(\boolean $boolean) {
		return $boolean;
	}

	public function testCheckers() {
		$th = $this->getHandler();
		$this->registerBool($th, false);

		$this->assertSame(true,  $this->takeABoolean(true));
		$this->assertSame(1,     $this->takeABoolean(1));
		$this->assertSame('1',   $this->takeABoolean('1'));
		$this->assertSame('yes', $this->takeABoolean('yes'));

		$this->assertSame(false, $this->takeABoolean(false));
		$this->assertSame(0,     $this->takeABoolean(0));
		$this->assertSame('0',   $this->takeABoolean('0'));
		$this->assertSame('no',  $this->takeABoolean('no'));
	}

	function provideChecker() {
		return [ [ 2 ], [ 'ham' ], [ 'aye' ] ];
	}

	/** @dataProvider provideChecker
	    @expectedException Elephander\ArgumentNotOfType */
	public function testCheckerThrowsArgumentNotOfType($val) {
		$th = $this->getHandler();
		$this->registerBool($th, false);

		$this->takeABoolean($val);
	}

	/** @expectedException Elephander\ArgumentNotProvided */
	public function testCheckerThrowsArgumentNotProvided() {
		$th = $this->getHandler();
		$this->registerBool($th, false);

		$this->takeABoolean();
	}

	public function testBoolConverter() {
		$th = $this->getHandler();
		$this->registerBool($th, true);

		$this->assertSame(true, $this->takeABoolean(true));
		$this->assertSame(true, $this->takeABoolean(1));
		$this->assertSame(true, $this->takeABoolean('1'));
		$this->assertSame(true, $this->takeABoolean('yes'));

		$this->assertSame(false, $this->takeABoolean(false));
		$this->assertSame(false, $this->takeABoolean(0));
		$this->assertSame(false, $this->takeABoolean('0'));
		$this->assertSame(false, $this->takeABoolean('no'));
	}

	protected function registerDate($th, $convert = true) {
		$th->registerTypeHint('DateTime',
			function ($x) {
				return is_string($x) && preg_match('#^\\d{4}-\\d{2}-\\d{2}(?:T\\d{2}:\\d{2}:\\d{2})?(?:Z|[+-]\\d{2}:?\\d{2}| [\\w/+-]+)?$#', $x);
			},
			$convert ?
				function ($x) {
					$zone = null;
					if (preg_match('#(.*?) ([\\w/+-]+)$#', $x, $m)) {
						list($x, $zone) = [ $m[1], $m[2] ];
					}
					if ($zone) {
						$zone = new DateTimeZone($zone);
					}
					return new DateTime($x, $zone);
				} :
				null);
	}

	public function takeADate(DateTime $date) {
		return $date;
	}

	public function testDateConverter() {
		$th = $this->getHandler();
		$this->registerDate($th, true);

		$this->assertEquals(new DateTime("2014-10-27"), $this->takeADate("2014-10-27"));
		$this->assertEquals(new DateTime("2014-10-27", new DateTimeZone("America/Chicago")), $this->takeADate("2014-10-27 America/Chicago"));
		$this->assertEquals(new DateTime("2014-10-27+0600"), $this->takeADate("2014-10-27+0600"));
	}


}
