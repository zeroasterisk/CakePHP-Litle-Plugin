<?php

class LitleAppModel extends AppModel {
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
		$this->useDbConfig = 'litle';
		ConnectionManager::create($this->useDbConfig);
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$this->config = $db->config();
		// initialize extras: transaction log model
		if (!empty($this->config['logModel'])) {
			if (App::import('model', $this->config['logModel'])) {
				$this->logModel = ClassRegistry::init(array_pop(explode('.', $this->config['logModel'])));
				if (isset($this->config['logModel.useTable']) && $this->config['logModel.useTable']!==null) {
					$this->logModel->useTable = $this->config['logModel.useTable'];
				}
			}
		}
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
	*
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
		return $data;
	}
	
	
	
	
	/**
	*
	*
	*/
	public function __prepareDataForPost($data = null, $root='litleRequest', $rootAttr=array()) {
		if (empty($data)) {
			return false;
		}
		$func = __function__;
		
		
		
		
		// creating object of SimpleXMLElement
		$Xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$root}></{$root}>");
		// function call to convert array to xml
		$this->array_to_xml($data, $Xml);
		//saving generated xml file
		$xml = $Xml->asXML();
		print_r(compact('func', 'data', 'xml'));
		die();
		
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
}

?>