<?php
/**
 * Convenience test for all Tests in Litle Plugin
 *
 * ./cake test Litle AllLitle
 *
 */
class AllLitleTest extends CakeTestSuite {
	public static $working = array(
		// these tests will work without firewall authorization to Litle
		'Lib/LitleUtil',
		'Lib/ArrayToXml',
		'Model/LitleAppModel',
		// these test require firewall authorization to Litle
		'Model/Datasource/LitleSource',
		'Model/LitleSale',
		'Model/LitleCredit',
		'Model/LitleVoid',
		'Model/LitleToken',
	);

	public static function suite() {
		$suite = new CakeTestSuite('All Litle tests');
		$dir = dirname(__FILE__);
		foreach (self::$working as $file) {
			$suite->addTestFile($dir . DS . $file . 'Test.php');
		}
		return $suite;
	}
}
