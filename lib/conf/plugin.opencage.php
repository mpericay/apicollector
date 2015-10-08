<?php

	//limit(23-07-2015): 2,500 requests per 24 hour period. 5 requests per second
	$this->config["dbtable"] = "dwc";
	//first query field is the one that mustn't be null for querying. The others are not checked
	$this->config["queryfield"] = array("country","locality","municipality","county","stateProvince");
	$this->config["queryfieldencode"] = 1;
	$this->config["updatefield"] = "opencage_hits";
	$this->config["urlpattern"] = "http://api.opencagedata.com/geocode/v1/json?key=793e8c738614d7b4ce5c4e887792f612&q=[locality]%20[municipality]%20[county]%20[stateProvince]%20[country]";
				
?>				