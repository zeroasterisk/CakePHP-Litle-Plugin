<?php
/**
* Unit Tests for ArrayToXml
*
* @link https://github.com/zeroasterisk/CakePHP-ArrayToXml-Lib
* @author Alan Blount <alan@zeroasterisk.com>
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*/
App::uses('LitleUtil', 'Litle.Lib');
App::uses('ArrayToXml', 'Litle.Lib');
App::uses('Set', 'Utility');
class ArrayToXmlTest extends CakeTestCase {

	public $plugin = 'app';
	public $fixtures = array();
	protected $_testsToRun = array();

	public $array1 = array(
			'A' => 'one',
			'B|{"attrJson1":"attrVal1","attrJson2":"attrVal2"}' => '2',
			'C' => 3,
			'D' => null,
			'E' => 5.5,
			'F' => 0,
			'G' => false,
			'H' => true,
			'I' => array(
				'J' => 'one',
				'K' => '2',
				'L' => 3,
				'M' => null,
				'N' => 5.5,
				'O' => 0,
				'P' => false,
				'Q' => true,
				'R' => array(
					'S' => 'one',
					'T' => '2',
					'U' => 3,
					'V' => null,
					'W' => 5.5,
					'X' => 0,
					'Y' => false,
					'Z' => true,
					),
				),
			'a' => array(
				'attrib' => array(
					'attr1' => 'val1',
					'attr2' => 'val2',
					'attr3' => 3,
					'attr4' => true,
					'attr5' => false,
					'attr6' => null,
					'attr7' => 7.77,
					),
				'b' => 'two',
				'c' => 3,
				'd' => null,
				'e' => 5.5,
				'f' => 0,
				'g' => false,
				'h' => true,
				'i' => array(
					'attrib' => array(
						'attr1' => 'val1',
						'attr2' => 'val2',
						'attr3' => 3,
						'attr4' => true,
						'attr5' => false,
						'attr6' => null,
						'attr7' => 7.77,
						),
					'j' => 'one',
					'k' => '2',
					'l' => 3,
					'm' => null,
					'n' => 5.5,
					'o' => 0,
					'p' => false,
					'q' => true,
					),
				),
			);
	public $array2 = array(
		array('id' => 1234, 'name' => 'one'),
		array('id' => 1235, 'name' => 'two'),
		array('id' => 1236, 'name' => 'three', 'attrib' => array("key", "value")),
		);
	public $array3 = array(
		'Parent' => array(
			'id' => 1,
			'name' => 'parent-node',
			),
		'Children' => array(
			array('id' => 1234, 'name' => 'one'),
			array('id' => 1235, 'name' => 'two'),
			array('id' => 1236, 'name' => 'three', 'attrib' => array("key", "value")),
			),
		);

	/**
	* Start Test callback
	*
	* @param string $method
	* @return void
	* @access public
	*/
	public function startTest($method) {
		parent::startTest($method);
	}

	/**
	* End Test callback
	*
	* @param string $method
	* @return void
	* @access public
	*/
	public function endTest($method) {
		parent::endTest($method);
		ClassRegistry::flush();
	}



	public function test_build() {
		$xmlStr = str_replace("\n", "", ArrayToXml::build($this->array1, true));
		$expected = '<?xml version="1.0" encoding="UTF-8" ?'.'><A>one</A><B attrJson1="attrVal1" attrJson2="attrVal2">2</B><C>3</C><D/><E>5.5</E><F>0</F><G>FALSE</G><H>TRUE</H><I><J>one</J><K>2</K><L>3</L><M/><N>5.5</N><O>0</O><P>FALSE</P><Q>TRUE</Q><R><S>one</S><T>2</T><U>3</U><V/><W>5.5</W><X>0</X><Y>FALSE</Y><Z>TRUE</Z></R></I><a attr1="val1" attr2="val2" attr3="3" attr4="1" attr5="0" attr6="" attr7="7.77"><b>two</b><c>3</c><d/><e>5.5</e><f>0</f><g>FALSE</g><h>TRUE</h><i attr1="val1" attr2="val2" attr3="3" attr4="1" attr5="0" attr6="" attr7="7.77"><j>one</j><k>2</k><l>3</l><m/><n>5.5</n><o>0</o><p>FALSE</p><q>TRUE</q></i></a>';
		$this->assertEqual($xmlStr, $expected);
		$xmlStr = str_replace("\n", "", ArrayToXml::build($this->array1, true));
	}
	public function test_simplexml() {
		$xmlStr = str_replace("\n", "", ArrayToXml::simplexml($this->array1, "nodes"));
		$expected = '<?xml version="1.0"?'.'><nodes><A>one</A><"attrVal1","attrJson2":"attrVal2"}>2</"attrVal1","attrJson2":"attrVal2"}><C>3</C><D/><E>5.5</E><F>0</F><G>FALSE</G><H>TRUE</H><I><J>one</J><K>2</K><L>3</L><M/><N>5.5</N><O>0</O><P>FALSE</P><Q>TRUE</Q><R><S>one</S><T>2</T><U>3</U><V/><W>5.5</W><X>0</X><Y>FALSE</Y><Z>TRUE</Z></R></I><a attr1="val1" attr2="val2" attr3="3" attr4="TRUE" attr5="FALSE" attr6="" attr7="7.77"><b>two</b><c>3</c><d/><e>5.5</e><f>0</f><g>FALSE</g><h>TRUE</h><i attr1="val1" attr2="val2" attr3="3" attr4="TRUE" attr5="FALSE" attr6="" attr7="7.77"><j>one</j><k>2</k><l>3</l><m/><n>5.5</n><o>0</o><p>FALSE</p><q>TRUE</q></i></a></nodes>';
		$this->assertEqual($xmlStr, $expected);
		$xmlStr = str_replace("\n", "", ArrayToXml::simplexml($this->array1));
		$expected = '<?xml version="1.0"?'.'><A><A>one</A><"attrVal1","attrJson2":"attrVal2"}>2</"attrVal1","attrJson2":"attrVal2"}><C>3</C><D/><E>5.5</E><F>0</F><G>FALSE</G><H>TRUE</H><I><J>one</J><K>2</K><L>3</L><M/><N>5.5</N><O>0</O><P>FALSE</P><Q>TRUE</Q><R><S>one</S><T>2</T><U>3</U><V/><W>5.5</W><X>0</X><Y>FALSE</Y><Z>TRUE</Z></R></I><a attr1="val1" attr2="val2" attr3="3" attr4="TRUE" attr5="FALSE" attr6="" attr7="7.77"><b>two</b><c>3</c><d/><e>5.5</e><f>0</f><g>FALSE</g><h>TRUE</h><i attr1="val1" attr2="val2" attr3="3" attr4="TRUE" attr5="FALSE" attr6="" attr7="7.77"><j>one</j><k>2</k><l>3</l><m/><n>5.5</n><o>0</o><p>FALSE</p><q>TRUE</q></i></a></A>';
		$this->assertEqual($xmlStr, $expected);

		$xmlStr = str_replace("\n", "", ArrayToXml::simplexml(ArrayToXml::add_tags($this->array2, "node"), "nodes"));
		$expected = '<?xml version="1.0"?><nodes><node><id>1234</id><name>one</name></node><node><id>1235</id><name>two</name></node><node 0="key" 1="value"><id>1236</id><name>three</name></node></nodes>';
		$this->assertEqual($xmlStr, $expected);

		$array3 = $this->array3;
		$array3['Children'] = ArrayToXml::add_tags($array3['Children'], "Child", true);
		$xmlStr = str_replace("\n", "", ArrayToXml::simplexml($array3, "nodes"));
		$expected = '<?xml version="1.0"?><nodes><Parent><id>1</id><name>parent-node</name></Parent><Children><Child><id>1234</id><name>one</name></Child><Child><id>1235</id><name>two</name></Child><Child 0="key" 1="value"><id>1236</id><name>three</name></Child></Children></nodes>';
		$this->assertEqual($xmlStr, $expected);
	}
	public function test_simple_to_xml() {
		$XmlObj = new SimpleXMLElement("<?xml version=\"1.0\"?><test></test>");
		ArrayToXml::simple_to_xml($XmlObj, $this->array1);
		$xmlStr = str_replace("\n", "", $XmlObj->asXML());
		$expected = '<?xml version="1.0"?><test><A>one</A><"attrVal1","attrJson2":"attrVal2"}>2</"attrVal1","attrJson2":"attrVal2"}><C>3</C><D/><E>5.5</E><F>0</F><G>FALSE</G><H>TRUE</H><I><J>one</J><K>2</K><L>3</L><M/><N>5.5</N><O>0</O><P>FALSE</P><Q>TRUE</Q><R><S>one</S><T>2</T><U>3</U><V/><W>5.5</W><X>0</X><Y>FALSE</Y><Z>TRUE</Z></R></I><a attr1="val1" attr2="val2" attr3="3" attr4="TRUE" attr5="FALSE" attr6="" attr7="7.77"><b>two</b><c>3</c><d/><e>5.5</e><f>0</f><g>FALSE</g><h>TRUE</h><i attr1="val1" attr2="val2" attr3="3" attr4="TRUE" attr5="FALSE" attr6="" attr7="7.77"><j>one</j><k>2</k><l>3</l><m/><n>5.5</n><o>0</o><p>FALSE</p><q>TRUE</q></i></a></test>';
		$this->assertEqual($xmlStr, $expected);

		$XmlObj = new SimpleXMLElement("<?xml version=\"1.0\"?><test></test>");
		ArrayToXml::simple_to_xml($XmlObj, $this->array2);
		$xmlStr = str_replace("\n", "", $XmlObj->asXML());
		$expected = '<?xml version="1.0"?><test><id>1234</id><name>one</name><id>1235</id><name>two</name><id>1236</id><name>three</name><attrib><0>key</0><1>value</1></attrib></test>';
		$this->assertEqual($xmlStr, $expected);

		$XmlObj = new SimpleXMLElement("<?xml version=\"1.0\"?><test></test>");
		ArrayToXml::simple_to_xml($XmlObj, ArrayToXml::add_tags($this->array2, "node"));
		$xmlStr = str_replace("\n", "", $XmlObj->asXML());
		$expected = '<?xml version="1.0"?><test><node><id>1234</id><name>one</name></node><node><id>1235</id><name>two</name></node><node 0="key" 1="value"><id>1236</id><name>three</name></node></test>';
		$this->assertEqual($xmlStr, $expected);
	}
	public function test_xml_attributes() {
		// control
		$XmlObj = new SimpleXMLElement("<?xml version=\"1.0\"?><test></test>");
		$xmlStr = str_replace("\n", "", $XmlObj->asXML());
		$expected = '<?xml version="1.0"?><test/>';
		$this->assertEqual($xmlStr, $expected);
		$XmlObj = new SimpleXMLElement("<?xml version=\"1.0\"?><test></test>");
		ArrayToXml::xml_attributes($XmlObj, array());
		$xmlStr = str_replace("\n", "", $XmlObj->asXML());
		$expected = '<?xml version="1.0"?><test/>';
		$this->assertEqual($xmlStr, $expected);
		// attrs
		$XmlObj = new SimpleXMLElement("<?xml version=\"1.0\"?><test></test>");
		ArrayToXml::xml_attributes($XmlObj, array('attr1' => 'val1', 'attr2' => 2.2, 'attr3' => false, 'attr4' => array(0,2,3)));
		$xmlStr = str_replace("\n", "", $XmlObj->asXML());
		$expected = '<?xml version="1.0"?><test attr1="val1" attr2="2.2" attr3="FALSE" attr4="Array"/>';
		$this->assertEqual($xmlStr, $expected);
	}
	public function test_parseKey() {
		// clean tag
		$expected = array('tag' => 'node', 'options' => null);
		$this->assertEqual($expected, ArrayToXml::parseKey('node'));
		$expected = array('tag' => 'node', 'options' => null);
		$this->assertEqual($expected, ArrayToXml::parseKey("\nnode "));
		// parse options
		$expected = array('tag' => 'node', 'options' => array("attr" => "val", "bool" => true));
		$this->assertEqual($expected, ArrayToXml::parseKey('node|{"attr":"val","bool":true}'));
		// parse iteration cruft
		$expected = array('tag' => 'node', 'options' => null);
		for ($A=0;$A<20;$A++) {
			$this->assertEqual($expected, ArrayToXml::parseKey("node|{$A}"));
			$A = $A+rand(1,10);
		}
		// parse options + iteration cruft + clean
		$expected = array('tag' => 'node', 'options' => array("attr" => "val", "bool" => true));
		$this->assertEqual($expected, ArrayToXml::parseKey("\n".'node |{"attr":"val","bool":true}|0'));
	}
	public function test_add_tags() {
		$expected = $this->array3;
		$response = ArrayToXml::add_tags($this->array3, "Child");
		$this->assertEqual($expected, $response);
		$expected = array(
			'Child|0' => array('id' => 1234, 'name' => 'one'),
			'Child|1' => array('id' => 1235, 'name' => 'two'),
			'Child|2' => array('id' => 1236, 'name' => 'three', 'attrib' => array("key", "value")),
			);
		$response = ArrayToXml::add_tags($this->array3['Children'], "Child");
		$this->assertEqual($expected, $response);
		$response = ArrayToXml::add_tags($this->array3['Children'], "Child", true);
		$this->assertEqual($expected, $response);
		$tricky = array(
			'Main' => array(
				'Nest1' => array(
					array('name' => 'nested1', 'tagged', 'tagged'),
					),
				array('name' => 'nested2', 'tagged', 'tagged'),
				),
			array('name' => 'nested2', 'tagged', 'tagged'),
			);
		$expected = array(
			'Main' => array(
				'Nest1' => array(
					'Tag|0' => array('name' => 'nested1',
						'Tag|0' => 'tagged',
						'Tag|1' => 'tagged'
						),
					),
				'Tag|0' => array('name' => 'nested2',
					'Tag|0' => 'tagged',
					'Tag|1' => 'tagged'
					),
				),
			'Tag|0' => array('name' => 'nested2',
				'Tag|0' => 'tagged',
				'Tag|1' => 'tagged'
				),
			);
		$response = ArrayToXml::add_tags($tricky, "Tag", true);
		$this->assertEqual($expected, $response);
	}
}
?>
