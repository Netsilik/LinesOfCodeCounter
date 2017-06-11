<?php
namespace Tests\Loc;

use Netsilik\Util\Loc;
use PHPUnit\Framework\TestCase;

class MainTest Extends TestCase 
{
	private $_loc;
	
	public function setUp()
	{
		$this->_loc = new Loc();
		$this->assertInstanceOf(Loc::class, $this->_loc);
	}
	
	public function test_whenEmptyArgumentsGiven_helpHintReturnedAndErrorCodeIsGreaterThanZero()
	{
		$argv = ['loc'];
		$this->assertContains('--help', $this->_loc->main(count($argv), $argv));
		$this->assertGreaterThan(0, $this->_loc->getExitCode());
	}
}
