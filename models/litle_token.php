<?php
/**
* Plugin model for "Litle Credit Card Tokenization".
*
* offloading of CC management to Litle
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link http://zeroasterisk.com
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*/
class LitleToken extends LitleAppModel {
	/**
	* The name of this model
	* @var name
	*/
	public $name ='LitleToken';
	/**
	* The fields and their types for the form helper
	* @var array
	*/
	public $_schema = array(
		'account_number' => array('type' => 'integer'),
		'token' => array('type' => 'integer'),
		);
	/**
	* Initially setup a token from an account number
	* note: usually not needed, since transactions are tokenized automatically
	* note: assumes all other details are defaulted from config
	* @param int $account_number
	* @return int $token
	*/
	public function register($account_number) {
		$this->save(array('accountNumber' => $account_number));
		if (isset($this->lastRequest['response_array']['litleToken'])) {
			return $this->lastRequest['response_array']['litleToken'];
		}
		return 0;
	}
	/**
	* beforeSave reconfigures save inputs for "sale" transactions
	* assumes LitleSale->data exists and has the details for the save()
	* @param array $options optional extra litle config data
	* @return array $response
	*/
	function beforeSave($options=array()) {
		$config = set::merge($this->config, $options);
		$errors = array();
		// setup defaults so elements are in the right order.
		$data = $this->data[$this->alias];
		$data = $this->translateFields($data, 'registerTokenRequest');
		$data = $this->assignDefaults($data, 'registerTokenRequest');
		// prep root element attributes
		$reportGroup = (isset($data['reportGroup']) ? $data['reportGroup'] : 'unspecified');
		$customerId = (isset($data['customerId']) ? $data['customerId'] : 0);
		$id = (isset($data['id']) ? $data['id'] : time());
		$rootAttributes = compact('id', 'customerId', 'reportGroup');
		$data = $this->finalizeFields($data, 'void', $rootAttributes);
		$this->data = array($this->alias => $data);
		return true;
	}
	/**
	* afterSave parses results and verifies status for this transaction
	* assumes LitleSale->lastRequest exists and has the details for this request
	* @param array $options optional extra litle config data
	* @return array $response
	*/
	function afterSave($created=null) {
		parent::afterSave($created);
		if (empty($this->lastRequest)) {
			$this->lastRequest = array('status' => 'error', 'errors' => array("Unable to access {$this->Alias}->lastRequest"));
			return false;
		}
		extract($this->lastRequest);
		if (isset($response_array['RegisterTokenResponse'])) {
			$response_array = set::flatten($response_array['RegisterTokenResponse']);
		}
		extract($response_array);
		$this->id = $transaction_id = (!empty($litleTxnId) ? $litleTxnId : 0);
		if (empty($transaction_id)) {
			$errors[] = "Missing transaction_id (litleTxnId)";
		}
		if ($response!="000" && $response!="0") {
			$errors[] = "Error: {$message}";
		}
		if (!empty($errors)) {
			$status = "error";
		}
		$this->lastRequest = compact($this->requestVars);
		return true;
	}
}
?>