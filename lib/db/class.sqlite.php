<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// SQLite database class interface
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
  var $port = '';

  var $queries = 0;
  var $ready = false;
  
  // the recordset returns associative array, numeric array or both?
  // associative array = 1
  // numeric array = 2
  // associative and numeric array = 3
  var $fetcharraymode = null;

  // Constructor ***************************************************************

  //--------------------------------------------------------------------------//
  // database                                                                 //
  //--------------------------------------------------------------------------//
  // class constructor, accepts connection parameters                         //
  // returns nothing                                                          //
  // usage: $db = new database($host, $dbname, $uname, $pass, [$port]);       //
  //--------------------------------------------------------------------------//

  function database($host, $dbname = false, $user = false, $password = false, $port = false) {
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
    
    $cs = "sqlite:".$this->host;
		
		try{
			$this->db = new PDO($cs);
			$this->ready = true;
		} catch (PDOException $e) {
			$this->ready = false;
		}
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
    }
    return false;
  }

  function get_error_description() {
    if ($this->ready) {
    	return $this->db->errorInfo();
    } else {
    	return "Database not ready (check file path)";
    }
  }

  function get_error_number() {
    if ($this->ready) return $this->db->errorCode();
  }

  function query($query) {
    if ($this->ready) {
      if ($this->resource = @$this->db->query($query)) {
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
        return @$this->lastInsertId($this->resource);
    }
    return false;
  }

  function affected_rows() {
    if ($this->ready) {
        return @$this->resource->rowCount();
    }
    return false;
  }

  function free_result($recordset) {
    if ($this->ready) return @$recordset->closeCursor();
    return false;
  }

  function seek($recordset, $row=0) {
    return false;
  }

  function num_rows($recordset) {
    if ($this->ready) return @$recordset->rowCount();
    return false;
  }
  
  function num_fields($recordset) {
    if ($this->ready) return @$recordset->columnCount();
    return false;
  }

  function fetch_row($recordset, $row=-1) {
    if ($this->ready) {
        if ($record = @$recordset->fetch($this->fetcharraymode)) return $record;
    }
    return false;
  }

  function fetch_object($recordset) {
    if ($this->ready) {
      if($record =  @$recordset->fetchObject()) return $record;
    }
    return false;
  }

  function fetch_array($recordset) {
    if ($this->ready) {
      if ($record = @$recordset->fetch($this->fetcharraymode)) return $record;
      
    }
    return false;
  }

  function free($recordset) {
  	return @$recordset->closeCursor();
  }

  function escape_data($string) {
    return false;
  }

}

?>
