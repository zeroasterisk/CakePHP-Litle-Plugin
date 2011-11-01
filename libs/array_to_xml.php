<?php
/**
* ArrayToXml allows two methods for converting an associative array to XML
*
* ArrayToXml::build($array)
* uses CakePHP's XmlHelper to construct, 
* this lets you construct XML segments easier, which you can put together later
* NOTE: this XML will not have a root element, unless the array does 
*
* or if you prefer SimpleXml (which is faster)
* ArrayToXml::simplexml($array, $rootNodeName, $rootNodeAttributes)
* NOTE: this assumes the $array doesn't have a root element
*
* @link https://github.com/zeroasterisk/CakePHP-ArrayToXml-Lib
* @author Alan Blount <alan@zeroasterisk.com>
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*/
class ArrayToXml {
	static $attribKey = 'attrib';
	static $cleanValues = true;
	static $XmlHelper = null;
	
	/**
	* Build a full XML document from associative array
	* this uses CakePHP's Xml
	* very similar to simple() {uses SimpleXml) 
	* @param array of data to create xml document out of
	* @example:
		$data = array(
			'CallSource' => array(
				'Customer' => array(
					'CustomerCode|{"flag":"true"}' => 'sample_code1',
					'CustomerName' => 'Test Clinic',
					'DefaultTarget' => '5057023639'
				),
				'Campaign|{"returnResult":"true"}' => array(
					'SomeTag' => 'value'
				),
				'Username' => 'username',
			'Iteratitions' => array(
				'Iteratition|0' => array(
					'id' => 1234,
				),
				'Iteratition|1' => array(
					'id' => 1235,
				),
				'Iteratition|2' => array(
					'id' => 1236,
				),
			)
		);
	* @param boolean include headers (default false)
	*/
	static function build($array = array(), $headers = false){
		if (!is_object(ArrayToXml::$XmlHelper)) {
			App::import('Core', 'Xml');
			App::import('Helper', 'Xml');
			ArrayToXml::$XmlHelper = new XmlHelper();
		}
		$retval = $headers ? ArrayToXml::$XmlHelper->header() : "";
		foreach($array as $tag => $value){
			$options = null;
			extract(ArrayToXml::parseKey($tag));
			if (is_array($value)) {
				if (array_key_exists(ArrayToXml::$attribKey, $value)) {
					$options = (is_array($options) ? array_merge($value[ArrayToXml::$attribKey]) : $value[ArrayToXml::$attribKey]);
					unset($value[ArrayToXml::$attribKey]);
				}
				$retval .= ArrayToXml::$XmlHelper->elem($tag, $options, ArrayToXml::build($value, false));
			} else {
				$retval .= ArrayToXml::$XmlHelper->elem($tag, $options, ArrayToXml::xml_value($value));
			}
		}
		return $retval;
	}
	/**
	* Build a full XML document from associative array
	* this uses SimpleXml
	* very similar to build() {uses CakePHP's Xml) 
	* @param array $array array of elements to create (required)
	* @param string $rootNode root element name (required)
	* @param array $rootNodeAttrib array of attributes for the root element (optional)
	* @return string $xml
	*/
	static function simplexml($array, $rootNode=null, $rootNodeAttrib=array()) {
		if (empty($rootNode) || !is_string($rootNode)) {
			$rootNode = key($array);
		}
		$XmlObj = new SimpleXMLElement("<?xml version=\"1.0\"?><{$rootNode}></{$rootNode}>");
		ArrayToXml::simple_to_xml($XmlObj, $array);
		return $XmlObj->asXML();
	}
	/**
	* Helper to assign an array to an XML object
	* @param object $Xml
	* @param mixed $data
	* @param string $nodeTag
	* @return bool
	*/
	static function simple_to_xml(&$Xml, $data, $nodeTag=null) {
		if (is_array($data)) {
			foreach($data as $tag => $value) {
				if (is_array($value)) {
					if (!is_numeric($tag)) {
						$options = null;
						extract(ArrayToXml::parseKey($tag));
						$Subnode = $Xml->addChild("$tag");
						if (is_array($options) && !empty($options)) {
							ArrayToXml::xml_attributes($Subnode, $options);
						}
						// look for child attrib values
						if (array_key_exists(ArrayToXml::$attribKey, $value)) {
							ArrayToXml::xml_attributes($Subnode, $value[ArrayToXml::$attribKey]);
							unset($value[ArrayToXml::$attribKey]);
						}
						// recurse
						ArrayToXml::simple_to_xml($Subnode, $value);
					} else {
						ArrayToXml::simple_to_xml($Xml, $value);
					}
				} else {
					$Xml->addChild("$tag", ArrayToXml::xml_value($value));
				}
			}
		} elseif (!empty($nodeTag)) {
			$Xml->addChild("$nodeTag", ArrayToXml::xml_value($data));
		}
		return true;
	}
	/**
	* Assigns attributes to an XML object
	* @param object $Xml
	* @param array $attributes
	* @return bool
	*/
	static function xml_attributes(&$Xml, $attributes=array()) {
		if (!is_array($attributes) || empty($attributes)) {
			return true;
		}
		foreach ( $attributes as $attrib_name => $attrib_value ) { 
			$Xml->addAttribute("$attrib_name", ArrayToXml::xml_value($attrib_value));
		}
		return true;
	}
	/**
	* Cleans a value for use in XML
	* @param mixed $value
	* @return mixed $value
	*/
	static function xml_value($value) {
		if (!ArrayToXml::$cleanValues) {
			return $value;
		}
		if ($value===true) {
			return "TRUE";
		} elseif ($value===false) {
			return "FALSE";
		} elseif (is_numeric($value)) {
			return $value;
		}
		return strval($value);
	}
	/**
	* Parses attributes and possible non-duplicate/numeric keys 
	* @param string $tag
	* @return array compact('tag', 'options')
	*/
	static function parseKey($tag) {
		$options = null;
		if (strpos($tag, "|")!==false) {
			list($tag, $options) = explode("|", $tag);
			$options = json_decode($options, true);
			if (!is_array($options)) {
				$options = null;
			}
		}
		$tag = trim($tag);
		return compact('tag', 'options');
	}
	/**
	* Add a tag name to all numerical indexes in an array
	* @param array $array
	* @param string $tag
	* @param bool $recursive false
	* @return array $out
	*/
	static function add_tags($array, $tag, $recursive=false) {
		$out = array();
		foreach ( $array as $key => $val ) {
			if ($recursive && is_array($val) && strval($key)!=ArrayToXml::$attribKey) {
				$val = ArrayToXml::add_tags($val, $tag, $recursive);
			}
			if (is_numeric($key)) {
				$out["{$tag}|{$key}"] = $val;
				continue;
			}
			$out["$key"] = $val;
		}
		return $out;
	}
}
?>
