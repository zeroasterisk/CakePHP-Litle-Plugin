<?php
/**
* Plugin model for "Litle Credit Card Sale Processing".
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link http://zeroasterisk.com
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*
* @example
	$saleWorked = $this->LitleSale->save(array(
		'orderId' => 1234,
		'amount' => 100,
		'orderSource' => 'ecommerce',
	));
	debug($saleWorked);
	debug($this->LitleSale->lastRequest);
*/
class LitleSale extends LitleAppModel {
	/**
	* The name of this model
	* @var name
	*/
	public $name ='LitleSale';
	/**
	* This model doesn't use a table
	* @var name
	*/
	public $useTable = false;
	/**
	* This model requires the datasource of litle
	* @var name
	*/
	public $useDbConfig = 'litle';
	/**
	* Placeholder for the last transaction
	* @var name
	*/
	public $lastSale = null;
	/**
	*
	*
	*/
	public $primaryKey = 'id';
	/**
	* The fields and their types for the form helper
	* @var array
	*/
	public $_schema = array(
		// sale attributes
		'id' => array('type' => 'string', 'length' => '25', 'comment' => 'unique transaction id (determines duplicates)'),
		'reportGroup' => array('type' => 'string', 'length' => '25', 'comment' => 'required attribute that defines the merchant sub-group'),
		'customerId' => array('type' => 'string', 'length' => '25', 'comment' => 'required attribute that defines the merchant sub-group'),
		// sale elements
		'orderId' => array('type' => 'integer', 'length' => '8', 'comment' => 'internal order id'),
		'amount' => array('type' => 'integer', 'length' => '8', 'comment' => 'a value of 1995 signifies $19.95'),
		'orderSource' => array('type' => 'string', 'length' => '25', 'comment' => 'defines the order entry source for the type of transaction', 'options' => array('3dsAuthenticated', '3dsAttempted', 'ecommerce', 'installment', 'mailorder', 'recurring', 'retail', 'telephone')),
		'card' => array('type' => 'blob'),
		'token' => array('type' => 'blob'),
		'billToAddress' => array('type' => 'blob'),
		'customerInfo' => array('type' => 'blob'),
		'customBilling' => array('type' => 'blob'),
		'enhancedData' => array('type' => 'blob'),
		// extra field to create root level element
		'root' => array('type' => 'blob'),
		); 
	/**
	* These fields are commonly used as extras on this model 
	* should be parsed into containers before _schema validation on save
	* @var array
	*/
	public $_schema_extras = array(
		'card_number' => array('type' => 'integer', 'length' => '25', 'comment' => 'account number associated with the transaction.'),
		'card_expDate' => array('type' => 'integer', 'length' => '4', 'comment' => 'required for card-not-present transactions. / You should submit whatever expiration date you have on file, regardless of whether or not it is expired/stale.'),
		'card_type' => array('type' => 'string', 'length' => '2', 'comment' => '', 'options' => array('MC', 'VI',' AX', 'DC', 'DI', 'PP', 'JC', 'BL', 'EC')),
		'card_cardValidationNum' => array('type' => 'string', 'length' => '4', 'comment' => 'optional'),
		'token_tokenResponseCode' => array('type' => 'string', 'length' => '3', 'options' => array('801', '802', '820', '821', '822', '823', '898', '899'), 'comment' => 'The tokenMessage element contains a short, human-readable explanation of the tokenResponseCode'),
		'token_tokenMessage' => array('type' => 'string', 'length' => '512', 'comment' => 'The tokenMessage element provides a short, human-readable explanation of the tokenResponseCode'),
		'token_litleToken' => array('type' => 'string', 'length' => '25', 'comment' => 'The length of the token is the same as the length of the submitted account number for credit card tokens or a fixed length of seventeen (17) characters for eCheck account tokens.'),
		'token_type' => array('type' => 'string', 'length' => '3', 'comment' => 'Bank Identification Number'),
		'token_bin' => array('type' => 'string', 'length' => '3', 'comment' => 'Bank Identification Number'),
		'govt_tax' => array('type' => 'string', 'length' => '16', 'options' => array('payment', 'fee')),
		'currency_code' => array('type' => 'string', 'length' => '3', 'options' => array('AUD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'NOK', 'NZD', 'SEK', 'SGD', 'USD')),
		);
	/**
	* beforeSave reconfigures save inputs for "sale" transactions
	* assumes LitleSale->data exists and has the details for the save()
	* @param array $options optional extra litle config data
	* @return array $response
	*/
	function beforeSave($options=array()) {
		// TODO: use token or use card <<?
		$config = set::merge($this->config, $options);
		$errors = array();
		// setup defaults so elements are in the right order.
		$data_default = array(
			// attrib
			'id' => null,
			'reportGroup' => null,
			'customerId' => null,
			// child
			'orderId' => null,
			'amount' => null,
			'orderSource' => null,
			);
		$data = $data_raw = set::merge($data_default, $this->data[$this->alias]);
		$data = $this->translateFields($data, 'sale');
		$data = $this->assignDefaults($data, 'sale');
		if ($config['auto_orderId_if_missing'] && (!isset($data['orderId']) || empty($data['orderId']))) {
			$data['orderId'] = time();
		}
		$requiredFields = array('reportGroup', 'orderId', 'amount', 'orderSource');
		foreach ( $requiredFields as $key ) { 
			if (!array_key_exists($key, $data) || empty($data[$key])) {
				$errors[] = "Missing required field [{$key}]";
			}
		}
		$requiredPayment = array('card', 'paypal', 'paypage', 'token');
		$foundPayment = array_intersect_key($data, array_flip($requiredPayment));
		if (empty($foundPayment)) {
			$errors[] = "Missing required payment";
		} else {
			$requiredFields = array('number', 'expDate');
			$foundRequiredFields = array_intersect_key($data, $foundPayment);
			if (empty($foundRequiredFields)) {
				$errors[] = "Missing required payment fields";
			} else {
				foreach ( $foundRequiredFields as $paymentKey => $paymentData ) { 
					foreach ( $paymentData as $key => $val ) { 
						if (empty($val)) {
							$errors[] = "Missing required payment field {$paymentKey}.{$key} (empty)";
						}
					}
				}
			}
		}
		// the transaction_id is used to determine duplicate transactions
		if ($config['auto_id_if_missing'] && (!isset($data['auto_id_if_missing']) || empty($data['auto_id_if_missing']))) {
			if (isset($config['duplicate_window_in_seconds']) && !empty($config['duplicate_window_in_seconds'])) {
				$data['id'] = $data['orderId'].'-'.ceil(time() / $config['duplicate_window_in_seconds']);
			} else {
				$data['id'] = $data['orderId'].'-'.time();
			}
		}
		// include only allowed fields (must be defined in the schema)
		$stripped_data_keys = array_diff(array_keys($data), array_keys($this->_schema));
		$data = array_intersect_key($data, $this->_schema);
		// prep sale element attributes
		$reportGroup = (isset($data['reportGroup']) ? $data['reportGroup'] : 'unspecified');
		$customerId = (isset($data['customerId']) ? $data['customerId'] : 0);
		$id = (isset($data['id']) ? $data['id'] : time());
		$saleAttributes = compact('reportGroup', 'customerId', 'id');
		$data = array_diff_key($data, $saleAttributes);
		$data['root'] = 'sale|'.json_encode($saleAttributes);
		$this->data = array($this->alias => $data);
		// verfiy on errors
		if (!empty($errors)) {
			$status = 'failed';
			$this->lastRequest = compact('status', 'errors', 'data', 'data_raw');
			return false;
		}
		return true;
	}
	/**
	* afterSave parses results and verifies status for "sale" transactions
	* assumes LitleSale->lastRequest exists and has the details for the LitleSource->_re
	* @param array $options optional extra litle config data
	* @return array $response
	*/
	function afterSave($created=null) {
		parent::afterSave($created);
		if (empty($this->lastRequest)) {
			$this->lastRequest = array('status' => 'error', 'errors' => array("Unable to access {$this->Alias}->lastRequest"));
			return false;
		}
		extract($this->lastRequest);
		echo dumpthis($response_array);
		$this->lastRequest = compact('status', 'transaction_id', 'errors', 'data_json', 'data', 'response_array', 'response_raw');
		return true;
	}
}
?>
