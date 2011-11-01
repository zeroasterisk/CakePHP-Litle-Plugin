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
	* @param int $account_number
	* @return int $token
	*/
	public function register($account_number) {
		die('WIP');
		return $token;
	}
}
?>