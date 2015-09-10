<?php

	//limit(23-07-2015): 2,500 requests per 24 hour period. 5 requests per second
	$this->config["dbtable"] = "dwc";
	//first query field is the one that mustn't be null for querying. The others are not checked
	$this->config["queryfield"] = array("country","locality","municipality","county","stateProvince");
	$this->config["queryfieldencode"] = 1;
	$this->config["updatefield"] = "google_hits";
	$this->config["urlpattern"] = "http://maps.googleapis.com/maps/api/geocode/json?address=[locality]%20[municipality]%20[county]%20[stateProvince]%20[country]";
				
?>				