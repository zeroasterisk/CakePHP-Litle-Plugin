<?php
/**
* Plugin AppModel for "Litle" API.
*
* The LitleAppModel takes care of
*	setting up beforeSave() to translate/default fields
*	setting up afterSave() to translate/parse responses
*	setting up logRequest() to log responses (if configured)
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link https://github.com/zeroasterisk/CakePHP-Litle-Plugin
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*
*/
class LitleAppModel extends AppModel {
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
	* This is a placeholder array for configuration (shared with LitleSource)
	* @param array $config
	*/
	static public $config = array();
	/**
	* This is a placeholder array for lastRequest
	* @param array $lastRequest
	*/
	public $lastRequest = array();
	/**
	* This is a placeholder array for an in-object log of all steps
	* @param mixed $log array() to enable, false to disable
	*/
	public $log = array();
	/**
	* This is a placeholder array for an in-object log of all errors
	* @param mixed $errors array() to enable, false to disable
	*/
	public $errors = array();
	/**
	* This is a placeholder for the Log model to log API interactions to
	* @param mixed $logModel string to enable, false to disable
	*/
	public $logModel = false;
	/**
	* Elements require specific ordering, even for optional children :(
	* So we have to define the structure, merge in values, strip out blanks
	* handled in beforeSave()
	* NOTE: we "should" be able to just use the schema for the core elements,
	*   but all the nested elements are another matter...
	*   seemed cleaner to just re-specify here.
	* @param array $templates
	*/
	public $templates = array(
		'sale' => array(
			// attrib
			'id' => null,
			'reportGroup' => null,
			'customerId' => null,
			// child
			'orderId' => null,
			'amount' => null,
			'orderSource' => null,
			'customerInfo' => null,
			'billToAddress' => null,
			'shipToAddress' => null,
			'card' => null,
			'paypage' => null,
			'token' => null,
			'paypal' => null,
			'billMeLaterRequest' => null,
			'cardholderAuthentication' => null,
			'customBilling' => null,
			'taxType' => null,
			'enhancedData' => null,
			'processingInstructions' => null,
			'pos' => null,
			'payPalOrderComplete' => null,
			'amexAggregatorData' => null,
			'allowPartialAuth' => null,
			'healthcareIIAS' => null,
			'filtering' => null,
			'merchantData' => null,
			'litleTxnId' => null,
			),
		'void' => array(
			// attrib
			'id' => null,
			'reportGroup' => null,
			'customerId' => null,
			// child
			'litleTxnId' => null,
			'processingInstructions' => null,
			),
		'credit' => array(
			// attribs
			'id' => null,
			'reportGroup' => null,
			'customerId' => null,
			// child
			'litleTxnId' => null,
			'amount' => null,
			),
		'card' => array(
			'type' => null,
			'number' => null,
			'expDate' => null,
			'cardValidationNum' => null,
			),
		'token' => array(
			'litleToken' => null,
			'expDate' => null,
			'cardValidationNum' => null,
			'routingNum' => null,
			'accType' => null,
			),
		'billToAddress' => array(
			'name' => null,
			'firstName' => null,
			'middleInitial' => null,
			'lastName' => null,
			'companyName' => null,
			'addressLine1' => null,
			'addressLine2' => null,
			'addressLine3' => null,
			'city' => null,
			'state' => null,
			'zip' => null,
			'country' => null,
			'email' => null,
			'phone' => null,
			),
		'customerInfo' => array(
			'ssn' => null,
			'dob' => null,
			'customerRegistrationDate' => null,
			'customerType' => null,
			'incomeAmount' => null,
			'employerName' => null,
			'customerWorkTelephone' => null,
			'residenceStatus' => null,
			'yearsAtResidence' => null,
			'yearsAtEmployer' => null,
			),
		'customBilling' => array(
			'phone' => null,
			'url' => null,
			'city' => null,
			'descriptor' => null,
			),
		'enhancedData' => array(
			'customerReference' => null,
			'salesTax' => null,
			'deliveryType' => null,
			'taxExempt' => null,
			'discountAmount' => null,
			'shippingAmount' => null,
			'dutyAmount' => null,
			'shipFromPostalCode' => null,
			'destinationPostalCode' => null,
			'destinationCountryCode' => null,
			'invoiceReferenceNumber' => null,
			'orderDate' => null,
			'detailTax' => null,
			'lineItemData' => null,
			),
		'detailTax' => array(
			'taxAmount' => null,
			'taxIncludedInTotal' => null,
			'taxRate' => null,
			'taxTypeIdentifier' => null,
			'cardAcceptorTaxId' => null,
			),
		'lineItemData' => array(
			'itemDescription' => null,
			'itemSequenceNumber' => null,
			'productCode' => null,
			'quantity' => null,
			'unitOfMeasure' => null,
			'taxAmount' => null,
			'lineItemTotal' => null,
			'lineItemTotalWithTax' => null,
			'itemDiscountAmount' => null,
			'commodityCode' => null,
			'unitCost' => null,
			'detailTax' => null,
			),
		'registerTokenRequest' => array(
			'orderId' => null,
			'accountNumber' => null,
			'echeckForToken' => null,
			'paypageRegistrationId' => null,
			),
		'processingInstructions' => array(
			'bypassVelocityCheck' => null,
			),
		// various attributes and elements (so we know how to truncate)
		'schema' => array(
			'id' => array('type' => 'string', 'length' => '25', 'comment' => 'unique transaction id (determines duplicates)'),
			'reportGroup' => array('type' => 'string', 'length' => '25', 'comment' => 'required attribute that defines the merchant sub-group'),
			'customerId' => array('type' => 'string', 'length' => '25', 'comment' => 'required attribute that defines the merchant sub-group'),
			'orderId' => array('type' => 'integer', 'length' => '8', 'comment' => 'internal order id'),
			'amount' => array('type' => 'integer', 'length' => '8', 'comment' => 'a value of 1995 signifies $19.95'),
			'orderSource' => array('type' => 'string', 'length' => '25', 'comment' => 'defines the order entry source for the type of transaction', 'options' => array('3dsAuthenticated', '3dsAttempted', 'ecommerce', 'installment', 'mailorder', 'recurring', 'retail', 'telephone')),
			'litleTxnId' => array('type' => 'string', 'length' => '25', 'comment' => 'litle\'s unique transaction id from response'),
			'number' => array('type' => 'integer', 'length' => '25', 'comment' => 'account number associated with the transaction.'),
			'expDate' => array('type' => 'integer', 'length' => '4', 'comment' => 'required for card-not-present transactions. / You should submit whatever expiration date you have on file, regardless of whether or not it is expired/stale.'),
			'type' => array('type' => 'string', 'length' => '2', 'comment' => '', 'options' => array('MC', 'VI',' AX', 'DC', 'DI', 'PP', 'JC', 'BL', 'EC')),
			'cardValidationNum' => array('type' => 'string', 'length' => '4', 'comment' => 'optional'),
			'litleToken' => array('type' => 'string', 'length' => '25', 'comment' => 'The length of the token is the same as the length of the submitted account number for credit card tokens or a fixed length of seventeen (17) characters for eCheck account tokens.'),
			'bin' => array('type' => 'string', 'length' => '3', 'comment' => 'Bank Identification Number'),
			'tax' => array('type' => 'string', 'length' => '16', 'options' => array('payment', 'fee')),
			'routingNum' => array('type' => 'string', 'length' => '9', 'comment' => 'The routingNum element is a required child of the echeck, originalAccountInfo, and newAccountInfo elements defining the routing number of the Echeck account.'),
			'currencyCode' => array('type' => 'string', 'length' => '3', 'options' => array('AUD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'NOK', 'NZD', 'SEK', 'SGD', 'USD')),
			'name' => array('type' => 'string', 'length' => '100'),
			'firstName' => array('type' => 'string', 'length' => '25'),
			'middleInitial' => array('type' => 'string', 'length' => '1'),
			'lastName' => array('type' => 'string', 'length' => '25'),
			'companyName' => array('type' => 'string', 'length' => '40'),
			'addressLine1' => array('type' => 'string', 'length' => '35'),
			'addressLine2' => array('type' => 'string', 'length' => '35'),
			'addressLine3' => array('type' => 'string', 'length' => '35'),
			'city' => array('type' => 'string', 'length' => '35'),
			'state' => array('type' => 'string', 'length' => '2'),
			'zip' => array('type' => 'string', 'length' => '20'),
			'country' => array('type' => 'string', 'length' => '3'),
			'email' => array('type' => 'string', 'length' => '100'),
			'phone' => array('type' => 'string', 'length' => '20'),
			'customBilling.phone' => array('type' => 'integer', 'length' => '13'),
			'customBilling.url' => array('type' => 'string', 'length' => '13', 'comment' => 'A-Z, a-z, 0-9, /, \, -, ., or _.'),
			'bypassVelocityCheck' => array('type' => 'bool'),
			'affiliate' => array('type' => 'string', 'length' => '25', 'comment' => 'use it to track transactions associated with various affiliate organizations'),
			'campaign' => array('type' => 'string', 'length' => '25', 'comment' => 'use it to track transactions associated with various marketing campaigns'),
			'merchantGroupingId' => array('type' => 'string', 'length' => '25', 'comment' => 'use it to track transactions based upon this user defined parameter'),
			'employerName' => array('type' => 'string', 'length' => '20'),
			'customerWorkTelephone' => array('type' => 'string', 'length' => '20'),
			'customerType' => array('type' => 'string', 'length' => '20'),
			),
		// common attributes
		'attrib' => array(
			'id' => null,
			'reportGroup' => null,
			),
		);

	/**
	* Standard variables to be passed around with a request
	* @param array $requestVars
	*/
	public $requestVars = array('type', 'status', 'response', 'message', 'transaction_id', 'litleToken', 'errors', 'data', 'request_raw', 'response_array', 'response_raw', 'url');
	/**
	* Setup & establish HttpSocket.
	* @param config an array of configuratives to be passed to the constructor to overwrite the default
	*/
	public function __construct($config=array()) {
		parent::__construct($config);
		if (!class_exists('LitleUtil')) {
			App::import('Lib', 'Litle.LitleUtil');
		}
	}
	/**
	* Overwrite of the save method
	* All work is really done by
	* beforeSave() preps the data
	* parent::save() --> LitleSource does the API request
	* afterSave() parses the data
	* logRequest() (optional) logs to
	*/
	function save($data) {
		$return = parent::save($data);
		$this->logRequest();
		return $return;
	}
	/**
	* beforeSave clears out $this->lastRequest
	*/
	function beforeSave($options=array()) {
		$this->lastRequest = $this->errors = array();
		return parent::beforeSave($options);
	}
	/**
	* afterSave parses results and verifies status for all transactions
	* assumes $this->lastRequest exists and has the details for the LitleSource->__request()
	* @param mixed $created
	* @return bool
	*/
	function afterSave($created=null) {
		parent::afterSave($created);
		if (empty($this->lastRequest)) {
			$this->lastRequest = array('status' => 'error', 'errors' => array("Unable to access {$this->Alias}->lastRequest"));
			return false;
		}
		extract($this->lastRequest);
		if (!isset($errors)) {
			$errors = array();
		} elseif (!is_array($errors)) {
			$errors = explode(',', $errors);
		}
		if (empty($response_array) || !is_array($response_array)) {
			$errors[] = 'Missing Response Array';
			$response_array = array('response' => 0, 'message' => 'Missing Response Array');
		}
		if (!empty($errors)) {
			$status = "error";
		}
		$this->lastRequest = compact($this->requestVars);
		return true;
	}
	/**
	* Logs the last request, if config includes a model to log with
	* This method is called within the afterSave() at the end
	* Can have method "logLitleRequest" as in: ModelName->logLitleRequest($lastRequest);
	* Can have method "logRequest" as in: ModelName->logRequest($lastRequest);
	* Can have method "save" as in: ModelName->save($lastRequest);
	* @return mixed $saved or false
	*/
	function logRequest() {
		if (empty($this->lastRequest)) {
			return false;
		}
		$logModel = LitleUtil::getConfig('logModel');
		if (!empty($logModel) && is_string($logModel)) {
			App::import('Model', $logModel);
			$LogModel =  ClassRegistry::init($logModel);
			if (method_exists($LogModel, 'logLitleRequest')) {
				return $LogModel->logLitleRequest($this->lastRequest);
			} elseif (method_exists($LogModel, 'logRequest')) {
				return $LogModel->logRequest($this->lastRequest);
			} elseif (method_exists($LogModel, 'save')) {
				return $LogModel->save($this->lastRequest);
			}
		}
		return false;
	}
	/**
	* Re-arrange fields which coule be passed in a single-dim array
	* need to extend this function?
	* you can use the config => array("field_map" => array($new_key => $old_keys))
	* perhaps write your own version before you call the plugin
	* @param array $data
	* @param string $style
	* @return array $data
	*/
	function translateFields($data, $style=null) {
		if (isset($data[$this->alias])) {
			$data = array_merge($data, $data[$this->alias]);
			unset($data[$this->alias]);
		}
		// translate based on field_map configuration
		$field_map = LitleUtil::getConfig('field_map');
		if (is_array($field_map)) {
			foreach ( $field_map as $new_key => $old_keys ) {
				if (is_string($old_keys)) {
					$old_keys = explode(',', $old_keys);
				}
				foreach ( $old_keys as $old_key ) {
					if (!array_key_exists($new_key, $data) && array_key_exists($old_key, $data)) {
						$data[$new_key] = $data[$old_key];
						unset($data[$old_key]);
					}
				}
			}
		}
		// translate nested keys
		$nested = array();
		foreach ( $data as $key => $val ) {
			if (strpos($key, '.')!==false) {
				$nested = set::insert($nested, $key, $val);
				unset($data[$key]);
			}
		}
		$data = Set::pushDiff($data, $nested);
		return $data;
	}
	/**
	* You can assign default values for ANY API interaction (after the translation)
	* @param array $data
	* @param string $style
	* @return array $data
	*/
	function assignDefaults($data, $style=null) {
		if (isset($data[$this->alias])) {
			$data = array_merge($data, $data[$this->alias]);
			unset($data[$this->alias]);
		}
		$defaults = LitleUtil::getConfig('defaults');
		if (isset($defaults[$style]) && is_array($defaults[$style]) && !empty($defaults[$style])) {
			$data = set::merge($defaults[$style], $data);
		}
		if (isset($this->_schema)) {
			foreach ( array_keys($this->_schema) as $key ) {
				if ((!isset($data[$key]) || empty($data[$key])) && array_key_exists($key, $defaults) && !is_array($defaults[$key])) {
					$data[$key] = $defaults[$key];
				}
			}
		}
		if (LitleUtil::getConfig('auto_orderId_if_missing') && (!isset($data['orderId']) || empty($data['orderId']))) {
			$data['orderId'] = time();
		}
		// the transaction_id is used to determine duplicate transactions
		if (LitleUtil::getConfig('auto_id_if_missing') && (!isset($data['id']) || empty($data['id']))) {
			if (LitleUtil::getConfig('duplicate_window_in_seconds')) {
				$idSuffix = ceil(time() / max(intval(LitleUtil::getConfig('duplicate_window_in_seconds')), 1));
			} else {
				$idSuffix = time();
			}
			if (isset($data['litleTxnId'])) {
				$data['id'] = $this->num($data['orderId']).'-'.$idSuffix;
			} elseif (isset($data['orderId'])) {
				$data['id'] = $this->num($data['orderId']).'-'.$idSuffix;
			}
		}
		return $data;
	}
	/**
	* Litle requires the XML data to be in a specific order :(
	* As such, we need to make sure every node in our array is correctly ordered
	* NOTE: this ends in a set::filter() which will remove all empty values (except for 0)
	* @param array $data
	* @param string $templateKey
	* @param array $rootAttributes optional
	* @return array $data
	*/
	function finalizeFields($data, $templateKey=null, $rootAttributes=array()) {
		// include only allowed fields (must be defined in the schema)
		$stripped_data_keys = array_diff(array_keys($data), array_keys($this->_schema));
		$data = array_intersect_key($data, $this->_schema);
		// clean commonly mistaken values
		$data = $this->cleanValues($data);
		// reorder based on templates (also strips empties)
		$data = $this->orderFields($data, $templateKey);
		if (!empty($rootAttributes)) {
			if (isset($rootAttributes['id'])) {
				$rootAttributes['id'] = substr($rootAttributes['id'], -25);
			}
			$data = array_diff_key($data, $rootAttributes);
			$data['root'] = $templateKey.'|'.json_encode($rootAttributes);
		} else {
			$data['root'] = $templateKey;
		}
		return $data;
	}
	/**
	* Litle requires the XML data to be in a specific order :(
	* As such, we need to make sure every node in our array is correctly ordered
	* NOTE: this ends in a set::filter() which will remove all empty values (except for 0)
	* @param array $data
	* @param string $templateKey
	* @return array $data
	*/
	function orderFields($data, $templateKey=null) {
		// act on this node
		if (array_key_exists($templateKey, $this->templates) && is_array($data)) {
			$data = set::merge($this->templates[$templateKey], $data);
		}
		// recursivly act on all child nodes
		foreach ( $data as $key => $val ) {
			if (array_key_exists($key, $this->templates) && is_array($val)) {
				$data[$key] = $this->orderFields($val, $key);
			}
		}
		// clear all null elements (recursivly, so do this after recusion)
		$data = set::filter($data);
		if (!is_array($data)) {
			$data = array();
		}
		// shuffle attrib to the end
		if (isset($data['attrib'])) {
			$attrib = $data['attrib'];
			unset($data['attrib']);
			$data['attrib'] = $attrib;
		}
		return $data;
	}
	/**
	* Just to be safe, we are going to clean values which have known, easy to fix, limitations
	* @param mixed $data
	* @return mixed $data
	*/
	function cleanValues($data) {
		if (is_array($data)) {
			foreach ( $data as $key => $val ) {
				if (is_array($val)) {
					$data[$key] = $this->cleanValues($val);
				} else {
					$schema = array();
					if (array_key_exists($key, $this->templates['schema'])) {
						$schema = $this->templates['schema'][$key];
					}
					if (isset($this->_schema) && array_key_exists($key, $this->_schema) && is_array($this->_schema[$key])) {
						$schema = array_merge($schema, $this->_schema[$key]);
					}
					if (array_key_exists('type', $schema) && $schema['type'] == 'integer') {
						$data[$key] = preg_replace('#[^0-9]#', '', $data[$key]);
					} elseif (array_key_exists('type', $schema) && $schema['type'] == 'bool') {
						$data[$key] = (empty($data[$key]) || strtolower($data[$key])=='false' || strtolower($data[$key])=='no' ? 'false' : 'true');
					}
					if (array_key_exists('length', $schema)) {
						$data[$key] = substr($data[$key], 0, $schema['length']);
					}
				}
			}
		}
		return $data;
	}
	/**
	* Access DataSource->prepareApiData($data)
	* @param array $data
	* @return string $xml
	*/
	public function prepareApiData($data = array()) {
		$db =  ConnectionManager::getDataSource($this->useDbConfig);
		return $db->prepareApiData($data);
	}
	/**
	* Access DataSource->parseResponse($data)
	* @param string $xml
	* @return array $response
	*/
	public function parseResponse($xml = null) {
		$db =  ConnectionManager::getDataSource($this->useDbConfig);
		return $db->parseResponse($xml);
	}
	/**
	* Overwrite of the exists() function
	* means everything is a create() / new
	*/
	function exists() {
		return false;
	}
	/**
	* Overwrite of the query() function
	* error handling
	*/
	function query() {
		die("Sorry, bad method call on {$this->alias}");
	}
	/**
	* Helper shortcut for commonly used number_format() call
	* @param mixed $number
	* @return string $formatted_number
	*/
	function num($number) {
		if (is_string($number) && is_numeric($number)) {
			if (strpos('.', $number)!==false) {
				return number_format(floatval($number), 0, '.', '');
			} else {
				return intval($number);
			}
		}
		return $number;
	}
	/**
	* Order Sources Values
	* Parent Elements: authorization, credit, captureGivenAuth, echeckCredit, echeckSale, echeckVerification forceCapture, sale
	* @var array
	*/
	public $orderSources = array(
		'3dsAuthenticated' => 'The transaction qualified as CPS/e-Commerce Preferred as an authenticated purchase. Use this value only if you authenticated the cardholder.',
		'3dsAttempted' => 'The transaction qualified as CPS/e-Commerce Preferred as an attempted authentication. Use this value only if you attempted to authenticate the cardholder and either the Issuer or cardholder is not participating in Verified by Visa.',
		'ecommerce' => 'The transaction is an Internet or electronic commerce transaction.',
		'installment' => 'The transaction in an installment payment.',
		'mailorder' => 'The transaction is for a single mail order transaction.',
		'recurring' => 'The transaction is a recurring transaction.',
		'retail' => 'The transaction is a Swiped or Keyed Entered retail purchase transaction.',
		'telephone' => 'The transaction is for a single telephone order.',
		);
}
?>