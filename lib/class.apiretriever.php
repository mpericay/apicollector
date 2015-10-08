<?php
require_once("lib/logger/class.logger.php");
require_once("lib/db/class.gdatabase.php");
require_once("lib/encodeco/class.encodeco.php");
require_once("conf.php");

class apiretriever {

    public $ready = false;
    private $profile = false;
    private $mode = false;
    private $format = false;
    private $result = false;
    private $debug = false;
    private $log = null;
    private $db = null; 
    private $config = false;
    private $totalQueries = 0;
    private $found = 0;
    private $notfound = 0;
    private $errorsDB = 0;    
    private $errors = 0;
	private $empty_queries = Array();
    private $error_strings = Array();

    /**
     * Street search object constructor
     * @constructor
     * @return boolean True if handlers were found, false otherwise
     */
    public function __construct($profile = false) {

        //Debug mode?
        $this->debug = (strtolower($this->getParameter("DEBUG")) == "true");

        //If no profile provided, get it from the request parameters
        $this->profile = $this->getParameter("PROFILE");
        if (!$this->profile) {
            $this->logError("apicollector::__construct - Parameter missing [PROFILE]");
        }

        //Set logger object
        $this->log = $this->setLoggerObject();

        //Get profile configuration
        $this->getConfig();
        if (!$this->config) {
            $this->logError("apicollector::__construct - Could not get configuration for this profile [".$this->profile."]");
            return false;
        }

        //Set DB object
        $this->setDBObject();
        if (!$this->db) {
            $this->logError("apicollector::__construct - Could not set DB connection");
            return false;
        }
        
        $this->ready = true;

    }

    /**
     * Street search object destroyer
     * @destructor
     * @return nothing
     */
    public function __destruct() {
        if ($this->db) $this->db->close();
    }

    private function getConfig() {
        // get config from conf.php
    	$this->config["dbtype"] = _DB_TYPE; 
        $this->config["dbhost"] = _DB_HOST; 
        $this->config["dbname"] = _DB_NAME;
        $this->config["dbuser"] = _DB_USER;
        $this->config["dbpass"] = _DB_PWD;
        $this->config["dbport"] = _DB_PORT;
		
		$filename = "lib/conf/plugin.".$this->profile.".php";
		
		if(file_exists($filename)) require_once($filename);
		else die("Plugin ".$this->profile." does not exist");

        return true;
    }


    private function setDBObject() {

        if (!$this->config) return false;
        $config = $this->config;
        $db = new gDatabase(
            $config["dbtype"],
            $config["dbhost"],
            $config["dbname"],
            $config["dbuser"],
            $config["dbpass"],
            $config["dbport"]
        );

        if (!$db->connect()) {
            return false;
        }
        $this->db = $db;

        //Return associative arrays only
        $this->db->set_fetcharraymode(1);

        return true;

    }


    private function setLoggerObject() {
        $log = new logger();
        if (!$log->init(_APICOLLECTOR_LOG_FILE)) {
            return false;
        }
        return $log;
    }


    private function logError($msg) {
        if ($this->log) {
            $this->log->add($msg);
        }
        
        echo $msg;

        $this->__destruct();
        $this->result = array("error" => 1, "msg" => $msg);
        $this->output = $this->getResultJSON();
        $this->outputResults();
        die();
    }
    
	private function logMsg($msg) {
		if ($this->debug) {
	        if ($this->log) {
	            $this->log->add($msg);
	    	}
		}
	}

    private function getParameter($name, $default = false, $from = false) {
        if ($from === false) $from = $_REQUEST;
        reset($from);
        while (list($key, $value) = each($from)) {
            if (strcasecmp($key, $name) == 0) return $value;
        }
        return $default;
    }

    private function executeSQL($sql,$getRecords) {
        if ($rs = $this->db->query($sql)) {
            if ($getRecords) {
                $records = array();
                while ($record = $this->db->fetch_array($rs)) {
                    $records[] = $record;
                }
                $out = (count($records)) ? $records : 0;
            } else {
                $out = true;
            }
            $this->db->free($rs);
        } else {
            $out = false;
        }
        return $out;
    }


    public function handle() {
        
        if (!$this->ready) {
            $this->logError("apicollector::handle - Service not ready");
            return false;
        }
        
        //default limit is only 1 record
        $limitParam = $this->getParameter("LIMIT");
        $limit = $limitParam ? $limitParam : 1;
        
        $nullParam = $this->getParameter("ONLYNULL");
		$onlynull = $nullParam ? $nullParam : true;
        $names = $this->getNames($limit, $onlynull);
        
        for($i=0; $i<count($names); $i++) {
        	
    		$this->sleep($this->getParameter("SLEEP"));
    		        	
	        $data = $this->getJson($names[$i]);
	        
	        switch($this->profile) {
	        	case "gni_detail":
	        		$results = $this->parseGniResourceJson($data);
	        	break;
	        	case "gbif":
	        		$results = $this->parseGbifNubJson($data);
	        	break;	  
	        	case "mapquest":
	        		$results = $this->parseMapQuestJson($data);
	        	break;
	        	case "opencage":
	        		$results = $this->parseOpenCageJson($data);
	        	break;	
	        	case "google":
	        		$results = $this->parseGoogleJson($data);
	        	break;      	
	        	case "gni":
	        	default:
	        		$results = $this->parseGniJson($data);
	        	break;
	        }
	    
        	$inserted = $this->insertResults($results, $names[$i]);
        } 
        
        $this->drawResults();
    }
    
    private function sleep($sleepParam) {
    	// Default sleep is 1 second
        $sleep = ($sleepParam !== false) ? (int) $sleepParam/1000 : 1;
    	sleep($sleep);
    } 

    private function getNames($limit, $onlynull) {
        //Build SQL
        $sql = "SELECT DISTINCT " . implode(",", $this->config["queryfield"]) . " FROM " . $this->config["dbtable"];
        //only first element of the array musn't be null or empty
        $sql .= " WHERE ". $this->config["queryfield"][0] . " IS NOT NULL";
		$sql .= " AND ". $this->config["queryfield"][0] . " != ''";
        if($onlynull) $sql .= " AND ". $this->config["updatefield"] . " IS NULL";
        if($limit) $sql .= " LIMIT ".$limit;

        $names = $this->executeSQL($sql,true);
        
        if(!$names) die("No records match can be processed by ".$this->profile. ". Does the field '". $this->config["queryfield"][0] . "' exist or is always empty? Does the field '". $this->config["updatefield"] . "' exist or is always full?");
        else $this->totalQueries = count($names);
        
        $this->logMsg("Queries to do: ".$this->totalQueries);

        return $names;
    }


    private function getJson($name) {
    	
    	$mainfield = $this->config["queryfield"][0];
    	
    	if($name[$mainfield] != '') {
	    		
	    	$search = $this->config["urlpattern"];
	    	//we substitute every [field] in pattern for its value
	    	for($i = 0; $i < count($this->config["queryfield"]); $i++) {
		    	$field = $this->config["queryfield"][$i];
		    	//does it need to be encoded?
		    	$value = $this->config["queryfieldencode"] ? urlencode(utf8_encode ($name[$field])) : $name[$field];
		    	//search_term=exact:[scientific_name] becomes search_term=exact:Abida+secale
		    	$search = str_replace("[".$field."]", $value, $search);
	    	}
			
			$this->logMsg($search);
	    	
	    	//provisional hack: in gni_detail, we use json format, not xml
	    	$search = str_replace(".xml", ".json", $search);

	    	$content = file_get_contents($search);

			//log errors or log everything (if DEBUG true)
	    	if($content === false) {
	        	$this->errors ++;
	        	$this->empty_queries[] = $search;
	        	$this->logMsg("Empty query!!! ".$search);
	        } else {
	        	//hack! this is not a json!
	        	if($this->profile == "mapquest") $content = substr($content,14,-1);
	        	
	        	$data = json_decode($content);
	        	$this->logMsg("Query OK: ".$search);
	        }
    	}
    	
        return $data;
    }


    public function parseGniJson($json) {
    	
    	if($json->name_strings_total) {
    		$values ['hits'] = $json -> name_strings_total;
    		$results = $json -> name_strings;
    		$values ['resource_uri'] = $results[0]->resource_uri;
    		//$values ['id'] = false; 
    	} else {
    		$values = 0;
    	}

    	return $values;
    }
    
	public function parseGoogleJson($json) {

    	if($json->status == "OK") {
    		$values ['hits'] = 1;
    		$results = $json -> results;
    		$first = $results[0]->geometry;
    		$values ['lat'] = $first->location->lat;
    		$values ['lon'] = $first->location->lng;
			if($first->bounds) {
				$values ['ne_lat'] = $first->bounds->northeast->lat;
				$values ['ne_lon'] = $first->bounds->northeast->lng;
				$values ['sw_lat'] = $first->bounds->southwest->lat;
				$values ['sw_lon'] = $first->bounds->southwest->lng;
			}
			if($first->location_type) $values ['location_type'] = $first->location_type;
    	} else {
    		$values['error'] = $json->status;
    		$this->logMsg("Error returned by Google API: ".$json->status);
    	}

    	return $values;
    }
	   
    public function parseOpenCageJson($json) {
    	if($json->status->code == 200) {
    		$values ['hits'] = 1;
    		$results = $json -> results;
    		$first = $results[0];
    		$values ['lat'] = $first->geometry->lat;
    		$values ['lon'] = $first->geometry->lng;
			if($first->bounds) {
				$values ['ne_lat'] = $first->bounds->northeast->lat;
				$values ['ne_lon'] = $first->bounds->northeast->lng;
				$values ['sw_lat'] = $first->bounds->southwest->lat;
				$values ['sw_lon'] = $first->bounds->southwest->lng;
			}
			if($first->confidence) $values['confidence'] = $first->confidence;
    	} else {
    		$values['error'] = $json->status->message;
    		$this->logMsg("Error returned by Opencage API: ".$values['error']);
    	}

    	return $values;
    } 
    
    public function parseMapQuestJson($json) {
    	
    	$locations = $json->results[0]->locations[0];

    	if($locations) {
    		$values ['hits'] = 1;
    		$values ['lat'] = $locations->latLng->lat;
    		$values ['lon'] = $locations->latLng->lng;
    		
    		//$values ['id'] = false; 
    	} else {
    		$values = 0;
    	}

    	return $values;
    }    
    
    public function parseGbifNubJson($json) {
    	
    	if($json->matchType != "NONE") {
	    	$values ['hits'] = 1;
	    	$values ['scientific_name'] = $json->scientificName;
	   		$values ['rank'] = $json->rank; 
	   		$values ['synonym'] = $json->synonym;
	   		$values ['confidence'] = $json->confidence;
	   		$values ['kingdom'] = $json->kingdom;
    		$values ['phylum'] = $json->phylum;
	    	$values ['clazz'] = $json->clazz;
	    	$values ['order'] = $json->order;
	   		$values ['family'] = $json->family;
	   		$values ['genus'] = $json->genus;
	   		if($json->matchType != "EXACT") $values ['note'] = $json->note;
    	} else {
    		$values ['hits'] = 0;
    	}
    	$values ['match_type'] = $json->matchType;    	

    	return $values;
    }    
    
    public function parseGniResourceJson($json) {
    	
    	if($json && $json->data) {
    		$somethingfound = 0;
    		for($i = 0; $i < count($json->data); $i++) {
    			$record = $json->data[$i];
    			$title = $record->data_source->title;
    			if($this->isPreferredDataSource($title)) {
    				$fieldname = str_replace(" ", "_", strtolower ($title));
    				$values [$fieldname] = $record->records[0]->local_id;
    				$values['hits'] = 1;//$record->records_number;
    				$somethingfound = 1;
    			}
    		}
    		if(!$somethingfound) {
    			$values = 0;
    		}
    	}

    	return $values;

    }    
    
    public function isPreferredDataSource($source) {
    	if($source == "ITIS" ||
    	   $source == "uBio NameBank" ||
    	   $source == "Index to Organism Names" ||
    	   $source == "EOL" ||
    	   $source == "Catalogue Of Life") return true;
    	else return false; 
    }
    
    public function insertResults($data, $name) {

		//mysql_escape_string vs pg_escape_string
		$func = $this->getEscapeFunction();
		
    	$sql = "UPDATE " . $this->config["dbtable"] . " SET " . $this->config["updatefield"]. "=";

    	if($data && !$data['error']) {
    		$sql .=  $data['hits'];
    		//altres camps
    		foreach($data as $field => $value) {
    			// put prefix
    			$fieldname = $this->profile . "_" . $field;
    			
    			if($fieldname != $this->config["updatefield"]) $sql .= ", " . $fieldname . "='" . $func($value) . "'";
    		}
    	//if no data found	
    	} else {
    		$sql .=  "0";
    	}
    	$sql .= " WHERE ";
    	for($i = 0; $i < count($this->config["queryfield"]); $i++) {
    		$qfvalue = $name[$this->config["queryfield"][$i]];
    		if($qfvalue) {	
	    		if(isset($where)) $where .= " AND "; 
	    		$where .= $this->config["queryfield"][$i] . "='" . $func($qfvalue) . "'"; 
			}
    	}
    	$sql .= $where;

    	$wentwell = $this->executeSQL($sql,false);
    	if($wentwell) {
   			if($data) $this->found += 1;
   			else $this->notfound += 1;
   			$this->logMsg("DB query: ".$sql);
   		} else {
   			$this->errorsDB += 1;
			$this->error_strings[] = $sql;
   			$this->logMsg("Error updating!!! ".$sql);
   		}
    	
    	return $data;
    
    }

	public function getEscapeFunction() {

		$func == "pg_escape_string";
		switch($this->config["dbtype"]) {
			case "mysql":
				$func = "mysql_escape_string";
				break;
			default:
				break;
		}

		return $func;	
	}
    
    public function drawResults() {

    	$this->logMsg("ENDED: ".$this->found . " of ". $this->totalQueries . " found.");
    	$this->logMsg("-----------------------------------------------------------------");
    	print_r($this->totalQueries . " records were queried<br>");
    	print_r($this->found . " records were found and inserted in DB<br>");
    	print_r($this->notfound . " records were not found<br>");
    	print_r($this->errorsDB . " gave an error when inserting to DB:");
		foreach($this->error_strings as $error) print_r("<br>".$error);
    	print_r("<br>".$this->errors . " returned an empty string or timed out:");
    	foreach($this->empty_queries as $error2) print_r("<br>".$error2);
		
    }    

    private function xget_file_contents($file) {
        $pipe = @fopen($file, 'rb');
        if ($pipe) {
            while (!feof($pipe)) {
                $line = fgets($pipe, 2048);
                $buffer .= $line;
            }
            fclose($pipe);
            return $buffer;
        }
        return false;
    }


    private function getResult() {
        if ($this->result === false) {
            $this->logError("apicollector::getResult - No results to output");
            return false;
        }
        if ($this->format == "application/json") {
            $this->output = $this->getResultJSON();
        } else {
            $this->logError("apicollector::getResult - Unknown output format [".$this->format."]");
            return false;
        }
        $this->outputResults();
    }


    private function getResultJSON() {
        return json_encode($this->result);
    }


    private function outputResults() {
        if ($this->output === false) {
            $this->logError("apicollector::outputResults - No output ready");
            return false;
        }

        $filename = "geodatanode.".$this->mode.".json";
        header("Content-Disposition: inline; filename=".$filename);
        header("Content-type: ".$this->format."; charset=UTF-8");
        header("Content-Length: ".strlen($this->output));

        // Output data
        echo $this->output;
    }

}
?>
