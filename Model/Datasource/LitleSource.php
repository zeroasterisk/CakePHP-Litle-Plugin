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
* @link https://github.com/zeroasterisk/CakePHP-Litle-Plugin
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*
*/
App::uses('DataSource','Model/Datasource');
App::uses('LitleUtil','Litle.Lib');
App::uses('HttpSocket', 'Network/Http');
App::uses('ArrayToXml', 'Litle.Lib');
App::uses('Xml', 'Utility');

/**
 * LitleException Class
 */
class LitleException extends Exception {
}

class LitleSource extends DataSource {
	/**
	* The description of this data source
	*
	* @var string
	*/
	public $description = 'Litle.net DataSource';
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
	* Setup & establish HttpSocket.
	* @param config an array of configuratives to be passed to the constructor to overwrite the default
	*/
	public function __construct($config=array()) {
		parent::__construct($config);
		if (is_array($config) && !empty($config)) {
			$_config = configure::read('Litle');
			if (is_array($_config) && !empty($_config)) {
				$config = array_merge($_config, $config);
			}
			configure::write('Litle', $config);
		}
		$this->Http = new HttpSocket();
	}
	/**
	* Override of the basic describe() function
	* @param object $model
	* @return array $_schema
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
	public function listSources($data = null) {
		return array('litle_transactions');
	}
	/**
	* Not currently possible to read data. Method not implemented.
	*/
	public function read(Model $Model, $queryData = array(), $recursive = null) {
		return false;
	}
	/**
	* Create a new transaction
	*/
	public function create(Model $Model, $fields = null, $values = null) {
		$data = array_combine($fields, $values);
		return $this->__request($data, $Model);
	}
	/**
	* Capture a previously authorized transaction
	*/
	public function update(Model $Model, $fields = null, $values = null, $conditions = null) {
		$data = array_combine($fields, $values);
		return $this->__request($data, $Model);
	}
	/**
	* Not currently possible to read data. Method not implemented.
	* LitleSale->delete() works fine, through LitleVoid->save()
	*/
	public function delete(Model $Model, $id = null) {
		return false;
	}
	/**
	* Overwrite of the query() function - used as error handling
	*
	* @param string $sql
	* @return boolean
	*/
	public function query($sql = null) {
		throw new OutOfBoundsException("LitleSource::{$sql} - Sorry, bad method call");
	}
	/**
	* Translate keys to a value Litle.net expects in posted data, as well as encapsulating where relevant. Returns false
	* if no data is passed, otherwise array of translated data.
	* @param mixed $data
	* @return string $xml
	*/
	public function prepareApiData($data = null, Model $Model=null) {
		if (empty($data)) {
			return false;
		}
		if (is_string($data)) {
			// assume it's a XML body already
			return $data;
		}
		// litleOnlineRequestKey wrapper
		if (array_key_exists('litleOnlineRequest', $data)) {
			$litleOnlineRequestKey = $data['litleOnlineRequest'];
			unset($data['litleOnlineRequest']);
		} else {
			$attrib = array(
				'version' => LitleUtil::getConfig('version'),
				'url_xmlns' => LitleUtil::getConfig('url_xmlns'),
				'merchantId' => LitleUtil::getConfig('merchantId'),
				);
			$litleOnlineRequestKey = 'litleOnlineRequest|'.json_encode($attrib);
		}
		// authentication
		if (array_key_exists('authentication', $data)) {
			$authentication = $data['authentication'];
			unset($data['authentication']);
		} else {
			$authentication = array('authentication' => array(
				'user' => LitleUtil::getConfig('user'),
				'password' => LitleUtil::getConfig('password'),
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
		$xml = str_replace('url_xmlns', 'xmlns', $xml); // special replacement
		$xml = str_replace('><', ">\n<", $xml); // formatting with linebreaks
		#$xml = preg_replace('#(<[^/>]*>)(<[^/>]*>)#', "\$1\n\$2", $xml);
		#$xml = preg_replace('#(</[a-zA-Z0-9]*>)(</[a-zA-Z0-9]*>)#', "\$1\n\$2", $xml);
		$function = __function__;
		$this->log[] = compact('func', 'data', 'requestArray', 'xml');
		/* */
		return $xml;
	}
	/**
	* Parse the response data from a post
	*
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
			try {
				$response_array = Xml::toArray(Xml::build($response_raw));
			} catch (XmlException $e) {
				throw new LitleException($e->getMessage());
			}
		} elseif (is_array($response_array)) {
			$response_array = $response;
			if (array_key_exists('response_raw', $response_array)) {
				$response_raw = $response_array['response_raw'];
				unset($response_array['response_raw']);
			}
		} else {
			throw new LitleException('Response is in invalid format');
		}
		// boil down to just the response we are interested in
		if (array_key_exists('litleOnlineResponse', $response_array)) {
			$response_array = $response_array['litleOnlineResponse'];
		} elseif (array_key_exists('LitleOnlineResponse', $response_array)) {
			$response_array = $response_array['LitleOnlineResponse'];
		}
		// re-format, remove the '@' prefixes
		$response_array = $this->responseCleanAttr($response_array);
		// verify response_array
		if (!is_array($response_array)) {
			throw new LitleException('Response is not formatted as an Array');
		}
		if (!array_key_exists('response', $response_array)) {
			throw new LitleException('Response.response missing (request xml validity)');
		}
		if (array_key_exists('message', $response_array) && $response_array['message']!='Valid Format') {
			$errors[] = $response_array['message'];
		} elseif (!array_key_exists('response', $response_array) || intval($response_array['response'])!==0) {
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
	 * Response arrays may have fields/keys with a '@' prefix -- remove those
	 *
	 * @param array $array
	 * @return array $array
	 */
	public function responseCleanAttr($array) {
		if (!is_array($array)) {
			return $array;
		}
		foreach (array_keys($array) as $key) {
			if (is_array($array[$key])) {
				$array[$key] = $this->responseCleanAttr($array[$key]);
			}
			if (substr(trim($key), 0, 1) == '@') {
				$array[str_replace('@', '', trim($key))] = $array[$key];
				unset($array[$key]);
			}
		}
		return $array;
	}

	/**
	 *
	 * Post data to litle. Returns false if there is an error,
	 * or an array of the parsed response from litle if valid
	 *
	 * @param array $request
	 * @param object $Model optional
	 * @return mixed $response
	 */
	public function __request($data, Model $Model=null) {
		$errors = array();
		if (empty($data)) {
			$errors[] = "Missing input data";
			$request_raw = '';
		} elseif (is_array($data)) {
			$request_raw = $this->prepareApiData($data, $Model);
		} elseif (is_string($data)) {
			$request_raw = $data;
		} else {
			$errors[] = "Unknown input data type";
			$request_raw = '';
		}
		if (empty($errors)) {
			$this->Http->reset();
			$url = LitleUtil::getConfig('url');
			$requestOptions = array(
				'header' => array(
					'Connection' => 'close',
					'User-Agent' => 'CakePHP Litle Plugin v.'.LitleUtil::getConfig('version'),
				)
			);
			$response = $this->Http->post($url, $request_raw, $requestOptions);
			$response_raw = $response->body;
			if ($this->Http->response['status']['code'] != 200) {
				$errors[] = sprintf('LitleSource: Error: Could not connect to litle... [code=%s, url=%s]',
					$this->Http->response['status']['code'],
					$url
				);
			}
		}
		if (empty($errors)) {
			$response = $this->parseResponse($response_raw);
			extract($response);
		}
		// look for special values
		$transaction_id = $response_array['transaction_id'] = $this->array_find("litleTxnId", $response_array);
		$litleToken = $response_array['litleToken'] = $this->array_find("litleToken", $response_array);
		if (is_object($Model)) {
			$type = $response_array['type'] = str_replace('litle', '', strtolower($Model->alias));
		} else {
			$type = $response_array['type'] = "unkown";
		}
		// compact response array
		$return = compact('type', 'status', 'transaction_id', 'litleToken', 'errors', 'data', 'request_raw', 'response_array', 'response_raw', 'url');
		$this->lastRequest = $return;
		// assign to model if set
		if (is_object($Model)) {
			$Model->lastRequest = $return;
			// log to an array on the model
			if (isset($Model->log) && is_array($Model->log)) {
				$Model->log[] = $return;
			}
		}
		return $return;
	}
	/**
	 * Recursivly look through an array to find a specific key
	 * @param string $needle key to find in the array
	 * @param array $haystack array to search through
	 * @return mixed $output
	 */
	function array_find($needle=null, $haystack=null) {
		if (array_key_exists($needle, $haystack)) {
			return $haystack[$needle];
		}
		foreach ( $haystack as $value ) {
			if (is_array($value)) {
				$found = $this->array_find($needle, $value);
				if ($found!==null) {
					return $found;
				}
			}
		}
		return null;
	}
}
?>
