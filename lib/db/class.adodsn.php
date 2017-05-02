<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// ADO database class interface
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
//******************************************************************************

class database {

  var $db;
  var $resource;

  var $host = ''; // This must be a DSN name!!!
  var $dbname = '';
  var $user = '';
  var $password = '';
  var $port = '';

  var $queries = 0;
  var $recordsaffected = false;

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
    $cs = ";DATABASE=".$this->dbname
      .";UID=".$this->user
      .";PWD=".$this->password
      .";DSN=".$this->host;
    if ($this->db = new COM( 'ADODB.Connection' )) {
      $this->db->open($cs);
      return $this->_check_db();
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
    if ($this->resource) {
      @$this->resource->close();
      $this->resource = null;
    }
    if ($this->db) {
      @$this->db->close();
      $this->db = null;
    }
    return true;
  }

  function get_error_description() { // TODO
//    if ($this->_check_db()) {
//      for ($i=1; $i<=$this->db->errors->count; $i++)
//        $description .= ($this->db->errors->item[$i]->description." \n\r");
//      return $description;
//    }
    return false;
  }

  function get_error_number() { // TODO
//    if ($this->_check_db()) {
//      $error = $this->db->errors->item[$this->db->errors->count-1];
//      return $error->number;
//    }
    return false;
  }

  function query($query) {

    // check connection
    if ($this->_check_db()) {
      
      // clear previous errors
      $this->db->errors->clear();

      if (strpos(strtoupper($query), "SELECT") === false) {
      
        // execute
        $this->db->execute($query);
        $this->queries++;
        return true;
      
      } else {
      
        // create recordset object
        $this->resource = new COM( 'ADODB.Recordset' );
        $this->resource->activeConnection = $this->db;
        $this->resource->cursortype = 1;
        $this->resource->locktype = 1;
        $this->resource->source = $query;
        $this->resource->open;
      
        // check state
        if ($this->resource->state == 1) {
          $this->queries++;
          return $this->resource;
        }
     
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

  function get_id() { // TODO
    return false;
  }

  function affected_rows() {
    if ($this->_check_db()) return $this->recordsaffected;
  }

  function free_result($recordset) {
    if ($this->_check_db()) {
      @$recordset->close();
      $recordset=null;
    }
    return true;
  }

  function seek($recordset, $row=0) {
    if ($this->_check_db()) {
      $recordset->move($row, 1);
      return true;
    }
    return false;
  }

  function num_rows($recordset) { // TODO
    if ($this->_check_db())
      return $recordset->recordcount();
    return false;
  }
  
  function num_fields($recordset) {
    if ($this->_check_db())
      return $recordset->fields->count();
    return false;
  }

  function fetch_row($recordset, $row=-1) {
    if ($this->_check_db()) {
      if (!$recordset->eof) {
        $record = Array();
        for ($i=0; $i < $recordset->fields->count(); $i++) {
          $record[] = chop($recordset->fields[$i]->value);
        }
        $recordset->movenext();
        return $record;
      }
    }
    return false;
  }

  function fetch_object($recordset) {
    if ($this->_check_db()) {
      if (!$recordset->eof) {
        for ($i=0; $i < $recordset->fields->count(); $i++) {
          $name = strtolower($recordset->fields[$i]->name);
          $object->$name = chop($recordset->fields[$i]->value);
        }
        $recordset->movenext();
        return $object;
      }
    }
    return false;
  }

  function fetch_array($recordset) {
    if ($this->_check_db()) {
      if (!$recordset->eof) {
        $record = Array();
        for ($i=0; $i < $recordset->fields->count(); $i++) {
          $name = strtolower($recordset->fields[$i]->name);
          $record[$name] = chop($recordset->fields[$i]->value);
        }
        $recordset->movenext();
        return $record;
      }
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
    if ($this->db) return ($this->db->state == 1);
    return false;
  }

}

?>
