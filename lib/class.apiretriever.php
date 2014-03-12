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
            	$this->config["dbtable"] = "api_gni";
            	$this->config["queryfield"] = array("gni_resource_uri");
            	$this->config["queryfieldencode"] = 0;
            	$this->config["updatefield"] = "gni_detail_hits";
            	$this->config["urlpattern"] = "[gni_resource_uri]";
            break;
        	
        	case "gbif":
            	$this->config["dbtable"] = "api_gbif";
            	//first query field is the one that mustn't be null for querying. The others are not checked
            	$this->config["queryfield"] = array("genus", "classe_id", "ordre_id", "familia_id");
            	$this->config["queryfieldencode"] = 1;
            	$this->config["updatefield"] = "gbif_hits";
            	$this->config["urlpattern"] = "http://api.gbif.org/v0.9/species/match?class=[classe_id]&order=[ordre_id]&family=[familia_id]&name=[genus]&rank=GENUS";
            break;
        	
        	case "gni":
        	default:
            	$this->config["dbtable"] = "api_gni";
            	$this->config["queryfield"] = array("scientific_name");
            	$this->config["queryfieldencode"] = 1;
            	$this->config["updatefield"] = "gni_hits";
            	$this->config["urlpattern"] = "http://gni.globalnames.org/name_strings.json?search_term=exact:[scientific_name]";
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
    		        	
	        $data = $this->getJson($names[$i]);
	        
	        switch($this->profile) {
	        	case "gni_detail":
	        		$results = $this->parseGniResourceJson($data);
	        	break;
	        	case "gbif":
	        		$results = $this->parseGbifNubJson($data);
	        	break;	        	
	        	case "gni":
	        	default:
	        		$results = $this->parseGniJson($data);
	        	break;
	        }
	    
        	$inserted = $this->insertResults($results, $names[$i]);
        } 
        
        $this->drawResults($inserted);
    }
    
    private function sleep($sleepParam) {
    	// Default sleep is 1 second
        $sleep = ($sleepParam !== false) ? (int) $sleepParam : 1;
    	sleep($sleep);
    } 

    private function getNames($limit, $onlynull) {
        //Build SQL
        $sql = "SELECT DISTINCT " . implode(",", $this->config["queryfield"]) . " FROM " . $this->config["dbtable"];
        //only first element of the array musn't be null
        $sql .= " WHERE ". $this->config["queryfield"][0] . " IS NOT NULL";
        if($onlynull) $sql .= " AND ". $this->config["updatefield"] . " IS NULL";
        if($limit) $sql .= " LIMIT ".$limit;
        
        $names = $this->executeSQL($sql,true);
        
        if(!$names) die("No records match can be processed by ".$this->profile. ". Is ". $this->config["queryfield"][0] . " always empty? Is ". $this->config["updatefield"] . " always full?");
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
		    	$value = $this->config["queryfieldencode"] ? urlencode($name[$field]) : $name[$field];
		    	//search_term=exact:[scientific_name] becomes search_term=exact:Abida+secale
		    	$search = str_replace("[".$field."]", $value, $search);
	    	}
	    	
	    	//provisional hack: in gni_detail, we use json format, not xml
	    	$search = str_replace(".xml", ".json", $search);
	    	
	    	$content = file_get_contents($search);
	    	
	        //log errors or log everything (if DEBUG true)
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
    
    public function insertResults($gnidata, $name) {

    	$sql = "UPDATE " . $this->config["dbtable"] . " SET " . $this->config["updatefield"]. "=";
    	if($gnidata) {
    		$sql .=  $gnidata['hits'];
    		//altres camps
    		foreach($gnidata as $field => $value) {
    			// put prefix
    			$fieldname = $this->profile . "_" . $field;
    			//we already did updatefield
    			if($fieldname != $this->config["updatefield"]) $sql .= ", " . $fieldname . "='" . pg_escape_string($value) . "'";
    		}
    	//if no data found	
    	} else {
    		$sql .=  "0";
    	}
    	$sql .= " WHERE ";
    	for($i = 0; $i < count($this->config["queryfield"]); $i++) {
    		if($where) $where .= " AND "; 
    		$where .= $this->config["queryfield"][$i] . "='" . $name[$this->config["queryfield"][$i]] . "'";
    	}
    	$sql .= $where;
    	
    	$wentwell = $this->executeSQL($sql,false);
    	if($wentwell) {
   			if($gnidata) $this->found += 1;
   			else $this->notfound += 1;
   			$this->logMsg("DB query: ".$sql);
   		} else {
   			$this->errorsDB += 1;
   			$this->logMsg("Error updating!!! ".$sql);
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
