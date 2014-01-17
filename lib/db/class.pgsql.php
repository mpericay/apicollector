<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// PostgreSQL database class interface
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
//******************************************************************************

if (eregi("class.pgsql.php",$_SERVER['PHP_SELF'])) die();

class database {

  var $db;
  var $resource;

  var $host = '';
  var $dbname = '';
  var $user = '';
  var $password = '';
  var $port = '5432';

  var $queries = 0;
  var $ready = false;
  
  var $autocommit = true;
  
  var $transactioninprogress = false;
  
  // the recordset returns associative array, numeric array or both?
  // associative array = 1
  // numeric array = 2
  // associative and numeric array = 3
  var $fetcharraymode = 3;

  // Constructor ***************************************************************

  //--------------------------------------------------------------------------//
  // database                                                                 //
  //--------------------------------------------------------------------------//
  // class constructor, accepts connection parameters                         //
  // returns nothing                                                          //
  // usage: $db = new database($host, $dbname, $uname, $pass, [$port]);       //
  //--------------------------------------------------------------------------//

  function database($host, $dbname, $user, $password, $port = '5432') {
    $this->host = $host;
    $this->dbname = $dbname;
    $this->user = $user;
    $this->password = $password;
    $this->port = $port;
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
    $this->close();
    
    $cs = "host=".$this->host
      ." dbname=".$this->dbname
      ." user=".$this->user
      ." password=".$this->password
      ." port=".$this->port;
    if ($this->db = @pg_connect($cs))
      $this->ready = (@pg_connection_status($this->db) == 0);
    return $this->ready;
  }

  //--------------------------------------------------------------------------//
  // close                                                                    //
  //--------------------------------------------------------------------------//
  // closes database connection                                               //
  // returns true if ok, false otherwise                                      //
  // usage: $db->close();                                                     //
  //--------------------------------------------------------------------------//

  function close() {
    if ($this->ready ) {
      $this->resource = null;
      $this->ready = false;
      return @pg_close($this->db);
    }
    return false;
  }

  function get_error_description() {
    if ($this->ready) return pg_last_error($this->db);
  }

  function get_error_number() {
    return 0;
  }

  function query($query) {
    if ($this->ready) {
    	if (!$this->autocommit && !$this->transactioninprogress) $this->_begin_transaction();
      if ($this->resource = @pg_query($this->db, $query)) {
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
    if ($this->ready) {
      if (@pg_result_status($this->resource))
        return @pg_last_oid($this->resource);
    }
    return false;
  }

  function affected_rows() {
    if ($this->ready) {
      if (@pg_result_status($this->resource))
        return @pg_affected_rows($this->resource);
    }
    return false;
  }

  function free_result($recordset) {
    if ($this->ready) return @pg_free_result($recordset);
    return false;
  }

  function seek($recordset, $row=0) {
    if ($this->ready) return @pg_result_seek($recordset, $row);
    return false;
  }

  function num_rows($recordset) {
    if ($this->ready) return @pg_num_rows($recordset);
    return false;
  }
  
  function num_fields($recordset) {
    if ($this->ready) return @pg_num_fields($recordset);
    return false;
  }

  function fetch_row($recordset, $row=-1) {
    if ($this->ready) {
      if ($row==-1) {
        if ($record = @pg_fetch_row($recordset)) return $record;
      } else {
        if ($record = @pg_fetch_row($recordset, $row)) return $record;
      }
    }
    return false;
  }

  function fetch_object($recordset) {
    if ($this->ready) {
      if($record =  @pg_fetch_object($recordset)) return $record;
    }
    return false;
  }

  function fetch_array($recordset) {
    if ($this->ready) {
      if ($record = @pg_fetch_array($recordset,null,$this->fetcharraymode)) return $record;
      
    }
    return false;
  }

  function free($recordset) {
    return $this->free_result($recordset);
  }

  function escape_data($string) {
    return pg_escape_string($this->db,$string);
  }
  
	function set_character_encoding($value){
		return @pg_set_client_encoding($this->db,$value);
	}

	function commit(){
		$this->transactioninprogress = false;
	  return @pg_query($this->db,"COMMIT");
	}
	
	function rollback(){
		$this->transactioninprogress = false;
	  return @pg_query($this->db,"ROLLBACK");
	}
	
	function _begin_transaction(){
		$this->transactioninprogress = (@pg_query($this->db,"BEGIN"));
		return $this->transactioninprogress;
		
	}
}




?>
