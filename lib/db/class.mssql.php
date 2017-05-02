<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// Microsoft SQL Server database class interface
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

  // Constructor ***************************************************************

  //--------------------------------------------------------------------------//
  // database                                                                 //
  //--------------------------------------------------------------------------//
  // class constructor, accepts connection parameters                         //
  // returns nothing                                                          //
  // usage: $db = new database($host, $dbname, $uname, $pass, [$port]);       //
  //--------------------------------------------------------------------------//

  function database($host, $dbname, $user, $password, $port = '') {
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
    $cs = $this->host;
    if ($this->port) $cs .= (",".$this->port);
    if ($this->db = mssql_connect($cs, $this->user, $this->password))
      return $this->_check_db();
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
    if ($this->_check_db()) {
      $this->resource = null;
      return @mssql_close($this->db);
    }
    return false;
  }

  function get_error_description() {
    return mssql_get_last_message();
  }

  function get_error_number() {
    return 0;
  }

  function query($query) {
    if ($this->_check_db()) {
      if ($this->resource = @mssql_query($query, $this->db)) {
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
    $id = false;
    $rs = @mssql_query("SELECT @@identity AS id", $this->db);
    if ($row = mssql_fetch_row($rs)) $id = trim($row[0]);
    mssql_free_result($rs);
    return $id;
  }

  function affected_rows() {
    if ($this->_check_db())
      return @mssql_rows_affected($this->resource);
    return false;
  }

  function free_result($recordset) {
    if ($this->_check_db()) return @mssql_free_result($recordset);
    return false;
  }

  function seek($recordset, $row=0) {
    //if ($this->_check_db()) return @pg_result_seek($recordset, $row);
    return false;
  }

  function num_rows($recordset) {
    if ($this->_check_db()) return @mssql_num_rows($recordset);
    return false;
  }
  
  function num_fields($recordset) {
    if ($this->_check_db()) return @mssql_num_fields($recordset);
    return false;
  }

  function fetch_row($recordset, $row=-1) {
    if ($this->_check_db()) {
      if ($record = @mssql_fetch_row($recordset)) return $record;
    }
    return false;
  }

  function fetch_object($recordset) {
    if ($this->_check_db()) {
      if($record =  @mssql_fetch_object($recordset)) return $record;
    }
    return false;
  }

  function fetch_array($recordset) {
    if ($this->_check_db()) {
      if ($record = @mssql_fetch_array($recordset)) return $record;
    }
    return false;
  }

  function free($recordset) {
    return $this->free_result($recordset);
  }

  function escape_data($string) {
   $QuotePattern = "'";
   $QuoteReplace = "''";
   return(stripslashes(eregi_replace($QuotePattern, $QuoteReplace, $string)));
  }

	function set_character_encoding($value){
		return false;
	}

  // Private methods **************************************************

  function _check_db() {
    return (@mssql_select_db($this->dbname, $this->db) == 0);
  }

}

?>
