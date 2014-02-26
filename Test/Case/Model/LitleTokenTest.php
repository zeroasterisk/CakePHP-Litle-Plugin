<?php
App::uses('LitleSource','Litle.Model/Datasource');
App::uses('LitleSale', 'Litle.Model');
App::uses('LitleUtil', 'Litle.Lib');
App::uses('Set', 'Utility');
class LitleTokenTest extends CakeTestCase {
	public $plugin = 'app';
	public $fixtures = array();
	protected $_testsToRun = array();

	/**
	* Reset various configurations for testing
	* (has to be done here, instead of before the class loading)
	*/
	public function reconfigure() {
		# these details should be set in your config, but can be overridden here
		# LitleUtil::$config['user'] = '******';
		# LitleUtil::$config['password'] = '******';
		# LitleUtil::$config['merchantId'] = '******';
		LitleUtil::$config['logModel'] = false;
	}
	/**
	* Start Test callback
	*
	* @param string $method
	* @return void
	* @access public
	*/
	public function startTest($method) {
		parent::startTest($method);
		Configure::write('LitleTesting', true);
		$this->reconfigure();
		$this->LitleToken = ClassRegistry::init('Litle.LitleToken');
		$this->LitleSale = ClassRegistry::init('Litle.LitleSale');
		$this->LitleSale->useDbConfig = 'litle';
		$this->LitleSale->useTable = false;
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
		unset($this->LitleToken);
		unset($this->LitleSale);
		ClassRegistry::flush();
	}

	/**
	* Validate the plugin setup
	*/
	public function testSetup() {
		$this->assertTrue(is_object($this->LitleToken));
		$this->assertEqual($this->LitleToken->alias, 'LitleToken');
		$this->assertTrue(empty($this->LitleToken->useTable));
		$config_user = LitleUtil::getConfig('user');
		$this->assertFalse(empty($config_user), "You are missing the configuration Username");
		$config_password = LitleUtil::getConfig('password');
		$this->assertFalse(empty($config_password), "You are missing the configuration Password");
	}
}
