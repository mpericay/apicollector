<?php

	$this->config["dbtable"] = "dwc";
	$this->config["queryfield"] = array("scientificName");
	$this->config["queryfieldencode"] = 1;
	$this->config["updatefield"] = "gni_hits";
	$this->config["urlpattern"] = "http://gni.globalnames.org/name_strings.json?search_term=exact:[scientificName]";
				
?>				