<?php

//******************************************************************************
//
// Copyright (c) 2009 by Geodata Sistemas S.L.
// http://www.geodata.es
//
// Microsoft Access database class interface
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
//******************************************************************************

class database {

  var $db;
  var $resource;

  var $dbname = '';
  var $user = '';
  var $password = '';

  var $queries = 0;
  var $provider = 'Provider=Microsoft.Jet.OLEDB.4.0';
  var $mode = 'Mode=ReadWrite';
  var $PSI = 'Persist Security Info=False';
  
  var $error_description = '';

  // Constructor ***************************************************************

  //--------------------------------------------------------------------------//
  // database                                                                 //
  //--------------------------------------------------------------------------//
  // class constructor, accepts connection parameters                         //
  // returns nothing                                                          //
  // usage: $db = new database($dbname, $uname, $pass);                       //
  //--------------------------------------------------------------------------//

  function database($dbname, $user='', $password='') {
    $this->dbname = realpath($dbname);
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
    
    // check dbname
    if (strlen($this->dbname)==0) {
      $error_description="File not found";
      return false;
    }
    
    // build connection string
    $cs = $this->provider.";Data Source=".$this->dbname.";".$this->mode.";".$this->PSI;

    // create ADODB object
    $this->db = new COM( 'ADODB.Connection' );
    if (!$this->db) {
      $error_description="Error creating ADODB object";
      return false;
    }
    
    // open conection
    $this->db->open($cs);
    if ($this->db->state != 1) {
      $error_description="Error opening connection ($cs)";
      return false;
    }

    return true;
          
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

  function get_error_description() {
    return $error_description;
  }

  function get_error_number() {
    return false; // TODO
  }

  function query($query) {

    // check connection
    if ($this->_check_db()) {
      
      // clear previous errors
      $this->db->errors->clear();
      
      if (strpos(strtoupper($query), "SELECT") === false) {
      
        // execute
        $this->resource = $this->db->execute($query);
        $this->queries++;
        return $this->resource;
      
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

  function get_id() {
    return false; // TODO
  }

  function affected_rows() {
    if ($this->_check_db()) return $this->recordsaffected;
    return false;
  }

  function free_result($recordset) {
    if ($this->_check_db()) {
      @$recordset->close();
      $recordset = null;
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

  function num_rows($recordset) {
    if ($this->_check_db()) return $recordset->recordcount();
    return false;
  }
  
  function num_fields($recordset) {
    if ($this->_check_db()) return $recordset->fields->count();
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
