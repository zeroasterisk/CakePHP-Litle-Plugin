<?php

App::import('Core', 'HttpSocket');

class LitleSource extends DataSource {
	/**
	* The description of this data source
	*
	* @var string
	*/
	public $description = 'Litle.net DataSource';
	/**
	* Default configuration
	* Overwritten by anything in LITLE_CONFIG::$config
	* @var array
	*/
	public $_baseConfig = array(
		"server" => 'test',
		"test_request" => false,
		"login" => NULL,
		"key" => NULL,
		"email" => false,
		"duplicate_window" => "120",
		"payment_method" => "CC",
		"default_type" => "AUTH_CAPTURE",
		'delimit_response' => true,
		"response_delimiter" => "|",
		"response_encapsulator" => "",
		'api_version' => '3.1',
		'payment_method' => 'CC',
		'relay_response' => false
		);

	/**
	* Translation for Litle POST data keys from default config keys
	* Overwritten by anything in LITLE_CONFIG::$translation
	* @var array $bad => $good
	*/
	public $_translation = array(
		'card_number' => 'card_num',
		'expiration' => 'exp_date',
		'default_type' => 'type',
		'transaction_id' => 'trans_id',
		'key' => 'tran_key',
		'delimit_response' => 'delim_data',
		'response_delimiter' => 'delim_char',
		'response_encapsulator' => 'encap_char',
		'api_version' => 'version',
		'payment_method' => 'method',
		'email_customer' => 'email',
		'customer_email' => 'email',
		'customer_id' => 'cust_id',
		'cust_ip' => 'customer_ip',
		'billing_first_name' => 'first_name',
		'billing_last_name' => 'last_name',
		'billing_company' => 'company',
		'billing_street' => 'street',
		'billing_city' => 'city',
		'billing_state' => 'state',
		'billing_zip' => 'zip',
		'billing_country' => 'country',
		'billing_phone' => 'phone',
		'billing_fax' => 'fax',
		'billing_email' => 'email',
		);
	/**
	*
	* These fields are often defined in the data set, but don't need to be sent to Litle
	*
	* @var array
	*/
	public $_fieldsToIgnore = array(
		'LitlePluginVersion', 	'datasource', 	'logModel',
		'test_account', 	'test_cvv', 	'test_expire', 	'test_name', 	'test_address', 	'test_zip',
		);
	/**
	* HttpSocket object
	* @var object
	*/
	public $Http;
	/**
	* Set configuration and establish HttpSocket with appropriate test/production url.
	* @param config an array of configuratives to be passed to the constructor to overwrite the default
	*/
	public function __construct($config) {
		parent::__construct($config);
		$this->Http = new HttpSocket();
	}
	/**
	* Not currently possible to read data. Method not implemented.
	*/
	public function read(&$Model, $queryData = array()) {
		return false;
	}
	/**
	* Create a new transaction
	*/
	public function create(&$Model, $fields = array(), $values = array()) {
		$data = array_combine($fields, $values);
		$data = Set::merge($this->config, $data);
		$result = $this->__request($Model, $data);
		return $result;
	}
	/**
	* Capture a previously authorized transaction
	*/
	public function update(&$Model, $fields = null, $values = null) {
		$data = array_combine($fields, $values);
		if ((float)$data['amount'] >= 0) {
			$data = Set::merge($data, array('default_type' => 'PRIOR_AUTH_CAPTURE'));
		} else {
			// if a negative value is passed, assuming refund
			$data = Set::merge($data, array('default_type' => 'CREDIT'));
			// Litle assumes we want to send a positive number for a credit transcation (how much to credit)
			$data['amount'] = abs((float) $data['amount']);
		}
		$data = Set::merge($this->config, $data);
		return $this->__request($Model, $data);
	}
	/**
	* Void a transaction
	*/
	public function delete(&$Model, $id = null) {
		if (empty($id)) {
			$id = $Model->id;
		}
		if (is_array($id) && isset($id[$Model->alias][$Model->primaryKey])) {
			$id = $id[$Model->alias][$Model->primaryKey];
		} elseif (is_array($id) && isset($id[$Model->alias.'.'.$Model->primaryKey])) {
			$id = $id[$Model->alias.'.'.$Model->primaryKey];
		} elseif (is_array($id) && isset($id[$Model->primaryKey])) {
			$id = $id[$Model->primaryKey];
		} else {
			$id = current($id[$Model->primaryKey]);
		}
		$data = array(
			'transaction_id' => $id,
			'default_type' => 'VOID'
			);
		$data = Set::merge($this->config, $data);
		return $this->__request($Model, $data);
	}
	/**
	* Unsupported methods other CakePHP model and related classes require.
	*/
	public function listSources() {}
	/**
	* Translate keys to a value Litle.net expects in posted data, as well as encapsulating where relevant. Returns false
	* if no data is passed, otherwise array of translated data.
	* @param array $data
	* @return mixed
	*/
	private function __prepareDataForPost($data = null) {
		if (empty($data)) {
			return false;
		}
		$encapsulators = array('line_items','taxes','freight','duty');
		$return = array();
		$data = array_diff_key($data, array_flip($this->_fieldsToIgnore));
		foreach ($data as $key => $value) {
			if (empty($value)) {
				continue;
			}
			if (in_array($key, $encapsulators)) {
				if (is_array($value)) {
					$value = implode('<|>', $value);
				}
			}
			// translate key
			if (array_key_exists($key, $this->_translation)) {
				$key = $this->_translation[$key];
			}
			// cleanup key
			if (substr($key, 0, 2)=='x_') {
				$key = substr($key, 2);
			}
			$return["x_{$key}"] = $value;
		}
		return $return;
	}
	
	/**
	* Parse the response data from a post to authorize.net
	* @param object $Model
	* @param string $response
	* @param array $input
	* @param string $url
	* @return array
	*/
	private function __parseResponse(&$Model, $response, $input=null, $url=null) {
		$status = 'unknown';
		$error = $transaction_id = null;
		die('WIP __parseResponse (should prob interact w/ model, since various responses will come in');
		return compact('status', 'transaction_id', 'error', 'response', 'response_reason', 'avs_response', 'input', 'data', 'url', 'type');
	}

	/**
	*
	* Post data to authorize.net. Returns false if there is an error,
	* or an array of the parsed response from authorize.net if valid
	*
	* @param array $request
	* @return mixed
	*/
	private function __request(&$Model, $data) {
		if (empty($data)) {
			return false;
		}
		if (!empty($data['server'])) {
			$server = $data['server'];
			unset($data['server']);
		} else {
			$server = $this->config['server'];
		}
		$url = $this->config['url'];
		$data = $this->__prepareDataForPost($data);
		$this->Http->reset();
		$response = $this->Http->post($url, $data, array(
			'header' => array(
    			'Connection' => 'close',
    			'User-Agent' => 'CakePHP Litle Plugin v.'.$this->config['LitlePluginVersion'],
				)
			));
		
		if ($this->Http->response['status']['code'] != 200) {
			$Model->errors[] = $error = 'LitleSource: Error: Could not connect to authorize.net... bad credentials?';
			trigger_error(__d('adobe_connect', $error, true), E_USER_WARNING);
			return false;
		}
		$Model->response = $return = $this->__parseResponse($Model, $response, $data, $url);
		// log to an array on the model
		if (isset($Model->log) && is_array($Model->log)) {
			$Model->log[] = $return;
		}
		// log to a model (database table), if setup on the model
		if (isset($Model->logModel) && is_object($Model->logModel)) {
			// inject data from this model to the logModel, if set
			// this is a convenient way to pass IDs around, would have to be handled in the logModel 
			if (isset($Model->logModelData)) {
				$Model->logModel->logModelData = $Model->logModelData; 
			}
			$Model->logModel->create(false);
			$Model->logModel->save($return);
		}
		return $return;
	}
}
?>