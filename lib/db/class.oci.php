<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// Oracle database class interface
// Requieres PHP 5.0.0 or higher
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
  
  var $fetcharraymode = 3;
	
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

  function connect($encoding = false) {
    if ($this->dbname) {
      $cs = "//".$this->host."/".$this->dbname;
    } else {
      $cs=$this->host;
    }
    if ($encoding){
    	if ($this->db = @oci_connect($this->user, $this->password, $cs)) return $this->_check_db();
    } else {
    	if ($this->db = @oci_connect($this->user, $this->password, $cs, $encoding)) return $this->_check_db();
    }
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
    if ($this->_check_db()) return @oci_close($this->db);
    return false;
  }
  
  function get_error_description() {
    $e = oci_error($this->db);
    return $e['message'];
  }

  function get_error_number() {
    $e = oci_error($this->db);
    return $e['code'];
  }

  function query($query) {
    if ($this->_check_db()) {
    	$stmt = @oci_parse($this->db, $query);
    	if ($stmt!==false) {
    		if ($this->autocommit){
    			$mode = OCI_COMMIT_ON_SUCCESS;
    		} else {
    			$mode = OCI_DEFAULT;
    			$this->transactioninprogress = true;
    		}
    		
    		
    		if (@oci_execute($stmt,$mode)) {
    			$this->resource = $stmt;
        	$this->queries++;
        	return $this->resource;
      	}
      }
    	return false;
  	}
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
    return false;
  }
  
  function affected_rows() {
    if ($this->_check_db()) return oci_num_rows($this->resource);
    return false;
  }

  function free_result($stmt) {
    if ($this->_check_db()) return @oci_free_statement($stmt);
    return false;
  }

  function seek($stmt, $row=0) {
    return false;
  }

  function num_rows($stmt) {
    return $this->affected_rows($stmt);
  }
  
  function num_fields($stmt) {
    if ($this->_check_db()) return oci_num_fields($stmt);
    return false;
  }

  function fetch_row($stmt, $row=-1) {
    if ($this->_check_db()) {
      if ($record = @oci_fetch_row($stmt)) return $record;
    }
    return false;
  }
  
  function fetch_object($stmt) {
    if ($this->_check_db()) {
      if($record =  @oci_fetch_object($stmt)) return $record;
    }
    return false;
  }
  
  function fetch_array($stmt) {
    if ($this->_check_db()) {
      if ($record = @oci_fetch_array($stmt, $this->fetcharraymode)) return $record;
    }
    return false;
  }
  
  function free($stmt) {
    return $this->free_result($stmt);
  }

  function escape_data($string) {
    return $string;
  }

	function commit(){
		$this->transactioninprogress = false;
	  return @oci_commit($this->db);
	}
	
	function rollback(){
		$this->transactioninprogress = false;
	  return @oci_rollback($this->db);
	}

  // Private methods **************************************************

  function _check_db() {
    return ($this->db !== false);
  }

}

?>
