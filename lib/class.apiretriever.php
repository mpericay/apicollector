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
    private $error_string = "";

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

        //Get operation and mode
        /*$this->operation = $this->getParameter("OP");
        if (!$this->operation) {
            $this->logError("apicollector::__construct - Parameter missing [OP]");
            return false;
        }*/

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
            	
        switch($this->profile) {
        	case "gni_detail":
            	$this->config["dbtable"] = "gni";
            	$this->config["queryfield"] = "gni_resource_uri";
            	$this->config["queryfieldencode"] = 0;
            	$this->config["updatefield"] = "gni_detail_hits";
            	$this->config["url"] = "";
            break;
        	
        	case "gni":
        	default:
            	$this->config["dbtable"] = "gni";
            	$this->config["queryfield"] = "scientific_name";
            	$this->config["queryfieldencode"] = 1;
            	$this->config["updatefield"] = "gni_hits";
            	$this->config["url"] = "http://gni.globalnames.org/name_strings.json?search_term=exact:";
            break;
        }

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


    private function extend($array,$defArray) {
        foreach ($defArray as $key => $value) {
            if (isset($array[$key]) && gettype($array[$key]) == "array") {
                $array[$key] = $this->extend($array[$key],$defArray[$key]);
            } else if (!isset($array[$key]) || !$array[$key]) {
                    $array[$key] = $defArray[$key];
                }
        }
        return $array;
    }


    private function arraySearchRecursive($needle,$haystack) {
        foreach($haystack as $key => $value) {
            $current_key=$key;
            if($needle===$value || (is_array($value) && $this->arraySearchRecursive($needle,$value) !== false)) {
                return $current_key;
            }
        }
        return false;
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
        
        $names = $this->getNames($limit, true);
        
        for($i=0; $i<count($names); $i++) {
        	
    		$this->sleep($this->getParameter("SLEEP"));
    		        	
	        $data = $this->getJson($names[$i][$this->config["queryfield"]]);
	        
	        switch($this->profile) {
	        	case "gni_detail":
	        		$results = $this->parseGniResourceJson($data);
	        	break;
	        	case "gni":
	        	default:
	        		$results = $this->parseGniJson($data);
	        	break;
	        }
	    
        	$inserted = $this->insertResults($results, $names[$i][$this->config["queryfield"]]);
        } 
        
        $this->drawResults($inserted);
    }
    
    private function sleep($sleepParam) {
    	// Default sleep is 1 second
        $sleep = $sleepParam ? (int) $sleepParam : 1;
    	sleep($sleep);
    } 

    private function getNames($limit, $onlynull) {
        //Build SQL
        $sql = "SELECT DISTINCT gni.\"" . $this->config["queryfield"] . "\" FROM " . $this->config["dbtable"];
        $sql .= " WHERE ". $this->config["queryfield"] . " IS NOT NULL";
        if($onlynull) $sql .= " AND ". $this->config["updatefield"] . " IS NULL";
        if($limit) $sql .= " LIMIT ".$limit;
        
        $names = $this->executeSQL($sql,true);
        
        if(!$names) die("No records match can be processed by ".$this->profile. ". Is ". $this->config["queryfield"] . " always empty? Is ". $this->config["updatefield"] . " always full?");
        else $this->totalQueries = count($names); 
        
        $this->logMsg("Queries to do: ".$this->totalQueries);

        return $names;
    }


    private function getJson($name) {

    	if($name != '') {
	    		
    		//is service down?
    		//if(count($data) > 3 && count($data) == $this->errors) die($this->config["url"]." doesn't seem to be responding");
    			
	    	$search = $this->config["url"];
	    	//does it need to be encoded?
	    	$search .= $this->config["queryfieldencode"] ? urlencode($name) : $name;
	    	//provisional: we use json format, not xml
	    	$search = str_replace(".xml", ".json", $search);
	    	
	    	$content = file_get_contents($search);
	    	
	        if($content === false) {
	        	$this->errors ++;
	        	$this->error_string .= ";".$search;
	        	$this->logMsg("Empty query!!! ".$search);
	        } else {
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
    
    public function insertResults($gnidata, $name) {

    	$sql = "UPDATE " . $this->config["dbtable"] . " SET " . $this->config["updatefield"]. "=";
    	if($gnidata) {
    		$sql .=  $gnidata['hits'];
    		//altres camps
    		foreach($gnidata as $field => $value) {
    			// put prefix
    			$fieldname = $this->profile . "_" . $field;
    			//we already did updatefield
    			if($fieldname != $this->config["updatefield"]) $sql .= ", " . $fieldname . "='" . $value . "'";
    		}
    	//if no data found	
    	} else {
    		$sql .=  "0";
    	}
    	$sql .= " WHERE gni.\"" . $this->config["queryfield"] . "\"='" . $name . "'";
    	
    	$this->logMsg("DB query: ".$sql);
    	
    	$wentwell = $this->executeSQL($sql,false);
    	if($wentwell) {
   			if($gnidata) $this->found += 1;
   			else $this->notfound += 1;
   		} else {
   			$this->errorsDB += 1;
   		}
    	
    	return $result;
    
    }
    
    public function drawResults() {
    	$this->logMsg("ENDED: ".$this->found . " of ". $this->totalQueries . " found.");
    	$this->logMsg("-----------------------------------------------------------------");
    	print_r($this->totalQueries . " records were queried<br>");
    	print_r($this->found . " records were found and inserted in DB<br>");
    	print_r($this->notfound . " records were not found<br>");
    	print_r($this->errorsDB . " gave an error when inserting to DB<br>");
    	print_r($this->errors . " returned an empty string or timed out:");
    	print_r(str_replace(";", "<br>", $this->error_string));
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
