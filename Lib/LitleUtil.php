<?php
/**
* Helper class to preform some basic tasks.
*
*/
class LitleUtil extends Object {
	/**
	* Litle configurations stored in
	* app/config/litle.php
	* @var array
	*/
	static public $config = array();
	/**
	* Base config
	* @var array
	*/
	static public $_baseConfig = array(
		'plugin_version' => '1.0',
		'plugin_description' => 'CakePHP Litle Bank API Plugin',
		# Litle API Settings
		'version' => '8.7',
		'url_xmlns' => 'http://www.litle.com/schema',
		# Other configuration should be set in app/config/litle.php
		'url' => NULL,
		'user' => NULL,
		'password' => NULL,
		);
	/**
	* Testing getting a configuration option.
	* @param string $key to search for
	* @return mixed $result of configuration key.
	* @access public
	*/
	static public function getConfig($key){
		if (isset(self::$config[$key])) {
			return self::$config[$key];
		}
		//try existing configure setting
		if (self::$config[$key] = Configure::read("Litle.$key")) {
			return self::$config[$key];
		}
		//try load configuration file and try again.
		$loaded = Configure::load('litle');
		$loaded_config = Configure::read('Litle');
		if (!is_array($loaded_config)) {
			$loaded_config = array();
		}
		self::$config = array_merge(self::$_baseConfig, $loaded_config);
		if (isset(self::$config[$key])) {
			return self::$config[$key];
		}
		if (self::$config[$key] = Configure::read("Litle.$key")) {
			return self::$config[$key];
		}
		unset(self::$config[$key]);
		return null;
	}
	/**
	* Set a configuration option... (locally only, doesn't change config)
	* @param string $key
	* @param mixed $value
	* @return bool
	* @access public
	*/
	static public function setConfig($key = null, $value = null) {
		if (strpos($key, '.')!==false) {
			self::$config = Set::insert(self::$config, $key, $value);
		}
		self::$config[$key] = $value;
		return Configure::write("Litle.$key", $value);
	}
}
?>
