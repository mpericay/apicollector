<?php
/** 
* Log class
* @details &copy;2008 - Geodata Sistemas SL
* @file class.logger.php
* @version 0.1
*/

/**
*	This class creates or updates a log
* @class logger
*/
class logger {

	/**
	*	Log file
	* @details string
	*/
	private $file = "";

	/**
	*	Permissions used
	* @details string
	*/
	public $permissions = "a+";

	/**
	*	Add time by default
	* @details boolean
	*/
	public $addTime = true;

	/**
	*	Default time format
	* @details string
	*/
	private $timeFormat = "Y-m-d H:i:s";

	/**
	*	Logger constructor
	* @constructor
	*/
	public function __construct(){

	}

	/**
	*	Logger destructor
	* @destructor
	*/
	public function __destruct(){

	}

	/**
	*	Logger initializer
	* @param string $file Log file
	* @returns boolean True if necessary permissions exist, false otherwise
	*/
	public function init($file){
		$permission = $this->checkPermissions($file, $this->permissions);
		if ($permission) {
			$this->file = $file;
		}
		return $permission;
	}	

	/**
	*	Set time format
	* @param string $file PHP time format
	* @returns string New time format
	*/
	public function setTimeFormat($format){
		$this->timeFormat($format);

		return $this->timeFormat;
	}	
	

	/**
	*	Adds a string to the current log
	* @param string $text Text to add
	* @returns boolean True if all went right, false otherwise
	*/
	public function add($text){
		if ($this->file){
			if ($file = @fopen($this->file,$this->permissions)){
				if ($this->addTime){
					$text = date($this->timeFormat,time()).": ".$text;
				}
				
				$text .= "\n";
				fwrite($file,$text);
				fclose($file);
				return true;
			}			
		}
		return false;
	}

	/**
	*	Clears the current log
	* @returns nothing
	*/
	public function clear(){
		return file_put_contents($this->file,"");
	}
	
	/**
	*	Checks if the PHP user (daemon) has necessary permissions to create and modify the log file
	* @param string $file Log file	
	* @returns boolean True if necessary permissions exist, false otherwise
	*/
	private function checkPermissions($file,$permissions){
		if ($fileopen = @fopen($file,$permissions)){
			fclose($fileopen);
			return true;
		}
		return false;
	}	
}




?>