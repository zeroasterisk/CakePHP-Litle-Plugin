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
		$this->lastRequest = compact('status', 'transaction_id', 'errors', 'data_json', 'data', 'response_array', 'response_raw');
		return true;
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
		foreach ( $data as $key => $val ) { 
			if (strpos($key, '.')!==false) {
				$keyParts = explode('.', $key);
				$keyPrefix = array_shift($keyParts);
				$keySuffix = implode('.', $keyParts);
				if (!isset($data[$keyPrefix][$keySuffix])) {
					$data[$keyPrefix][$keySuffix] = $data[$key];
				}
				unset($data[$key]);
			}
		}
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
			$ordered_defaults = set::merge($data, $config['defaults'][$style]);
			$data = set::merge($ordered_defaults, set::filter($data));
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
	* Log a request (on a specifed model, defined in configuration)
	* @param array $lastRequest
	* @return mixed $save_response or null
	*/
	function logRequest($lastRequest) {
		if (!isset($this->logModel)) {
			// initialize extras: transaction log model
			if (!empty($this->config['logModel'])) {
				if (App::import('model', $this->config['logModel'])) {
					$this->logModel = ClassRegistry::init(array_pop(explode('.', $this->config['logModel'])));
					if (isset($this->config['logModel.useTable']) && $this->config['logModel.useTable']!==null) {
						$this->logModel->useTable = $this->config['logModel.useTable'];
					}
				}
			}
		}
		if (empty($this->logModel) || !is_object($this->logModel)) {
			return null;
		}
		if (class_exists($this->logModel, 'logRequest')) {
			return $this->logModel->logRequest($this->lastRequest);
		}
		$this->logModel->create(false);
		return $this->logModel->save($this->lastRequest);
	}
	/**
	* Overwrite of the exists() function
	* means everything is a create() / new
	*/
	function exists() {
		return false;
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