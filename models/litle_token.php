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
		);
}
?>