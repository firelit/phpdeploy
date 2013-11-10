<?php

class DeployCommandTest extends PHPUnit_Framework_TestCase {
	
	public function testConstruct() {

		$dc = new DeployCommand;

		$this->assertNotNull($dc);

	}

}