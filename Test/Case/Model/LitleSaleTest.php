<?php
/**
 * Unit Tests for Model/LitleSale (the main model for charges/sales)
 *
 * @link https://github.com/zeroasterisk/CakePHP-ArrayToXml-Lib
 * @author Alan Blount <alan@zeroasterisk.com>
 * @copyright (c) 2011 Alan Blount
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
App::uses('LitleSource','Litle.Model/Datasource');
App::uses('LitleSale', 'Litle.Model');
App::uses('LitleUtil', 'Litle.Lib');
App::uses('Set', 'Utility');
class LitleSaleTest extends CakeTestCase {
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
		unset($this->LitleSale);
		ClassRegistry::flush();
	}
	/**
	 * Validate the plugin setup
	 */
	public function testSetup() {
		$this->assertTrue(is_object($this->LitleSale));
		$this->assertEqual($this->LitleSale->alias, 'LitleSale');
		$this->assertTrue(empty($this->LitleSale->useTable));
		$config_user = LitleUtil::getConfig('user');
		$this->assertFalse(empty($config_user), "You are missing the configuration Username");
		$config_password = LitleUtil::getConfig('password');
		$this->assertFalse(empty($config_password), "You are missing the configuration Password");
	}
	/**
	 * Validate translate fields
	 */
	public function testTranslateFields() {
		$field_map = LitleUtil::getConfig('field_map');
		$field_map = Set::merge($field_map, array(
			'id' 							=> array('unique_id'),
			'orderId' 						=> array('bill_id', 'order_id'),
			'billToAddress.name' 			=> array('bill_name'),
			'billToAddress.addressLine1'	=> array('bill_address'),
			'billToAddress.addressLine2'	=> array('bill_address_2'),
			'billToAddress.addressLine3'	=> array('bill_address_3'),
			'billToAddress.city'			=> array('bill_city'),
			'billToAddress.state'			=> array('bill_state'),
			'billToAddress.zip'				=> array('bill_zip'),
			'billToAddress.country'			=> array('bill_country'),
			'card.type'						=> array('cc_type', 'funky_card_type'),
			'card.number'					=> array('cc_account', 'funky_card_account'),
			'card.expDate'					=> array('cc_expires', 'funky_card_exp'),
			'card.cardValidationNum'		=> array('funky_card_cvv'),
		));
		LitleUtil::setConfig('field_map', $field_map);
		$data = array(
			'id' => 'shouldStay',
			'unique_id' => 'shouldNotOverwriteId',
			'name' => 'Bubba Doe',
			'funky_card_account' => '5186005800001012', // translation via config
			'funky_card_exp' => '1110', // translation via config
			'funky_card_cvv' => '321', // translation via config
			'funky_card_type' => 'MC', // translation via config
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
			'id' => 'shouldStay',
			'unique_id' => 'shouldNotOverwriteId',
			'card' => array(
				'number' => '5186005800001012',
				'expDate' => '1110',
				'type' => 'MC',
				'cardValidationNum' => '321',
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
	public function testSaleSimple() {
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
	/*  */
	public function test1() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => '1',
			'amount' => 10010,
			'bill_name' => 'John Smith',
			'bill_address' => '1 Main St.',
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
			$this->AssertEqual(trim($this->LitleSale->lastRequest['response_array'][$key]), $val, "Test#{$sale['orderId']}: Validation failure for key {$key}={$this->LitleSale->lastRequest['response_array'][$key]} not {$val}");
		}
		$transaction_id = $this->LitleSale->id;
		$this->LitleSale->lastRequest = array();
		// Test C (void)
		$response = $this->LitleSale->delete($transaction_id);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
		);
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual($this->LitleSale->lastRequest['response_array'][$key], $val);
		}
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['litleTxnId']));
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['postDate']));
	}
	/*  */
	public function test2() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => '2',
			'amount' => 20020,
			'bill_name' => 'Mike J. Hammer',
			'bill_address' => '2 Main St.',
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
			//'FraudResult.avsResult' => '01',
			'FraudResult.cardValidationResult' => 'M',
			//'FraudResult.authenticationResult' => 'Note: Not returned for MasterCard',
		);
		$response = $this->LitleSale->save($sale);
		$this->AssertTrue($this->LitleSale->id > 111111111111111111);
		$this->AssertTrue($this->LitleSale->lastRequest['transaction_id']==$this->LitleSale->id);
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual(trim($this->LitleSale->lastRequest['response_array'][$key]), $val, "Test#{$sale['orderId']}: Validation failure for key {$key}={$this->LitleSale->lastRequest['response_array'][$key]} not {$val}");
		}
		$transaction_id = $this->LitleSale->lastRequest['transaction_id'];
		$this->LitleSale->lastRequest = array();
		// Test C (void)
		$response = $this->LitleSale->delete($transaction_id);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
		);
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual($this->LitleSale->lastRequest['response_array'][$key], $val);
		}
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['litleTxnId']));
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['postDate']));
	}
	/*  */
	public function test3() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => '3',
			'amount' => 30030,
			'bill_name' => 'Eileen Jones',
			'bill_address' => '3 Main St.',
			'bill_city' => 'Bloomfield',
			'bill_state' => 'CT',
			'bill_zip' => '06002',
			'bill_country' => 'US',
			'card_type' => 'DI',
			'card_number' => '6011010000000003',
			'card_expdate' => '0312',
			'card_cvv' => '758',
		);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
			'authCode' => '33333',
			'FraudResult.avsResult' => '10',
			'FraudResult.cardValidationResult' => 'M',
		);
		$response = $this->LitleSale->save($sale);
		$this->AssertTrue($this->LitleSale->id > 111111111111111111);
		$this->AssertTrue($this->LitleSale->lastRequest['transaction_id']==$this->LitleSale->id);
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual(trim($this->LitleSale->lastRequest['response_array'][$key]), $val, "Test#{$sale['orderId']}: Validation failure for key {$key}={$this->LitleSale->lastRequest['response_array'][$key]} not {$val}");
		}
		$transaction_id = $this->LitleSale->lastRequest['transaction_id'];
		$this->LitleSale->lastRequest = array();
		// Test C (void)
		$response = $this->LitleSale->delete($transaction_id);
		$expected = array(
			'response' => '000',
			'message' => 'Approved',
		);
		$this->AssertTrue(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual($this->LitleSale->lastRequest['response_array'][$key], $val);
		}
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['litleTxnId']));
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['postDate']));
	}
	/*  */
	public function test6() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => '6',
			'amount' => 60060,
			'bill_name' => 'Joe Green',
			'bill_address' => '6 Main St.',
			'bill_city' => '6 Main St.',
			'bill_state' => 'NH',
			'bill_zip' => '03038',
			'bill_country' => 'US',
			'card_type' => 'VI',
			'card_number' => '4457010100000008',
			'card_expdate' => '0612',
			'card_cvv' => '992',
		);
		$expected = array(
			'response' => '110',
			'message' => 'Insufficient Funds',
			'FraudResult.avsResult' => '34',
			'FraudResult.cardValidationResult' => 'P',
		);
		$response = $this->LitleSale->save($sale);
		$this->AssertTrue($this->LitleSale->id > 111111111111111111);
		$this->AssertTrue($this->LitleSale->lastRequest['transaction_id']==$this->LitleSale->id);
		$this->AssertFalse(empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual(trim($this->LitleSale->lastRequest['response_array'][$key]), $val, "Test#{$sale['orderId']}: Validation failure for key {$key}={$this->LitleSale->lastRequest['response_array'][$key]} not {$val}");
		}
		$this->AssertTrue(!isset($this->LitleSale->lastRequest['response_array']['authCode']));
		$expected_errors = array('Error: Insufficient Funds');
		$this->AssertEqual($this->LitleSale->lastRequest['errors'], $expected_errors);
		$transaction_id = $this->LitleSale->lastRequest['transaction_id'];
		$this->AssertFalse(empty($transaction_id));
		$this->LitleSale->lastRequest = array();
		# -- not working at the moment -- #
		// Test C (void)
		#$response = $this->LitleSale->delete($transaction_id);
		#$this->AssertEqual($this->LitleSale->lastRequest['response_array']['response'], '360');
		#$this->AssertEqual($this->LitleSale->lastRequest['response_array']['message'], 'No transaction found with specified litleTxnId');
		#$this->AssertTrue(!empty($this->LitleSale->lastRequest['errors']));
		#$expected_errors = array('Error: No transaction found with specified litleTxnId');
		#$this->AssertEqual($this->LitleSale->lastRequest['errors'], $expected_errors);
		#print_r(array_intersect_key($this->LitleSale->lastRequest, array('request_raw' => 1,'response_raw' => 1)));
	}
	/*  */
	public function testVIcredit12401() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => 'VIcredit12401',
			'amount' => 12401,
			'bill_name' => 'Mike J. Hammer',
			'bill_address' => '2 Main St.',
			'bill_address_2' => 'Apt. 222',
			'bill_city' => 'Riverside',
			'bill_state' => 'RI',
			'bill_zip' => '02915',
			'bill_country' => 'US',
			'card_type' => 'VI',
			'card_number' => '4457012400000001',
			'card_expdate' => '1220',
		);
		$expected = array(
			'response' => '110',
			'message' => 'Insufficient Funds',
		);
		$response = $this->LitleSale->save($sale);
		$this->AssertTrue($this->LitleSale->id > 111111111111111111);
		$this->AssertTrue($this->LitleSale->lastRequest['transaction_id']==$this->LitleSale->id);
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual(trim($this->LitleSale->lastRequest['response_array'][$key]), $val, "Test#{$sale['orderId']}: Validation failure for key {$key}={$this->LitleSale->lastRequest['response_array'][$key]} not {$val}");
		}
		if (isset($this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.recycleAdviceEnd'])) {
			$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.recycleAdviceEnd']));
		} elseif (isset($this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.nextRecycleTime'])) {
			$recycle_date = $this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.nextRecycleTime'];
			$recycle_epoch = strtotime($recycle_date);
			$min_epoch = strtotime('+12 hours');
			$max_epoch = strtotime('+96 hours');
			$this->AssertTrue($recycle_epoch > $min_epoch, "nextRecycleTime not far enough in future [$recycle_date] {$recycle_epoch} < ".date("Y-m-d H:i:s", $min_epoch) );
			$this->AssertTrue($recycle_epoch < $max_epoch, "nextRecycleTime too far in future [$recycle_date] {$recycle_epoch} > ".date("Y-m-d H:i:s", $max_epoch) );
			$expected_errors = array('Error: Insufficient Funds');
			$this->AssertEqual($this->LitleSale->lastRequest['errors'], $expected_errors);
		} else {
			$this->AssertTrue(false, "Unable to find 'Recycling.RecycleAdvice' details");
		}
	}
	/* */
	public function testVIprepaid13201() {
		$sale = array(
			'reportGroup' => 'test',
			'orderId' => 'VIprepaid13201',
			'amount' => 13201,
			'bill_name' => 'Mike J. Hammer',
			'bill_address' => '2 Main St.',
			'bill_address_2' => 'Apt. 222',
			'bill_city' => 'Riverside',
			'bill_state' => 'RI',
			'bill_zip' => '02915',
			'bill_country' => 'US',
			'card_type' => 'VI',
			'card_number' => '4457013200000001',
			'card_expdate' => '1220',
		);
		$expected = array(
			'response' => '349',
			'message' => 'Do Not Honor',
		);
		$response = $this->LitleSale->save($sale);
		$this->AssertTrue($this->LitleSale->id > 111111111111111111);
		$this->AssertTrue($this->LitleSale->lastRequest['transaction_id']==$this->LitleSale->id);
		$this->AssertTrue(!empty($this->LitleSale->lastRequest['errors']));
		foreach ( $expected as $key => $val ) {
			$this->AssertEqual(trim($this->LitleSale->lastRequest['response_array'][$key]), $val, "Test#{$sale['orderId']}: Validation failure for key {$key}={$this->LitleSale->lastRequest['response_array'][$key]} not {$val}");
		}
		if (isset($this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.recycleAdviceEnd'])) {
			$this->AssertTrue(!empty($this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.recycleAdviceEnd']));
		} elseif (isset($this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.nextRecycleTime'])) {
			$recycle_date = $this->LitleSale->lastRequest['response_array']['Recycling.RecycleAdvice.nextRecycleTime'];
			$recycle_epoch = strtotime($recycle_date);
			$min_epoch = strtotime('+60 hours');
			$max_epoch = strtotime('+6 days');
			$this->AssertTrue($recycle_epoch > $min_epoch, "nextRecycleTime not far enough in future [$recycle_date] {$recycle_epoch} < ".date("Y-m-d H:i:s", $min_epoch) );
			$this->AssertTrue($recycle_epoch < $max_epoch, "nextRecycleTime too far in future [$recycle_date] {$recycle_epoch} > ".date("Y-m-d H:i:s", $max_epoch) );
			$expected_errors = array('Error: Do Not Honor');
			$this->AssertEqual($this->LitleSale->lastRequest['errors'], $expected_errors);
		} else {
			$this->AssertTrue(false, "Unable to find 'Recycling.RecycleAdvice' details");
		}
	}

	/**
	 *
	 *
	 */
	public function __deep_ksort(&$arr) {
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
	public function __deep_shuffle(&$arr) {
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
