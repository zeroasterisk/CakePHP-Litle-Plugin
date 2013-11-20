<?php
App::uses('LitleUtil', 'Litle.Lib');
App::uses('ArrayToXml', 'Litle.Lib');
App::uses('Set', 'Utility');
class LitleUtilTest extends CakeTestCase {

	public $plugin = 'app';
	public $fixtures = array();
	protected $_testsToRun = array();

	/**
	* Start Test callback
	*
	* @param string $method
	* @return void
	* @access public
	*/
	public function startTest($method) {
		parent::startTest($method);
	}

	/**
	* End Test callback
	*
	* @param string $method
	* @return void
	* @access public
	*/
	public function endTest($method) {
		parent::endTest($method);
		LitleUtil::$config = array();
		configure::write('Litle', array());
		ClassRegistry::flush();
	}

	/*  */
	public function testGetConfig() {
		// test basic config
		$this->AssertEqual(LitleUtil::getConfig("plugin_version"), LitleUtil::$_baseConfig['plugin_version']);
		$this->AssertEqual(LitleUtil::getConfig("version"), LitleUtil::$_baseConfig['version']);
		$this->AssertEqual(LitleUtil::getConfig("url_xmlns"), LitleUtil::$_baseConfig['url_xmlns']);
		// test basic configuration settings
		$this->AssertNull(LitleUtil::getConfig("somthingCrazy"));
		$somethingCrazy1 = date('ymdhis').rand(0,2000);
		$somethingCrazy2 = date('sihdmy').rand(0,2000);
		Configure::write('Litle.somthingCrazy', $somethingCrazy1);
		$this->AssertEqual(LitleUtil::getConfig("somthingCrazy"), $somethingCrazy1);
		// override directly on the config (works only for simple key/values)
		LitleUtil::$config['somthingCrazy'] = $somethingCrazy2;
		$this->AssertEqual(LitleUtil::getConfig("somthingCrazy"), $somethingCrazy2);
		$this->AssertEqual(LitleUtil::$config['somthingCrazy'], $somethingCrazy2);
		// doesn't update configuration when setting directly
		$this->AssertEqual(Configure::read('Litle.somthingCrazy'), $somethingCrazy1);
		// can set extra "deep" keys on
		Configure::write('Litle.nested', array('one' => 1, 'two' => array('sub' => 1, 'subtwo' => array('one' => 3, 'sub' => 3))));
		$this->AssertEqual(LitleUtil::getConfig("nested.one"), 1);
		$this->AssertEqual(LitleUtil::getConfig("nested.two.sub"), 1);
		$this->AssertEqual(LitleUtil::getConfig("nested.two.subtwo"), array('one' => 3, 'sub' => 3));
		$this->AssertEqual(LitleUtil::getConfig("nested.two.subtwo.one"), 3);
	}
	/*  */
	public function testSetConfig() {
		// test basic configuration settings
		$this->AssertNull(LitleUtil::getConfig("somthingCrazy"));
		$somethingCrazy1 = date('ymdhis').rand(0,2000);
		$somethingCrazy2 = date('sihdmy').rand(0,2000);
		Configure::write('Litle.somthingCrazy', $somethingCrazy1);
		$this->AssertEqual(LitleUtil::getConfig("somthingCrazy"), $somethingCrazy1);
		// override directly on the config (works only for simple key/values)
		LitleUtil::setConfig('somthingCrazy', $somethingCrazy2);
		$this->AssertEqual(LitleUtil::getConfig("somthingCrazy"), $somethingCrazy2);
		$this->AssertEqual(LitleUtil::$config['somthingCrazy'], $somethingCrazy2);
		// does update configuration when using setConfig
		$this->AssertEqual(Configure::read('Litle.somthingCrazy'), $somethingCrazy2);
		// setting directly on the config, doesn't work for nested paths
		// because "get" doesn't know how to get them back out
		$nestA = array(
			'a1' => 1,
			'a2' => "two",
			'a3' => array(
				'b1' => 11,
				'b2' => "twelve",
				'b3' => array(
					'c1' => 21,
					'c2' => "twentytwo",
					'c3' => array(5,6,7,"eight"),
					),
				),
			);
		LitleUtil::$config['nestA'] = $nestA;
		$this->AssertEqual(LitleUtil::getConfig("nestA.a1"), null); // failure to get value = 1
		LitleUtil::setConfig('nestA', $nestA);
		$this->AssertEqual(LitleUtil::getConfig("nestA.a1"), 1);
		$this->AssertEqual(LitleUtil::getConfig("nestA.a2"), "two");
		$this->AssertEqual(LitleUtil::getConfig("nestA.a3.b1"), 11);
		$this->AssertEqual(LitleUtil::getConfig("nestA.a3.b2"), "twelve");
		$this->AssertEqual(LitleUtil::getConfig("nestA.a3.b3.c1"), 21);
		$this->AssertEqual(LitleUtil::getConfig("nestA.a3.b3.c2"), "twentytwo");
		$this->AssertEqual(LitleUtil::getConfig("nestA.a3.b3.c3"), array(5,6,7,"eight"));
	}
}
?>
