<?php
App::import('Datasource', 'litle.LitleSource');
App::import('Model', 'litle.LitleAppModel');
App::import('Lib', 'Templates.AppTestCase');
class LitleAppModelTestCase extends AppTestCase {

	public $plugin = 'app';
	public $fixtures = array();
	public $config = array(
		'user' => 'USERNAME', // test environment
		'password' => 'PASSWORD', // test environment
		'merchantId' => '123456',
		'url' => 'https://cert.litle.com/vap/communicator/online',
		// --- Other Configurations
		'logModel' => 'LitleApiLog', // null to disable transaction logging
		'auto_orderId_if_missing' => true,
		'auto_id_if_missing' => true, // you should probably keep this as true
		'duplicate_window_in_seconds' => true, // protection against duplicate transactions (only used if auto_transactionId)
		// translate your local fields to special fields
		'field_map' => array(
			'billToAddress.name'			=> array('bill_name'),
			'billToAddress.addressLine1'	=> array('bill_address'),
			'billToAddress.addressLine2'	=> array('bill_address_2'),
			'billToAddress.addressLine3'	=> array('bill_address_3'),
			'billToAddress.city'			=> array('bill_city'),
			'billToAddress.state'			=> array('bill_state'),
			'billToAddress.zip'				=> array('bill_zip'),
			'billToAddress.county'			=> array('bill_county'),
			'card.number'					=> array('card_number', 'cc_account', 'cc_number', 'account'),
			'card.expDate'					=> array('card_expdate', 'card_expire', 'cc_expires', 'cc_expire', 'expires'),
			'card.cardValidationNum'		=> array('card_cardvalidationnum', 'card_cvv', 'cc_cvv', 'cvv', 'cvvn'),
			),
		// You can assign default values for ANY API interaction (after the translation)
		'defaults' => array(
			// sale transactions
			'sale' => array(
				'reportGroup' => '1',
				'orderSource' => 'ecommerce',
				'billToAddress' => array(
					'country' => 'US',
					),
				'customBilling' => array(
					'phone' => '8888888888',
					'descriptor' => 'abc*ABC Company, LLC',
					),
				),
			'void' => array(),
			'refund' => array(),
			'token' => array(),
			// etc..
			),
		);
	public $test1 = array(
			'reportGroup' => '1',
			'orderId' => '1',
			'amount' => 125,
			'orderSource' => 'ecommerce',
			'billToAddress' => array (
				'name' => 'John Smith',
				'addressLine1' => '1 Main Street',
				'city' => 'Burlington',
				'state' => 'MA',
				'zip' => '01803-3747',
				'country' => 'US',
				),
			'card' => array (
				'type' => 'VI',
				'number' => '4457010000000009',
				'expDate' => '0112',
				'cardValidationNum' => '349',
				),
			'customBilling' => array (
				'phone' => '8888888888',
				'descriptor' => 'abc*ABC Company, LLC',
				),
			'attrib' => array (
				'id' => 1320253459,
				'reportGroup' => 'test',
				),
			);
	protected $_testsToRun = array();

	/**
	* Start Test callback
	*
	* @param string $method
	* @return void
	* @access public
	*/
	public function startTest($method) {
		$this->LitleAppModel = new LitleAppModel(false, null, 'litle');
		$this->LitleAppModel->config = set::merge($this->LitleAppModel->config, $this->config);
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
		unset($this->LitleAppModel);
		ClassRegistry::flush();
	}

	/**
	* Validate the plugin setup
	*/
	function testSetup() {
		$this->assertTrue(is_object($this->LitleAppModel));
		$this->assertTrue(isset($this->LitleAppModel->useTable));
		$this->assertFalse($this->LitleAppModel->useTable);
	}
	/**
	* Validate the config setup
	* /
	function testConfig() {
		$this->assertTrue(isset($this->config));
		// TODO: confirm config overwritting
		$uid = time();
		$this->LitleAppModel->config(compact('uid'));
		$this->assertTrue(array_key_exists('uid', $this->config));
		$this->assertIdentical($this->config['uid'], $uid);
		$uid = 'something else';
		$this->LitleAppModel->config(compact('uid'));
		$this->assertIdentical($this->config['uid'], $uid);
	}
	/**
	* Validate orderFields functionality
	*/
	function testOrderFields() {
		$response = $expected = $data = $this->test1;
		$this->AssertEqual(json_encode($response), json_encode($expected));
		$this->__deep_ksort($response);
		$this->AssertNotEqual(json_encode($response), json_encode($expected));
		$this->__deep_ksort($data);
		$response = $this->LitleAppModel->orderFields($data, 'sale');
		$this->AssertEqual(json_encode($response), json_encode($expected));
		// shuffle more
		$card = $data['card'];
		unset($data['card']);
		$number = $card['number'];
		unset($card['number']);
		$card['number'] = $number;
		$data['card'] = $card;
		$amount = $data['amount'];
		unset($data['amount']);
		$data['amount'] = $amount;
		$this->AssertNotEqual(json_encode($data), json_encode($expected));
		$response = $this->LitleAppModel->orderFields($data, 'sale');
		$this->AssertEqual(json_encode($response), json_encode($expected));
	}
	/**
	* Validate the translateFields
	*/
	function testTranslateFields() {
		// control | pass in the anticipated results and ensure they are unchanged
		$response = $expected = $data = $this->test1;
		$this->__deep_ksort($data);
		$this->__deep_ksort($expected);
		$response = $this->LitleAppModel->translateFields($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertEqual($response, $expected);
		// now lets pass in some dot.notaion fields to be translated
		unset($data['card']);
		foreach ( $this->test1['card'] as $key => $val ) {
			$data["card.{$key}"] = $val;
		}
		$response = $this->LitleAppModel->translateFields($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertEqual($response, $expected);
		// now lets mix-and-match, with overwrites (explicit path takes precidence
		$data['card']['number'] = $this->test1['card']['number'];
		$data["card.number"] = 'bad value';
		$response = $this->LitleAppModel->translateFields($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertEqual($response, $expected);
		// now lets pass in some extra dot suffixes
		$data = $expected;
		$data['custom.extra1'] = $expected['custom']['extra1'] = 'found';
		$data['custom.extra2.super.nested'] = $expected['custom']['extra2']['super']['nested'] = 'found';
		$data['custom.extra2.super.nested'] = $expected['custom']['extra2']['super']['nested'] = 'found';
		$data["card.number"] = 'bad value'; // shouldn't overwrite existing final paths
		$response = $this->LitleAppModel->translateFields($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertEqual($response, $expected);
		// now lets pass in some fieldname translations
		$data = $expected;
		unset($data['orderId']);
		unset($data['card']['number']);
		unset($data['billToAddress']['addressLine1']);
		$data['bill_id'] = $this->test1['orderId'];
		$data['card_number'] = $this->test1['card']['number'];
		$data['bill_address'] = $this->test1['billToAddress']['addressLine1'];
		$response = $this->LitleAppModel->translateFields($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertEqual($response, $expected);
	}
	/**
	* Validate the assignDefaults
	*/
	function testAssignDefaults() {
		// control | pass in the anticipated results and ensure they are unchanged
		$expected = $data = $this->test1;
		$this->__deep_ksort($data);
		$this->__deep_ksort($expected);
		$response = $this->LitleAppModel->assignDefaults($data, 'sale');
		$this->AssertEqual($response, $expected);
		// now test the inclusion of defaults
		// unset fields should be included by default
		unset($data['orderSource']);
		unset($data['billToAddress']['country']);
		unset($data['customBilling']);
		$response = $this->LitleAppModel->assignDefaults($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertEqual($response, $expected);
		// now confirm we are not getting "magic" replacements
		unset($data['card']['number']);
		$response = $this->LitleAppModel->assignDefaults($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertNotEqual($response, $expected);
		// now tweak the config and retest
		$this->LitleAppModel->config['defaults']['sale']['card']['number'] = $this->test1['card']['number'];
		$response = $this->LitleAppModel->assignDefaults($data, 'sale');
		$this->__deep_ksort($response);
		$this->AssertEqual($response, $expected);
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
}
?>
