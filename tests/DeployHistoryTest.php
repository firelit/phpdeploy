<?php

class DeployHistoryTest extends PHPUnit_Framework_TestCase {
	
	protected static $fixturesPath;

	public static function setUpBeforeClass() {
        self::$fixturesPath = realpath(__DIR__.'/fixtures/');
    }

    public function testFileLoad() {

    	$dh = new DeployHistory(self::$fixturesPath . '/history.json');

    	$this->assertEquals(2, sizeof($dh->history), 'History file did not load properly');

    }

    public function testInvalidFile() {

    	$this->setExpectedException('InvalidFileException');

    	$dh = new DeployHistory(self::$fixturesPath . '/history/fake/file/exception.json');

    }

    public function testMaxSet() {

    	$dh = new DeployHistory(self::$fixturesPath . '/history.json');

    	$dh->setMaxHistory(25);

    	$this->assertEquals(25, $dh->max);

    }

    public function testAddHistory() {

    	$dh = new DeployHistory(self::$fixturesPath . '/history.json');

    	$oldest = $dh->history[ sizeof($dh->history) - 1 ];

    	$dh->setMaxHistory(2);
    	$dh->addHistory('v3.0', '/test/v3_0');

    	$this->assertEquals(2, sizeof($dh->history), 'Max history file size not enforced');

    	$this->assertEquals('v3.0', $dh->history[0]['tag'], 'Newest version in history incorrect');
    	$this->assertEquals('/test/v3_0', $dh->history[0]['dir'], 'Newest version in history incorrect');

    	$this->assertEquals(1, sizeof($dh->old), 'Too many old versions removed from history');
    	$this->assertEquals($oldest, $dh->old[0], 'Oldest version in history not properly removed');

    }

}