<?php
/**
* Plugin datasource for "Litle" API.
*
* The datasource takes care of 
*	converting array parameters into XML
*	doing the API request
*	converting response XML into an array
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link http://zeroasterisk.com
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*
*/
if (!class_exists('ArrayToXml')) {
	App::import('Lib', 'Litle.ArrayToXml');
}
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
		"url" => NULL,
		"user" => NULL,
		"password" => NULL,
		'version' => '8.7',
		'url_xmlns' => 'http://www.litle.com/schema',
		);
	/**
	* These fields are often defined in the data set, but don't need to be sent to Litle
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
	* Signed request string to pass to Amazon
	* @var string
	*/
	protected $_request = null;
	/**
	* Request Logs
	* @var array
	*/
	private $__requestLog = array();
	/**
	* Set configuration and establish HttpSocket with appropriate test/production url.
	* @param config an array of configuratives to be passed to the constructor to overwrite the default
	*/
	public function __construct($config=array()) {
		parent::__construct($config);
		// Try an import the .../config/litle_config.php file and merge
		// any default and datasource specific config with the defaults above
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
		// Add any config from Configure class that you might have added at any point before the model is instantiated.
		if (($configureConfig = Configure::read('Litle.config')) != false) {
			$config = set::merge($config, $configureConfig);
		}
		$config = $this->config($config);
		$this->Http = new HttpSocket();
	}
	/**
	*
	*
	*/
	public function describe($model) {
		if (isset($model->_schema)) {
			return $model->_schema;
		} elseif (isset($model->alias) && isset($this->_schema) && isset($this->_schema[$model->alias])) {
			return $this->_schema[$model->alias];
		}
		return array();
	}
	/**
	* Unsupported methods other CakePHP model and related classes require.
	*/
	public function listSources() {
		return array('litle_transactions');
	}
	/**
    * Simple function to return the $config array
    * @param array $config if set, merge with existing array
    * @param bool $verify
    * @return array $config
    */
	public function config($config = array(), $verify=true) {
		if (!isset($this->config) || empty($this->config) || !is_array($this->config)) {
			$this->config = $this->_baseConfig;
		}
		if (is_array($config) && !empty($config)) {
			$config = set::merge($this->config, $config);
		} else {
			$config = $this->config;
		}
		if (!isset($config['version']) || empty($config['version'])) {
			$config = set::merge($this->_baseConfig, $config);
		}
		if ($verify) {
			$errors = array();
			if (!isset($config['url']) || empty($config['url'])) {
				$errors[] = "Missing or incorrect url";
			}
			if (!isset($config['user']) || empty($config['user'])) {
				$errors[] = "Missing or incorrect user";
			}
			if (!isset($config['password']) || empty($config['password'])) {
				$errors[] = "Missing or incorrect password";
			}
			if (!isset($config['merchantId']) || empty($config['merchantId'])) {
				$errors[] = "Missing or incorrect merchantId";
			}
			if (!empty($errors)) {
				die("Sorry, Litle Configuration is incorrect.<br>\n".implode("<br>\n", $errors));
			}
		}
		$this->config = $config;
		return $config;
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
		return $this->__request($data, $Model);
	}
	/**
	* Capture a previously authorized transaction
	*/
	public function update(&$Model, $fields = null, $values = null) {
		$data = array_combine($fields, $values);
		return $this->__request($data, $Model);
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
		# TODO: set this default "helper" data correctly (or disable this method)
		$data = array(
			'transaction_id' => $id,
			'default_type' => 'VOID'
			);
		$data = Set::merge($this->config, $data);
		return $this->__request($data, $Model);
	}
	/**
	* Translate keys to a value Litle.net expects in posted data, as well as encapsulating where relevant. Returns false
	* if no data is passed, otherwise array of translated data.
	* @param mixed $data
	* @return string $xml
	*/
	public function prepareApiData($data = null, &$Model=null) {
		if (empty($data)) {
			return false;
		}
		if (is_string($data)) {
			// assume it's a XML body already
			return $data;
		}
		$config = $this->config();
		// litleOnlineRequestKey wrapper
		if (array_key_exists('litleOnlineRequest', $data)) {
			$litleOnlineRequestKey = $data['litleOnlineRequest'];
			unset($data['litleOnlineRequest']);
		} else {
			$attrib = array_intersect_key($config, array('version' => 0, 'url_xmlns' => 0, 'merchantId' => 0)); 
			$litleOnlineRequestKey = 'litleOnlineRequest|'.json_encode($attrib);
		}
		// authentication
		if (array_key_exists('authentication', $data)) {
			$authentication = $data['authentication'];
			unset($data['authentication']);
		} else {
			$authentication = array('authentication' => array(
				'user' => $config['user'], 'password' => $config['password']
				));
		}
		// root wrapper
		if (array_key_exists('root', $data)) {
			$root = $data['root'];
			unset($data['root']);
		} else {
			$root = (isset($Model->alias) ? $Model->alias : null); 
		}
		// re-order and nest
		if (is_string($root) && !empty($root)) {
			$data = array($root => $data);
		}
		$requestArray = array($litleOnlineRequestKey => array_merge($authentication, $data));
		$xml = ArrayToXml::build($requestArray);
		$xml = str_replace('url_xmlns', 'xmlns', $xml);
		$function = __function__;
		$this->log[] =compact('func', 'config', /* 'data', */ 'requestArray', 'xml');
		/* */
		return $xml;
	}
	
	/**
	* Parse the response data from a post to authorize.net
	* @param string $response
	* @param object $Model
	* @return array
	*/
	public function parseResponse($response, &$Model=null) {
		$errors = array();
		$transaction_id = null;
		$response_raw = '';
		$response_array = array();
		if (is_string($response)) {
			$response_raw = $response;
			if (!class_exists('Xml')) {
				App::import("Core", "Xml");
			}
			$Xml = new Xml($response);
			$response_array = $Xml->toArray();
		} elseif (is_array($response_array)) {
			$response_array = $response;
			if (array_key_exists('response_raw', $response_array)) {
				$response_raw = $response_array['response_raw'];
				unset($response_array['response_raw']);
			}
		} else {
			$errors[] = 'Response is in invalid format';
		}
		// boil down to just the response we are interested in
		if (array_key_exists('litleOnlineResponse', $response_array)) {
			$response_array = $response_array['litleOnlineResponse'];
		} elseif (array_key_exists('LitleOnlineResponse', $response_array)) {
			$response_array = $response_array['LitleOnlineResponse'];
		}
		// verify response_array
		if (!is_array($response_array)) {
			$errors[] = 'Response is not formatted as an Array';
		} elseif (!array_key_exists('response', $response_array)) {
			$errors[] = 'Response.response missing (request xml validity)';
		}
		if (array_key_exists('message', $response_array) && $response_array['message']!='Valid Format') {
			$errors[] = $response_array['message'];
		} elseif (intval($response_array['response'])!==0) {
			$errors[] = 'Response.response indicates request xml is in-valid, unknown Message';
		}
		if (empty($errors)) {
			$status = 'good';
		} else {
			$status = 'error';
		}
		return compact('status', 'transaction_id', 'errors', 'response_array', 'response_raw');
	}

	/**
	*
	* Post data to authorize.net. Returns false if there is an error,
	* or an array of the parsed response from authorize.net if valid
	*
	* @param array $request
	* @param object $Model optional
	* @return mixed $response
	*/
	public function __request($data, &$Model=null) {
		$data_json = null;
		$errors = array();
		if (empty($data)) {
			$errors[] = "Missing input data";
		} elseif (is_array($data)) {
			$data_json = json_encode($data);
			$data = $this->prepareApiData($data, $Model);
		}
		if (empty($errors)) {
			$this->Http->reset();
			$url = $this->config['url'];
			$response_raw = $this->Http->post($url, $data, array(
				'header' => array(
					'Connection' => 'close',
					'User-Agent' => 'CakePHP Litle Plugin v.'.$this->config['version'],
					)
				));
			if ($this->Http->response['status']['code'] != 200) {
				$errors[] = "LitleSource: Error: Could not connect to authorize.net... bad credentials?";
			}
		}
		if (empty($errors)) {
			$response = $this->parseResponse($response_raw);
			extract($response);
		}
		// compact response array
		$return = compact('status', 'transaction_id', 'errors', 'data_json', 'data', 'response_array', 'response_raw');
		// assign to model if set
		if (is_object($Model)) {
			$Model->lastRequest = $return;
			// log to an array on the model
			if (isset($Model->log) && is_array($Model->log)) {
				$Model->log[] = $return;
			}
			if (method_exists($Model, 'logRequest')) {
				$Model->logRequest($return);
			}
		}
		return $return;
	}
}
?>