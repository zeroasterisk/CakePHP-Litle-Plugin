<?php
/**
* This is a simple logging model 
* it logs every request to a log file
* you can create your own logging model and set it in 
* config/litle_config.php ---> 'logModel' => 'NameOfYourModel'
*
* NOTE: this model is explicitly set as the log model for unit tests
*/
class LogToFile extends AppModel {
	public $name = 'LogToFile';
	public $useTable = false;
	public $logFileName = 'litle_transactions.log';
	public $_schema = array();
	/**
	* Defined in case you use a log model which is shared for other types of content
	* if this exists, we ignore save()
	* @param mixed $litleRequest
	* @param bool
	*
	function logLitleRequest($litleRequest=null) {
		return $this->save($litleRequest);
	}
	/*  */
	/**
	* Overwrite of the save() method
	* we're just logging to a file here
	* @param mixed $litleRequest
	* @param bool
	*/
	function save($litleRequest) {
		$filename = TMP.'logs'.DS.$this->logFileName;
		$text = date("Y-m-d H:i:s").' '.json_encode($litleRequest);
		return file_put_contents($filename, $text, FILE_APPEND);
	}
}
?>
