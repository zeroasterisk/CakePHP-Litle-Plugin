<?php

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
	public $config = array();
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
		'attrib' => array(
			'id' => null,
			'reportGroup' => null,
			),
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
			// attrib
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
		);

	/**
	* Standard variables to be passed around with a request
	* @param array $requestVars
	*/
	public $requestVars = array('type', 'status', 'response', 'message', 'transaction_id', 'litleToken', 'errors', 'data', 'request_raw', 'response_array', 'response_raw', 'url');
	/**
	* Updates config from: app/config/litle_config.php
	* Sets up $this->logModel
	* @param mixed $id
	* @param string $table
	* @param mixed $ds
	*/
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		ConnectionManager::create($this->useDbConfig, array('setup' => $this->name));
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		if (!method_exists($db, 'config')) {
			$this->useDbConfig = 'litle';
			ConnectionManager::create($this->useDbConfig);
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
		}
		if (!method_exists($db, 'config')) {
			$paths = array(
				APP.'config'.DS.'litle_config.php',
				APP.'config'.DS.'litle.php',
				'config'.DS.'litle_config.php',
				'config'.DS.'litle.php',
				);
			foreach ( $paths as $path ) {
				if (!class_exists('LITLE_CONFIG')) {
					App::import(array('type' => 'File', 'name' => 'Litle.LITLE_CONFIG', 'file' => $path));
				}
			}
			$config = array();
			if (class_exists('LITLE_CONFIG')) {
				$LITLE_CONFIG = new LITLE_CONFIG();
				if (isset($LITLE_CONFIG->config)) {
					$config = set::merge($config, $LITLE_CONFIG->config);
				}
			}
		}
		if (!method_exists($db, 'config')) {
			$_this =& ConnectionManager::getInstance();
			print_r($_this->config);
			print_r($_this->config);
			print_r(array_keys($_this->_dataSources));
			print_r(array_keys($_this->_connectionsEnum));
			print_r(array(
				'LITLE_CONFIG class exists' => class_exists('LITLE_CONFIG'),
				'LitleSource class exists' => class_exists('LitleSource'),
				));
			die("Error: can not access datasource->config() from within LitleAppModel");
		}
		$this->config = $db->config();
		return true;
	}
	/**
    * Simple function to return the $config array
    * @param array $config if set, merge with existing array
    * @return array $config
    */
	public function config($config = array()) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		if (!empty($config) && is_array($config)) {
			$db->config($config);
		}
		return $db->config;
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
		if (isset($this->config['logModel']) && !empty($this->config['logModel'])) {
			App::import('Model', $this->config['logModel']);
			$LogModel =& ClassRegistry::init($this->config['logModel']);
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
		$config = $this->config;
		//echo dumpthis($config);
		// translate based on field_map configuration
		if (isset($config['field_map']) && is_array($config['field_map']) && !empty($config['field_map'])) {
			foreach ( $config['field_map'] as $new_key => $old_keys ) {
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
		$config = $this->config;
		if (isset($config['defaults'][$style]) && is_array($config['defaults'][$style]) && !empty($config['defaults'][$style])) {
			$data = set::merge($config['defaults'][$style], $data);
		}
		if (isset($this->_schema)) {
			foreach ( array_keys($this->_schema) as $key ) {
				if ((!isset($data[$key]) || empty($data[$key])) && array_key_exists($key, $config['defaults']) && !is_array($config['defaults'][$key])) {
					$data[$key] = $config['defaults'][$key];
				}
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
				if (in_array($key, array('expDate', 'amount', 'number'))) {
					$data[$key] = preg_replace('#[^0-9]#', '', $val);
				} elseif (is_array($val)) {
					$data[$key] = $this->cleanValues($val);
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
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db->prepareApiData($data);
	}
	/**
	* Access DataSource->parseResponse($data)
	* @param string $xml
	* @return array $response
	*/
	public function parseResponse($xml = null) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
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