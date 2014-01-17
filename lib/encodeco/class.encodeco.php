<?
	
//*********************************************************************
//
// ============================================
//
// Copyright (c) 2008 by Geodata Sistemas S.L.
// http://www.geodata.es
// Written by Adrià Mercader & Arturo Bandini
//
//
// PHP classes for encoding / decoding strings
//
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
// History:
//	* v 0.1 24/01/2008 - Initial version
//	* v 0.2 18/03/2008 - Corrected bug in encodeArray
//										 - Added mode funcionality (Current modes: url, utf8. Default is url)	
//*************************************************************************

/**
*	This class provides functions for encoding strings
* @class phpEncoder
*/
class phpEncoder {

	/**	Encoding mode
	* @var string
	* @access public
	*/
	var $mode = "url";
	
	/**
	*	phpEncoder constructor
	* @constructor
	*/	
	function phpEncoder(){
		
	}

	/**
	*	Encodes the provided text
	* @returns $output Encoded text (if text)
	* @type variable
	*/
	function encode($text) {
	  $output = $text;
	  if (is_string($text)){
	  	switch ($this->mode){
	  		case "url":
			  	$output = urlencode($text);
			  	$output = str_replace("+"," ",$output);
	  		break;
	  		case "utf8":
	  			$output = utf8_encode($text);
	  		break;
	  		default:
	  		break;
	  	}
	  }
	  return $output;
	}

	/**
	*	Encodes the provided array elements, recursively
	* @returns $array Encoded array
	* @see encode(),encodeArray()
	* @type array
	*/	
	function encodeArray($array){
		if (gettype($array) == "array") {
			foreach ($array as $key => $value){
				if (gettype($array[$key]) == "object") {
					$array[$key] = $this->encodeObject($array[$key]);
				} else if (gettype($array[$key]) == "array") {
					$array[$key] = $this->encodeArray($array[$key]);
				} else {
					$array[$key] = $this->encode($array[$key]);
				}
			}
		} else {
			$array = $this->encode($array);
		}
		return $array;
	}

	/**
	*	Encodes the provided object members, recursively
	* @returns $object Encoded object
	* @see encode(),encodeObject()
	* @type object
	*/	
	function encodeObject($object){
		if (gettype($object) == "object") {
			foreach ($object as $key => $value){
				if (gettype($object->$key) == "object") {
					$object->$key = $this->encodeObject($object->$key);
				} else if (gettype($object->$key) == "array"){
					$object->$key = $this->encodeArray($object->$key);
				} else {
					$object->$key = $this->encode($object->$key);
				}
			}
		} else {
			$object = $this->encode($object);
		}
		return $object;
	}

}

/**
*	This class provides functions for decoding strings
* @class phpDecoder
*/
class phpDecoder {
	
	/**	Encoding mode
	* @var string
	* @access public
	*/
	var $mode = "url";
	
	/**
	*	phpDecoder constructor
	* @constructor
	*/		
	function phpDecoder(){
		
	}

	/**
	*	Decodes the provided text
	* @returns $output Decoded text (if text)
	* @type variable
	*/
	function decode($text) {
	  $output = $text;
	  if (is_string($text)){
	  	switch ($this->mode){
	  		case "url":
					$output = urldecode($text);
	  		break;
	  		case "utf8":
	  			$output = utf8_decode($text);
	  		break;
	  		default:
	  		break;
	  	}
	  }
	  return $output;
	}

	/**
	*	Decodes the provided array elements, recursively
	* @returns $array Decoded array
	* @see decode(),decodeArray()
	* @type array
	*/	
	function decodeArray($array){
		if (gettype($array) == "array") {
			foreach ($array as $key => $value){
				if (gettype($array[$key]) == "array") {
					$array[$key] = $this->decodeArray($array[$key]);
				} else if (gettype($array[$key]) == "object") {
					$array[$key] = $this->decodeObject($array[$key]);
				} else {
					$array[$key] = $this->decode($array[$key]);
				}
			}
		} else {
			$array = $this->decode($array);
		}
		return $array;
	}	

	/**
	*	Decodes the provided object members, recursively
	* @returns $object Decoded object
	* @see decode(),decodeObject()
	* @type object
	*/	
	function decodeObject($object){
		if (gettype($object) == "object") {
			foreach ($object as $key => $value){
				if (gettype($object->$key) == "object") {
					$object->$key = $this->decodeObject($object->$key);
				} else if (gettype($object->$key) == "array"){
				$object->$key = $this->decodeArray($object->$key);
				} else {
					$object->$key = $this->decode($object->$key);
				}
			}
		} else {
			$object = $this->decode($object);
		}
		return $object;
	}

}





	
?>