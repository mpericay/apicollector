<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// ODBC database class interface
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
//******************************************************************************

if (eregi("class.odbc.php",$_SERVER['PHP_SELF'])) die();

class database {

  var $db;
  var $resource;

  var $host = ''; // This must be a DSN name!!!
  var $dbname = ''; //Leave this blank
  var $user = '';
  var $password = '';

  var $queries = 0;

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
    if ($this->db = @odbc_connect($this->host, $this->user, $this->password)) return true;
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
    return odbc_close($this->db);
  }
  
  function get_error_description() {
    return odbc_error($this->db);
  }

  function get_error_number() {
    return odbc_errormsg($this->db);
  }

  function query($query) {
      if ($this->resource = odbc_exec($this->db,$query)) {
        $this->queries++;
        return $this->resource;
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
    $id = false;
    $rs = @odbc_exec("SELECT @@identity AS id", $this->db);
    if ($row = odbc_fetch_row($rs)) $id = trim($row[0]);
    odbc_free_result($rs);
    return $id;
  }


  function affected_rows() {
    return odbc_num_rows($this->resource);
  }

  function free_result($recordset) {
    return @odbc_free_result($recordset);
  }

  function seek($recordset, $row=0) {
    //if ($this->_check_db()) return @mysql_data_seek($recordset, $row);
    return false;
  }

  function num_rows($recordset) {
    return odbc_num_rows($recordset);
  }
  
  function num_fields($recordset) {
    return odbc_num_fields($recordset);
  }

  function fetch_row($recordset, $row=-1) {
    if ($record = odbc_fetch_row($recordset)) return $record;
  }
  
  function fetch_object($recordset) {
      if($record =  odbc_fetch_object($recordset)) return $record;
  }
  
  function fetch_array($recordset) {
      if ($record = @odbc_fetch_array($recordset)) return $record;
  }
  
  function free($recordset) {
    return $this->free_result($recordset);
  }

  function escape_data($string) {
		return false;
//    if (get_magic_quotes_gpc()==0) {
//      return mysql_real_escape_string($string, $this->db);
//    } else {
//      return $string;
//    }
  }

	function set_character_encoding($value){
		return false;
	}

  // Private methods **************************************************
/*
  function _check_db() {
    return mysql_select_db($this->dbname);
  }
*/
}

?>
