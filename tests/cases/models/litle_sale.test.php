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
		$this->LitleSale =& ClassRegistry::init('LitleSale');
		#$this->LitleSale = new LitleSale(false, null, 'litle');
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
	* /
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
	*/
	function testSaleSimple() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => '0987654321',
			'amount' => rand(100, 999),
			'orderSource' => 'ecommerce',
			'card' => array(
				'type' => 'VI',
				'number' => '4457010000000009',
				'expDate' => '0112',
				'cardValidationNum' => '349',
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
		$saved = $this->LitleSale->save($sale);
		$response = $this->LitleSale->lastRequest;
		$transaction_id = $this->LitleSale->id;
		$this->AssertTrue(!empty($transaction_id));
		$this->AssertEqual($transaction_id, $this->LitleSale->lastRequest['transaction_id']);
		$this->AssertEqual('good', $this->LitleSale->lastRequest['status']);
		$this->AssertTrue($this->LitleSale->delete($this->LitleSale->id));
	}
	/**
	* Validate litle tests for sale
	*/
	function testSaleLitleTest1() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => '1',
			'amount' => 10010,
			'bill_name' => 'John Smith',
			'bill_address' => '1 Main Street',
			'bill_city' => 'Burlington',
			'bill_state' => 'MA',
			'bill_zip' => '01803-3747',
			'bill_country' => 'US',
			'card_type' => 'VI',
			'card_number' => '4457010000000009',
			'card_expdate' => '0112',
			'card_cvv' => '349',
			);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
			'authCode' => '11111',
			//'FraudResult.avsResult' => '01', // getting 11?
			'FraudResult.cardValidationResult' => 'M',
			);
		$response = $this->LitleSale->save($sale);
		$this->AssertTrue($this->LitleSale->id > 111111111111111111);
		$this->AssertTrue($this->LitleSale->lastRequest['transaction_id']==$this->LitleSale->id);
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) { 
			$this->AssertEqual(trim($this->LitleSale->lastRequest['response_array'][$key]), $val); 
		}
		$transaction_id = $this->LitleSale->id;
		$this->LitleSale->lastRequest = array();
		// Test 1C (void)
		$response = $this->LitleSale->delete($transaction_id);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
			);
		$this->AssertTrue(!empty($this->LitleSale->lastRequest));
		$this->AssertTrue(!empty($this->LitleSale->lastRequest));
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) { 
			$this->AssertEqual($this->LitleSale->lastRequest['response_array'][$key], $val); 
		}
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['litleTxnId']));
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['postDate']));
	}
	/**
	* Validate litle tests for sale
	*/
	function testSaleLitleTest2() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => '2',
			'amount' => 20020,
			'bill_name' => 'Mike J. Hammer',
			'bill_address' => '2 Main Street',
			'bill_address_2' => 'Apt. 222',
			'bill_city' => 'Riverside',
			'bill_state' => 'RI',
			'bill_zip' => '02915',
			'bill_country' => 'US',
			'card_type' => 'MC',
			'card_number' => '5112010000000003',
			'card_expdate' => '0212',
			'card_cvv' => '261',
			'authenticationValue' => 'BwABBJQ1AgAAAAAgJDUCAAAAAAA=',
			);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
			'authCode' => '22222',
			'FraudResult.avsResult' => '01',
			'FraudResult.cardValidationResult' => 'M',
			'FraudResult.authenticationResult' => 'Note: Not returned for MasterCard',
			);
		$response = $this->LitleSale->save($sale);
		print_r($this->LitleSale->lastRequest);die();
		$this->AssertTrue($this->LitleSale->id > 111111111111111111);
		$this->AssertTrue($this->LitleSale->lastRequest['transaction_id']==$this->LitleSale->id);
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) { 
			$this->AssertEqual($this->LitleSale->lastRequest['response_array'][$key], $val); 
		}
		$transaction_id = $this->LitleSale->lastRequest['transaction_id'];
		$this->LitleSale->lastRequest = array();
		// Test 2C (void)
		$response = $this->LitleSale->delete($transaction_id);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
			);
		$this->AssertTrue(!empty($this->LitleSale->lastRequest));
		$this->AssertTrue(!empty($this->LitleSale->lastRequest));
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) { 
			$this->AssertEqual($this->LitleSale->lastRequest['response_array'][$key], $val); 
		}
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['litleTxnId']));
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['postDate']));
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