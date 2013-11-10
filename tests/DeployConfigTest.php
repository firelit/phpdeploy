<?php

class DeployConfigTest extends PHPUnit_Framework_TestCase {
	
	protected static $fixturesPath;

	public static function setUpBeforeClass() {
        self::$fixturesPath = realpath(__DIR__.'/fixtures/');
    }

    public function testFileLoad() {

    	$dc = new DeployConfig(self::$fixturesPath . '/config.json');

    	$this->assertEquals(self::$fixturesPath . '/config.json', $dc->file, 'Config file did not load properly');
        $this->assertEquals('git@github.com:myname/myrepo.git', $dc->repo, 'Repo did not load properly');
        $this->assertEquals('history.json', $dc->history, 'History did not load properly');
        $this->assertEquals(4, sizeof($dc->cmds), 'Commands did not load properly');
        $this->assertEquals('/var-test/www', $dc->web, 'Web-root did not load properly');

    }

    public function testInvalidFile() {

    	$this->setExpectedException('InvalidFileException');

    	$dc = new DeployConfig(self::$fixturesPath . '/config/fake/file/exception.json');

    }

}