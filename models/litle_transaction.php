<?php
/**
* Plugin model for "Litle Credit Card Transaction Processing".
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link http://zeroasterisk.com
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*/
class LitleTransaction extends LitleAppModel {
	/**
	* The name of this model
	* @var name
	*/
	public $name ='LitleTransaction';
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
}
?>
