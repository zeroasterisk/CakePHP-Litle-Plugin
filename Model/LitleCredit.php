<?php
/**
 * Plugin model for "Litle Credit Card Credit Processing".
 *
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/CakePHP-Litle-Plugin
 * @copyright (c) 2011 Alan Blount
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 *
 * @example
 $saleWorked = $this->LitleSale->save(array(
	 'orderId' => 1234,
	 'amount' => 100,
	 'orderSource' => 'ecommerce',
 ));
debug($saleWorked);
debug($this->LitleSale->lastRequest);
$transaction_id = $this->LitleSale->id;
# more: $transaction_id == $this->LitleSale->lastRequest['transaction_id'];

# and now to credit that sale:
$creditWorked = $this->LitleCredit->save(array(
	'litleTxnId' => $transaction_id,
));

# or you can us a helper on the Sale model
$creditWorked = $this->LitleSale->delete($transaction_id);
 */
class LitleCredit extends LitleAppModel {
	/**
	 * The name of this model
	 * @var name
	 */
	public $name ='LitleCredit';
	/**
	 * This model doesn't use a table
	 * @var name
	 */
	public $useTable = false;
	/**
	 * This model requires the datasource of litle
	 * @var name
	 */
	public $useDbConfig = 'litle';
	/**
	 *
	 *
	 */
	public $primaryKey = 'litleTxnId';
	/**
	 * The fields and their types for the form helper
	 * @var array
	 */
	public $_schema = array(
		// attributes
		'id' => array('type' => 'string', 'length' => '25', 'comment' => 'unique transaction id (determines duplicates)'),
		'reportGroup' => array('type' => 'string', 'length' => '25', 'comment' => 'required attribute that defines the merchant sub-group'),
		'customerId' => array('type' => 'string', 'length' => '25', 'comment' => 'required attribute that defines the merchant sub-group'),
		// elements
		'litleTxnId' => array('type' => 'integer', 'length' => '19', 'comment' => 'internal order id'),
		// extra optional fields
		'amount' => array('type' => 'integer', 'length' => '8', 'comment' => 'amount to credit, optional, if empty full amount is credited'),
		// extra field to create root level element
		'root' => array('type' => 'blob'),
	);
	/**
	 * These fields are submitted in a saleResponse from Litle
	 * @var array
	 */
	public $_schema_response = array(
		'litleTxnId' => array('type' => 'string', 'length' => '25', 'comment' => 'litle\'s unique transaction id from response'),
		'response' => array('type' => 'integer', 'length' => '3', 'comment' => 'response code'),
		'responseTime' => array('type' => 'datetime'),
		'message' => array('type' => 'string', 'length' => '512', 'comment' => 'brief definition of the response code'),
		'postDate' => array('type' => 'datetime'),
	);
	/**
	 * beforeSave reconfigures save inputs for "sale" transactions
	 * assumes LitleSale->data exists and has the details for the save()
	 * @param array $options
	 * @return array $response
	 */
	public function beforeSave($options=array()) {
		// TODO: use token or use card <<?
		$errors = array();
		// setup defaults so elements are in the right order.
		$data = $this->data[$this->alias];
		$data = $this->translateFields($data, 'credit');
		$data = $this->assignDefaults($data, 'credit');
		$requiredFields = array('reportGroup', 'litleTxnId');
		foreach ( $requiredFields as $key ) {
			if (!array_key_exists($key, $data) || empty($data[$key])) {
				$errors[] = "Missing required field [{$key}]";
			}
		}
		// prep sale element attributes
		$reportGroup = (isset($data['reportGroup']) ? $data['reportGroup'] : 'unspecified');
		$customerId = (isset($data['customerId']) ? $data['customerId'] : 0);
		$id = (isset($data['id']) ? $data['id'] : time());
		$rootAttributes = compact('id', 'customerId', 'reportGroup');
		$data = $this->finalizeFields($data, 'credit', $rootAttributes);
		$this->data = array($this->alias => $data);
		// verfiy fail on errors
		if (!empty($errors)) {
			$status = 'failed';
			$this->lastRequest = compact('status', 'errors', 'data', 'data_raw');
			return false;
		}
		return true;
	}

	/**
	 * afterSave parses results and verifies status for this transaction
	 * assumes LitleSale->lastRequest exists and has the details for this request
	 *
	 * @param boolean $created
	 * @param array $options
	 * @return array $response
	 */
	public function afterSave($created=null, $options = array()) {
		parent::afterSave($created);
		if (empty($this->lastRequest)) {
			$this->lastRequest = array('status' => 'error', 'errors' => array("Unable to access {$this->Alias}->lastRequest"));
			return false;
		}
		extract($this->lastRequest);
		// extract the "important parts" from any response
		$keys = array( 'creditResponse', 'CreditResponse', );
		foreach ($keys as $key) {
			if (array_key_exists($key, $response_array)) {
				$response_array = Set::flatten($response_array[$key]);
			}
		}
		extract($response_array);
		$this->id = $transaction_id = (!empty($litleTxnId) ? $litleTxnId : 0);
		if (empty($transaction_id)) {
			$errors[] = "Missing transaction_id (litleTxnId)";
		}
		if ($response=="360") {
			$message = "Unable to credit transaction.  It may have been voided, or an incorrect ID.";
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

