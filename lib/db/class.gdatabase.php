<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// Geodata generic database class interface
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
//******************************************************************************

class gDatabase {
	
	var $availableTypes = array("mysql",
															"oci",
															"pgsql",
															"odbc",
															"mssql",
															"mdb",
															"adodsn",
															"sqlite");

  var $db;
  var $resource;
	
	
	var $type = '';
  var $host = '';
  var $dbname = '';
  var $user = '';
  var $password = '';
  var $port = '';

  var $queries = 0;

	var $autocommit = true;
	
	var $fetcharraymode;
	
	var $arraykeyscase = false;
	
  // Constructor ***************************************************************

  //--------------------------------------------------------------------------//
  // database                                                                 //
  //--------------------------------------------------------------------------//
  // class constructor, accepts connection parameters                         //
  // returns nothing                                                          //
  // usage: $db = new database($type, $host, $dbname, $uname, $pass, [$port]); //
  //--------------------------------------------------------------------------//

  function gDatabase($type, $host, $dbname, $user, $password, $port = false) {
    if (!in_array($type,$this->availableTypes)) return false;
    $this->type = $type;
    
    //Include specific database interface
    require_once("class.".$this->type.".php");
		if (!class_exists("database")) return false;
		    
    $this->host = $host;
    $this->dbname = $dbname;
    $this->user = $user;
    $this->password = $password;
    $this->port = $port;
    
    $this->db = new database($this->host,$this->dbname,$this->user, $this->password, $this->port);
    
    
  }

  // Properties ****************************************************************

  // Methods *******************************************************************

  //--------------------------------------------------------------------------//
  // connect                                                                  //
  //--------------------------------------------------------------------------//
  // connects to host and database and selects table                          //
  // returns true if ok, false otherwise
  // Encoding parameter only used with the oracle (oci) plugin                              //
  //--------------------------------------------------------------------------//

  function connect($encoding = false) {
		if ($encoding){
			return $this->db->connect($encoding);
		} else {
			return $this->db->connect();
		}
  }
  
  //--------------------------------------------------------------------------//
  // close                                                                    //
  //--------------------------------------------------------------------------//
  // closes database connection                                               //
  // returns true if ok, false otherwise                                      //
  // usage: $db->close();                                                     //
  //--------------------------------------------------------------------------//

  function close() {
		return $this->db->close();
  }
  
  function get_error_description() {
		return $this->db->get_error_description();
  }

  function get_error_number() {
		return $this->db->get_error_number();
  }
	
	function set_autocommit($value){
		if (isset($this->db->autocommit)){
			$this->db->autocommit = $value;
			$this->autocommit = $value;
		}
	}

	function set_fetcharraymode($value){
		if (isset($this->db->fetcharraymode)){
			$this->db->fetcharraymode = $value;
			$this->fetcharraymode = $value;
		}
	}
	
	function set_arraykeyscase($case){
		if ($case != CASE_UPPER && $case != CASE_LOWER) return false;
		$this->arraykeyscase = $case;
	}
	
	function set_character_encoding($value){
		return $this->db->set_character_encoding($value);
	}
	
  function query($query) {
		$result = $this->db->query($query);
		if ($result) $this->queries = $this->db->queries;
		return $result;
	}

  /**
  * Executes the SQL statement provided
  * @param object $db Database object
  * @param string $sql SQL query
  * @param boolean $getRecords Return records if found, default is false
  * @return array|boolean If getRecords is true, array with records data if found, 0 if no records where found or false if errors occurred, If getRecords is false, true if execution was succesful, false if errors where found
  */
  function executeSQL($sql,$getRecords = false){
		$result = $this->db->executeSQL($sql,$getRecords);

		if ($result && $getRecords && $this->arraykeyscase !== false){

			$result = $this->array_change_key_case_recursive($result, $this->arraykeyscase);
		}
		
		return $result;
  }


  function array_change_key_case_recursive($input, $case = CASE_LOWER){
      if(!is_array($input)) return false;

      if(!in_array($case, array(CASE_UPPER, CASE_LOWER))) return false;

      $input = array_change_key_case($input, $case);

      foreach($input as $key => $array){
          if(is_array($array)){
              $input[$key] = $this->array_change_key_case_recursive($array, $case);
          }
      }
      return $input;
  }


  function get_id() {
		return $this->db->get_id();
  }
  
  function affected_rows() {
		return $this->db->affected_rows();
  }

  function free_result($stmt) {
		return $this->db->free_result($stmt);
  }

  function seek($stmt, $row=0) {
		return $this->db->seek($stmt,$row);
  }

  function num_rows($stmt) {
    return $this->db->num_rows($stmt);
  }
  
  function num_fields($stmt) {
		return $this->db->num_fields($stmt);
  }

  function fetch_row($stmt, $row=-1) {
		return $this->db->fetch_row($stmt, $row);
  }
  
  function fetch_object($stmt) {
		return $this->db->fetch_object($stmt);
  }
  
  function fetch_array($stmt) {
		return $this->db->fetch_array($stmt);
  }
  
  function free($stmt) {
		return $this->db->free($stmt);
  }

  function escape_data($string) {
		return $this->db->escape_data($string);
  }

	function transactioninprogress(){
		return $this->db->transactioninprogress;
	}
	
	function commit(){
		return $this->db->commit();
	}
	
	function rollback(){
		return $this->db->rollback();
	}

  // Private methods **************************************************

  function _check_db() {
		return $this->db->_check_db();
  }

}

?>
