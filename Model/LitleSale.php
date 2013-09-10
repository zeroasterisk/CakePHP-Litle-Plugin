<?php
/**
* Plugin model for "Litle Credit Card Sale Processing".
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link https://github.com/zeroasterisk/CakePHP-Litle-Plugin
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
App::uses('LitleAppModel', 'Litle.Model');
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
		'govt_tax' => array('type' => 'string', 'length' => '16', 'options' => array('payment', 'fee')),
		'currency_code' => array('type' => 'string', 'length' => '3', 'options' => array('AUD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'NOK', 'NZD', 'SEK', 'SGD', 'USD')),
		);
	/**
	* These fields are submitted in a saleResponse from Litle
	* @var array
	*/
	public $_schema_response = array(
		'litleTxnId' => array('type' => 'string', 'length' => '25', 'comment' => 'litle\'s unique transaction id from response'),
		'response' => array('type' => 'integer', 'length' => '3', 'comment' => 'response code'),
		'responseTime' => array('type' => 'datetime'),
		'message' => array('type' => 'string', 'length' => '512', 'comment' => 'brief definition of the response code'),
		);
	/**
	* beforeSave reconfigures save inputs for "sale" transactions
	* assumes LitleSale->data exists and has the details for the save()
	* @param array $options
	* @return array $response
	*/
	function beforeSave($options=array()) {
		parent::beforeSave($options);
		// TODO: use token or use card <<?
		$errors = array();
		// setup defaults so elements are in the right order.
		$data = $this->data[$this->alias];
		$data = $this->translateFields($data, 'sale');
		$data = $this->assignDefaults($data, 'sale');
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
				$errors[] = "Missing required Sale: payment fields";
			}
		}
		// prep sale element attributes
		$reportGroup = (isset($data['reportGroup']) ? $data['reportGroup'] : 'unspecified');
		$customerId = (isset($data['customerId']) ? $data['customerId'] : 0);
		$id = (isset($data['id']) ? $data['id'] : time());
		$rootAttributes = compact('id', 'customerId', 'reportGroup');
		$data = $this->finalizeFields($data, 'sale', $rootAttributes);
		$this->data = array($this->alias => $data);
		// verfiy fail on errors
		if (!empty($errors)) {
			$status = 'failed';
			$this->lastRequest = compact('status', 'errors', 'data', 'data_raw');
			return false;
		}
		return true;
	}
	/**
	* afterSave parses results and verifies status for this transaction
	* assumes LitleSale->lastRequest exists and has the details for this request
	* @param array $options
	* @return array $response
	*/
	function afterSave($created=null) {
		parent::afterSave($created);
		if (empty($this->lastRequest)) {
			$this->lastRequest = array('status' => 'error', 'errors' => array("Unable to access {$this->Alias}->lastRequest"));
			return false;
		}
		extract($this->lastRequest);
		if (isset($response_array['SaleResponse'])) {
			$response_array = set::flatten($response_array['SaleResponse']);
		}
		extract($response_array);
		$this->id = $transaction_id = (!empty($litleTxnId) ? $litleTxnId : 0);
		if (empty($transaction_id) && empty($errors)) {
			$errors[] = "Missing transaction_id (litleTxnId)";
		}
		if ($response!="000" && $response!="0" && empty($errors)) {
			$errors[] = "Error: {$message}";
		}
		if (!empty($errors)) {
			$status = "error";
		}
		// todo: parse recycle response
		$this->lastRequest = compact($this->requestVars);
		return true;
	}
	/**
	* Overwrite of the delete function
	* Performs a Void, and if that fails, tries to Credit
	* @param int $transaction_id
	* @param string $orderId optional
	* @param string $reportGroup optional
	*/
	function delete($transaction_id=null, $orderId=null, $reportGroup=null) {
		$this->lastRequest = array();
		$errors = array();
		if (empty($transaction_id) || !is_numeric($transaction_id)) {
			$status = 'failed';
			$errors[] = "Invalid or missing transaction_id";
			$this->lastRequest = compact('status', 'errors', 'transaction_id');
			return false;
		}
		App::import('Model', 'Litle.LitleVoid');
		$LitleVoid =& ClassRegistry::init('Litle.LitleVoid');
		$LitleVoid->useDbConfig = 'litle';
		$data = array('litleTxnId' => $transaction_id) + (!empty($reportGroup) ? array('reportGroup' => $reportGroup) : array());
		if (empty($orderId) && !empty($orderId) && (is_string($orderId) || is_int($orderId))) {
			$data['orderId'] = $orderId;
		}
		$saved = $LitleVoid->save($data);
		$this->log[] = $LitleVoid->log;
		$this->errors[] = $LitleVoid->errors;
		$this->lastRequest = $LitleVoid->lastRequest;
		if (isset($this->lastRequest['status']) && $this->lastRequest['status']=='good') {
			return true;
		}
		// now do a credit (no amount = full)
		App::import('Model', 'Litle.LitleCredit');
		$LitleCredit =& ClassRegistry::init('Litle.LitleCredit');
		$LitleCredit->useDbConfig = 'litle';
		$LitleCredit->save($data);
		$this->log[] = $LitleCredit->lastRequest;
		$this->lastRequest = $LitleCredit->lastRequest;
		if ($this->lastRequest['status']=='good') {
			return true;
		}
		// neither the credit nor the void worked...
		return false;
	}
}
?>
