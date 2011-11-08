<?php
/* LitleSource */
# import model because that's how the datasource it inited
App::import('Datasource', 'litle.LitleSource');
App::import('Model', 'litle.LitleTransaction');
App::import('Lib', 'Templates.AppTestCase');
# these details should be set in your config, but can be overridden here
# configure::write('Litle.user', '******');
# configure::write('Litle.password', '******');
# configure::write('Litle.merchantId', '******');
# probably always a good idea to override the URL to hit the cert URL
configure::write('Litle.url', 'https://cert.litle.com/vap/communicator/online');
configure::write('Litle.logModel', false);
class LitleSourceTestCase extends AppTestCase {
	public $plugin = 'app';
	public $fixtures = array();
	protected $_testsToRun = array();
	protected $sale = array(
			'sale|{"id":"1","reportGroup":"ABC Division","customerId":"038945"}' => array(
				'orderId' => '5234234',
				'amount' => '40000',
				'orderSource' => 'ecommerce', // recurring
				'billToAddress' => array(
					'name' => 'John Smith',
					'addressLine1' => '100 Main St',
					'addressLine2' => '100 Main St',
					'addressLine3' => '100 Main St',
					'city' => 'Boston',
					'state' => 'MA',
					'zip' => '12345',
					'country' => 'US',
					'email' => 'jsmith@someaddress.com',
					'phone' => '555-123-4567',
					),
				'card' => array(
					'type' => 'VI',
					'number' => '4005550000081019',
					'expDate' => '1210',
					'cardValidationNum' => '555',
					),
				'enhancedData' => array(
					'customerReference' => 'PO12345',
					'salesTax' => '125',
					'taxExempt' => 'false',
					'discountAmount' => '0',
					'shippingAmount' => '495',
					'dutyAmount' => '0',
					'shipFromPostalCode' => '01851',
					'destinationPostalCode' => '01851',
					'destinationCountryCode' => 'USA',
					'invoiceReferenceNumber' => '123456',
					'orderDate' => '2009-08-14',
					'detailTax' => array(
						'taxIncludedInTotal' => 'true',
						'taxAmount' => '55',
						'taxRate' => '0.0059',
						'taxTypeIdentifier' => '00',
						'cardAcceptorTaxId' => '011234567',
						),
					'lineItemData|0' => array(
						'itemSequenceNumber' => '1',
						'itemDescription' => 'chair',
						'productCode' => 'CH123',
						'quantity' => '1',
						'unitOfMeasure' => 'EACH',
						'taxAmount' => '125',
						'lineItemTotal' => '9380',
						'lineItemTotalWithTax' => '9505',
						'itemDiscountAmount' => '0',
						'commodityCode' => '300',
						'unitCost' => '93.80',
						'detailTax' => array(
							'taxIncludedInTotal' => 'true',
							'taxAmount' => '55',
							'taxRate' => '0.0059',
							'taxTypeIdentifier' => '03',
							'cardAcceptorTaxId' => '011234567',
							)
						),
					'lineItemData|1' => array(
						'itemSequenceNumber' => '2',
						'itemDescription' => 'table',
						'productCode' => 'TB123',
						'quantity' => '1',
						'unitOfMeasure' => 'EACH',
						'lineItemTotal' => '30000',
						'itemDiscountAmount' => '0',
						'commodityCode' => '300',
						'unitCost' => '300.00',
						),
					),
				),
			);
	/**
	* Start Test callback
	*
	* @param string $method
	* @return void
	* @access public
	*/
	function startTest(){
		$this->LitleSource = new LitleSource();
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
		unset($this->LitleSource);
		ClassRegistry::flush();
	}

	/**
	* Validate the plugin setup
	*/
	function testSetup() {
		$this->assertTrue(is_object($this->LitleSource));
		$this->assertTrue(is_object($this->LitleSource->Http));
		$plugin_version = LitleUtil::getConfig('plugin_version');
		$this->assertTrue(!empty($plugin_version), "missing plugin_version from config");
		$version = LitleUtil::getConfig('version');
		$this->assertTrue(!empty($version), "missing version from config");
		$url_xmlns = LitleUtil::getConfig('url_xmlns');
		$this->assertTrue(!empty($url_xmlns), "missing url_xmlns from config");
		$merchantId = LitleUtil::getConfig('merchantId');
		$this->assertTrue(!empty($merchantId), "missing merchantId from config");
	}
	/**
	* Validate prepApiData functionality
	*/
	function testPrepApiData() {
		// set only the sale node, the auth and wrapper should be set in the function
		// the mockup data assumes we are giving the function exactly what it expected
		$response = str_replace(array("\n", "	"), '', $this->LitleSource->prepareApiData($this->sale));
		// expected data straight out of the examples sent with the API documentaiton
		$expected = str_replace(array("\n", "	"), '', '
			<litleOnlineRequest version="'.LitleUtil::getConfig('version').'" xmlns="'.LitleUtil::getConfig('url_xmlns').'" merchantId="'.LitleUtil::getConfig('merchantId').'">
				<authentication>
					<user>'.LitleUtil::getConfig('user').'</user>
					<password>'.LitleUtil::getConfig('password').'</password>
				</authentication>
				<sale id="1" reportGroup="ABC Division" customerId="038945">
					<orderId>5234234</orderId>
					<amount>40000</amount>
					<orderSource>ecommerce</orderSource>
					<billToAddress>
						<name>John Smith</name>
						<addressLine1>100 Main St</addressLine1>
						<addressLine2>100 Main St</addressLine2>
						<addressLine3>100 Main St</addressLine3>
						<city>Boston</city>
						<state>MA</state>
						<zip>12345</zip>
						<country>US</country>
						<email>jsmith@someaddress.com</email>
						<phone>555-123-4567</phone>
					</billToAddress>
					<card>
						<type>VI</type>
						<number>4005550000081019</number>
						<expDate>1210</expDate>
						<cardValidationNum>555</cardValidationNum>
					</card>
					<enhancedData>
						<customerReference>PO12345</customerReference>
						<salesTax>125</salesTax>
						<taxExempt>false</taxExempt>
						<discountAmount>0</discountAmount>
						<shippingAmount>495</shippingAmount>
						<dutyAmount>0</dutyAmount>
						<shipFromPostalCode>01851</shipFromPostalCode>
						<destinationPostalCode>01851</destinationPostalCode>
						<destinationCountryCode>USA</destinationCountryCode>
						<invoiceReferenceNumber>123456</invoiceReferenceNumber>
						<orderDate>2009-08-14</orderDate>
						<detailTax>
							<taxIncludedInTotal>true</taxIncludedInTotal>
							<taxAmount>55</taxAmount>
							<taxRate>0.0059</taxRate>
							<taxTypeIdentifier>00</taxTypeIdentifier>
							<cardAcceptorTaxId>011234567</cardAcceptorTaxId>
						</detailTax>
						<lineItemData>
							<itemSequenceNumber>1</itemSequenceNumber>
							<itemDescription>chair</itemDescription>
							<productCode>CH123</productCode>
							<quantity>1</quantity>
							<unitOfMeasure>EACH</unitOfMeasure>
							<taxAmount>125</taxAmount>
							<lineItemTotal>9380</lineItemTotal>
							<lineItemTotalWithTax>9505</lineItemTotalWithTax>
							<itemDiscountAmount>0</itemDiscountAmount>
							<commodityCode>300</commodityCode>
							<unitCost>93.80</unitCost>
							<detailTax>
								<taxIncludedInTotal>true</taxIncludedInTotal>
								<taxAmount>55</taxAmount>
								<taxRate>0.0059</taxRate>
								<taxTypeIdentifier>03</taxTypeIdentifier>
								<cardAcceptorTaxId>011234567</cardAcceptorTaxId>
							</detailTax>
						</lineItemData>
						<lineItemData>
							<itemSequenceNumber>2</itemSequenceNumber>
							<itemDescription>table</itemDescription>
							<productCode>TB123</productCode>
							<quantity>1</quantity>
							<unitOfMeasure>EACH</unitOfMeasure>
							<lineItemTotal>30000</lineItemTotal>
							<itemDiscountAmount>0</itemDiscountAmount>
							<commodityCode>300</commodityCode>
							<unitCost>300.00</unitCost>
						</lineItemData>
					</enhancedData>
				</sale>
			</litleOnlineRequest>
		');
		$this->assertEqual($response, $expected);
	}
	/**
	* Validate parseResponse functionality
	*/
	function testParseResponse() {
		$sale_response_xml = str_replace(array("\n", "	"), '', '
			<saleResponse id="1" reportGroup="ABC Division" customerId="038945">
				<litleTxnId>1100030055</litleTxnId>
				<orderId>23423434</orderId>
				<response>000</response>
				<responseTime>2009-07-11T14:48:46</responseTime>
				<postDate>2009-07-11</postDate>
				<message>Approved</message>
				<authCode>123457</authCode>
				<fraudResult>
					<avsResult>01</avsResult>
					<cardValidationResult>U</cardValidationResult>
					<authenticationResult>2</authenticationResult>
				</fraudResult>
			</saleResponse>
			');
		$response_xml = str_replace(array("\n", "	"), '', '
			<litleOnlineResponse version="8.7" xmlns="http://www.litle.com/schema" response="0" message="Valid Format">
				'.$sale_response_xml.'
			</litleOnlineResponse>
			');
		$response = $this->LitleSource->parseResponse($response_xml);
		$expected = array(
			'status' => 'good',
			'transaction_id' => null,
			'errors' => array(),
			'response_raw' => $response_xml,
			);
		$response_check = $response;
		unset($response_check['response_array']);
		$this->assertEqual($response_check, $expected);

		$response_xml = str_replace(array("\n", "	"), '', '
			<litleOnlineResponse version="8.7" xmlns="http://www.litle.com/schema" response="1" message="System Error - Call Litle &amp; Co.">
				'.$sale_response_xml.'
			</litleOnlineResponse>
			');
		$response = $this->LitleSource->parseResponse($response_xml);
		$expected = array(
			'status' => 'error',
			'transaction_id' => null,
			'errors' => array(
				'System Error - Call Litle & Co.',
				),
			'response_raw' => $response_xml,
			);
		$response_check = $response;
		unset($response_check['response_array']);
		$this->assertEqual($response_check, $expected);
	}
	/**
	* Validate __request functionality
	*/
	function test__request() {
		$request = $this->LitleSource->__request($this->sale);
		$this->AssertEqual($request['status'], "good");
		$this->AssertEqual($request['errors'], array());
		$reponse = $request['response_array'];
		$this->AssertEqual($reponse['message'], "Valid Format");
		$this->AssertTrue((strpos($reponse['SaleResponse']['responseTime'], date("Y-m-"))!==false), "date ".date("Y-m-")." not found in responseTime {$reponse['SaleResponse']['responseTime']}");
		unset($reponse['SaleResponse']['responseTime']);
		$expected = array(
			'duplicate' => true,
			'id' => 1,
			'reportGroup' => 'ABC Division',
			'customerId' => '038945',
			'orderId' => '5234234',
			'response' => '000',
			'postDate' => date("Y-m-d"),
			'message' => 'Approved',
			'authCode' => '123457',
			'FraudResult' => Array(
				'avsResult' => '00',
				'cardValidationResult' => 'M',
				),
			);
		
		$litleTxnId = $reponse['SaleResponse']['litleTxnId'];
		unset($reponse['SaleResponse']['litleTxnId']);
		$this->AssertTrue($litleTxnId > 10000000, "Transaction ID not large enough [$litleTxnId]");
		$this->AssertEqual($reponse['SaleResponse'], $expected, "unexpected response: ".json_encode(set::diff($reponse['SaleResponse'], $expected)));
	}
	/*  */
}
?>
