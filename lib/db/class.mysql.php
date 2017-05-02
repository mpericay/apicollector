<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// MySQL database class interface
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
//******************************************************************************

class database {

  var $db;
  var $resource;

  var $host = '';
  var $dbname = '';
  var $user = '';
  var $password = '';

  var $queries = 0;
  
  var $autocommit = true;
  
	var $transactioninprogress = false;
	
  // Constructor ***************************************************************

  //--------------------------------------------------------------------------//
  // database                                                                 //
  //--------------------------------------------------------------------------//
  // class constructor, accepts connection parameters                         //
  // returns nothing                                                          //
  // usage: $db = new database($host, $dbname, $uname, $pass);                //
  //--------------------------------------------------------------------------//

  function database($host, $dbname, $user, $password) {
    $this->host = $host;
    $this->dbname = $dbname;
    $this->user = $user;
    $this->password = $password;
  }

  // Properties ****************************************************************

  // Methods *******************************************************************

  //--------------------------------------------------------------------------//
  // connect                                                                  //
  //--------------------------------------------------------------------------//
  // connects to host and database and selects table                          //
  // returns true if ok, false otherwise                                      //
  // usage: if (!$db->connect()) die("Error connecting to database");         //
  //--------------------------------------------------------------------------//

  function connect() {
    if ($this->db = @mysql_connect($this->host, $this->user, $this->password)) return $this->_check_db();
    return false;
  }
  
  //--------------------------------------------------------------------------//
  // close                                                                    //
  //--------------------------------------------------------------------------//
  // closes database connection                                               //
  // returns true if ok, false otherwise                                      //
  // usage: $db->close();                                                     //
  //--------------------------------------------------------------------------//

  function close() {
    if ($this->_check_db()) return mysql_close($this->db);
    return false;
  }
  
  function get_error_description() {
    return mysql_error($this->db);
  }

  function get_error_number() {
    return mysql_errno($this->db);
  }

  function query($query) {
    if ($this->_check_db()) {
	      if (!$this->autocommit && !$this->transactioninprogress) $this->_begin_transaction();
	      if ($this->resource = @mysql_query($query, $this->db)) {
	        $this->queries++;
	        return $this->resource;
	      }
	    
    }
    return false;
  }

  /**
  * Executes the SQL statement provided
  * @param object $db Database object
  * @param string $sql SQL query
  * @param boolean $getRecords Return records if found, default is false
  * @return array|boolean If getRecords is true, array with records data if found, 0 if no records where found or false if errors occurred, If getRecords is false, true if execution was succesful, false if errors where found
  */
  function executeSQL($sql,$getRecords = false){
    if ($rs = $this->query($sql)) {
      if ($getRecords){
        $records = array();
        while ($record = $this->fetch_array($rs)) {
          $records[] = $record;
        }
        $out = (count($records)) ? $records : 0;
      } else {
        $out = true;
      }
      $this->free($rs);  
    } else {
      $out = false;
    }
    return $out;
  }

  function get_id() {
    return  @mysql_insert_id($this->db);
  }
  
  function affected_rows() {
    if ($this->_check_db()) return mysql_affected_rows();
    return false;
  }

  function free_result($recordset) {
    if ($this->_check_db()) return @mysql_free_result($recordset);
    return false;
  }

  function seek($recordset, $row=0) {
    if ($this->_check_db()) return @mysql_data_seek($recordset, $row);
    return false;
  }

  function num_rows($recordset) {
    if ($this->_check_db()) return mysql_num_rows($recordset);
    return false;
  }
  
  function num_fields($recordset) {
    if ($this->_check_db()) return mysql_num_fields($recordset);
    return false;
  }

  function fetch_row($recordset, $row=-1) {
    if ($this->_check_db()) {
      if ($record = mysql_fetch_row($recordset)) return $record;
    }
    return false;
  }
  
  function fetch_object($recordset) {
    if ($this->_check_db()) {
      if($record =  mysql_fetch_object($recordset)) return $record;
    }
    return false;
  }
  
  function fetch_array($recordset) {
    if ($this->_check_db()) {
      if ($record = @mysql_fetch_array($recordset)) return $record;
    }
    return false;
  }
  
  function free($recordset) {
    return $this->free_result($recordset);
  }

  function escape_data($string) {
   	return mysql_real_escape_string($string, $this->db);
  }
	
	function commit(){
		$this->transactioninprogress = false;
	  return @mysql_query("COMMIT",$this->db);
	}
	
	function rollback(){
		$this->transactioninprogress = false;
	  return @mysql_query("ROLLBACK",$this->db);
	}



  // Private methods **************************************************

	function _begin_transaction(){
		$this->transactioninprogress = (@mysql_query("START TRANSACTION",$this->db) && @mysql_query("BEGIN",$this->db));
		return $this->transactioninprogress;
		
	}

  function _check_db() {
    return mysql_select_db($this->dbname);
  }

	function set_character_encoding($value){
		return @mysql_set_charset($value,$this->db);
	}

}

?>
