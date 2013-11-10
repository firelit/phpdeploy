<?php

class RollbackCommandTest extends PHPUnit_Framework_TestCase {
	
	public function testConstruct() {

		$rc = new RollbackCommand;

		$this->assertNotNull($rc);

	}

}