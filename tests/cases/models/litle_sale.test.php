<?php
/* LitleSale Test cases generated on: 2011-10-31 13:10:59 : 1320082739*/
App::import('Datasource', 'litle.LitleSource');
App::import('Model', 'litle.LitleSale');
App::import('Lib', 'Templates.AppTestCase');
class LitleSaleTestCase extends AppTestCase {
	
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
		//parent::startTest($method);
		//$this->LitleSale = AppMock::getTestModel('LitleSale');
		#$this->LitleSale =& ClassRegistry::init('LitleSale');
		$this->LitleSale = new LitleSale(false, null, 'litle');
		echo $this->LitleSale->useDbConfig."\n";die();
		#$fixture = new LitleSaleFixture();
		#$this->record = array('LitleSale' => $fixture->records[0]);
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
		unset($this->LitleSale);
		ClassRegistry::flush();
	}
	
	/**
	* Validate the plugin setup
	*/
	function testSetup() {
		$this->assertTrue(is_object($this->LitleSale));
		$this->assertEqual($this->LitleSale->alias, 'LitleSale');
		$config = $this->LitleSale->config();
		$this->assertEqual($config['datasource'], 'Litle.LitleSource');
		$this->assertFalse(empty($config['user']));
		$this->assertFalse(empty($config['password']));
	}
	/**
	* Validate translate fields
	*/
	function testTranslateFields() {
		$data = array(
			'name' => 'Bubba Doe',
			'account' => '5186005800001012', // translation via config
			'cc_expires' => '1110', // translation via config
			'bill_name' => 'John Doe', // translation via config
			'billToAddress' => array(
				'addressLine1' => '123 4th street',
				'city' => 'San Jose',
				'state' => 'CA',
				),
			'billToAddress.addressLine2' => 'Apt. 20',
			'billToAddress.city' => 'should not overwrite', // in place above
			'billToAddress.state' => 'CA', // in place above
			'billToAddress.zip' => '95032', // missing above
			'billToAddress.country' => 'USA', // missing above
			);
		$this->__deep_shuffle($data);
		$response = $this->LitleSale->translateFields($data);
		$expected = array(
			'card' => array(
				'number' => '5186005800001012',
				'expDate' => '1110',
				),
			'billToAddress' => array(
				'name' => 'John Doe',
				'addressLine1' => '123 4th street',
				'addressLine2' => 'Apt. 20',
				'city' => 'San Jose',
				'country' => 'USA',
				'state' => 'CA',
				'zip' => '95032',
				),
			'name' => 'Bubba Doe',
			);
		$this->__deep_ksort($response);
		$this->__deep_ksort($expected);
		$this->assertEqual($response, $expected);
	}
	/**
	* Validate simple sale
	*
	*/
	function testSale() {
		$sale = array(
			'orderId' => '0987654321',
			'amount' => '1.01',
			'orderSource' => 'testing',
			'reportGroup' => '1234',
			'card' => array(
				'type' => 'MC',
				'number' => '123457890',
				'expDate' => '0123',
				),
			'billToAddress' => array(
				'name' => 'John Doe',
				'addressLine1' => '123 4th street',
				'addressLine2' => 'Apt. 20',
				'city' => 'San Jose',
				'state' => 'CA',
				'zip' => '95032',
				'country' => 'USA',
				),
			);
		$response = $this->LitleSale->save($sale);
		//print_r(compact('response'));
	}
	
	/**
	*
	*
	*/
	function __deep_ksort(&$arr) { 
		ksort($arr); 
		foreach ($arr as &$a) { 
			if (is_array($a) && !empty($a)) { 
				$this->__deep_ksort($a); 
			} 
		} 
	}
	/**
	*
	*
	*/
	function __deep_shuffle(&$arr) {
		$keys = array_keys( $arr );
		shuffle( $keys );
		$arr = array_merge( array_flip( $keys ) , $arr );
		foreach ($arr as &$a) {
			if (is_array($a) && !empty($a)) {
				$this->__deep_shuffle($a);
			}
		}
	}
}
?>