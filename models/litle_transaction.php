<?php
/**
* Plugin model for "Litle Credit Card Transaction Processing".
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link http://zeroasterisk.com
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*
*
*/
class LitleTransaction extends LitleAppModel {
	/**
	* The name of this model
	* @var name
	*/
	public $name ='LitleTransaction';
	public $useTable = false;
	public $useDbConfig = 'litle';
	/**
	* The fields and their types for the form helper
	* @var array
	*/
	public $_schema = array(
		'authentication_user' => array('type' => 'string', 'length' => '20'),
		'authentication_password' => array('type' => 'string', 'length' => '20'),
		'method_of_payment' => array('type' => 'string', 'length' => '2', 'options' => array('MC', 'VI',' AX', 'DC', 'DI', 'PP', 'JC', 'BL', 'EC')),
		'govt_tax' => array('type' => 'string', 'length' => '16', 'options' => array('payment', 'fee')),
		'currency_code' => array('type' => 'string', 'length' => '3', 'options' => array('AUD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'NOK', 'NZD', 'SEK', 'SGD', 'USD')),
		'transaction_amount' => array('type' => 'integer'),
		);
	
	/**
	* Overwrite of the save() function
	* we prepare for the repsonse array, and parse the status to see if it's an error or not
	* @param mixed $data
	* @param mixed $validate true
	*/
	public function save($data = array(), $validate = true) {
		
		$xml = $this->__prepareDataForPost($data);
		echo $xml;die(); 
		
		$this->response = array();
		echo "\nparent:save() start";
		$response = parent::save($data, $validate);
		echo " stop\n";
		if (!empty($this->response) && isset($this->response['status']) && $this->response['status']=="good") {
			return $this->response;
		}
		if (isset($this->response['error']) && !empty($this->response['error'])) {
			$this->validationErrors[] = $this->response['error'];
			return false;
		}
		$this->validationErrors[] = "unknown error";
		return false;
	}
	
	/**
	* Helper to facilitate easy "sale" transactions
	*
	* @param array $input
	* @return array $response
	*/
	function sale($data=array()) {
		$errors = array();
		$data = $this->translateFields($data, 'sale');
		$data = $this->assignDefaults($data, 'sale');
		if ($this->config['auto_orderId_if_missing'] && (!isset($data['orderId']) || empty($data['orderId']))) {
			$data['orderId'] = time();
		}
		$requiredFields = array('reportGroup', 'orderId', 'amount', 'orderSource');
		foreach ( $requiredFields as $key ) { 
			if (!array_key_exists($key, $data) || empty($data[$key])) {
				$errors[] = "Missing required field [{$key}]";
			}
		}
		$requiredPayment = array('card', 'paypal', 'paypage', 'token');
		$foundPayment = array_intersect_key($data, array_flip($requiredPayment));
		if (empty($foundPayment)) {
			$errors[] = "Missing required payment";
		}
		if (!empty($errors)) {
			$status = 'failed';
			return compact('status', 'errors', 'data');
		}
		// attempt the API interaction
		$uid = time();
		$saleData = array(
			'attrib' => array(
				'reportGroup' => $data['reportGroup'],
				'id' => $uid,
				),
			);
		$sale = set::merge($data, $saleData);
		unset($sale['reportGroup']);
		unset($sale['merchantId']);
		/* $requestData = array(
			'batchRequest' => array(
				'attrib' => array(
					'id' => $uid,
					'numSales' => 1,
					'saleAmount' => $data['amount'],
					'merchantId' => $data['merchantId'],
					),
				'sale' => $sale,
				),
			); */
		$requestData = compact($sale);
		$response_raw = $this->save($requestData);
		// parse the response
		$response = $this->parse_sale_response($response_raw);
		return compact('status', 'errors', 'data', 'response', 'response_raw');
	}
	
	function parse_sale_response($data) {
		return $data;
	}
}
?>
