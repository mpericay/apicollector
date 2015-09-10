<?php

	//limit(23-07-2015): 15,000 transactions/month
	$this->config["dbtable"] = "dwc";
	//first query field is the one that mustn't be null for querying. The others are not checked
	$this->config["queryfield"] = array("country","locality","municipality","county","stateProvince");
	$this->config["queryfieldencode"] = 1;
	$this->config["updatefield"] = "mapquest_hits";
	$this->config["urlpattern"] = "http://www.mapquestapi.com/geocoding/v1/address?key=TsYEF8sucQyf24bDIS3RxwGzz8BbUisA&callback=renderOptions&inFormat=kvp&outFormat=json&location=[locality]%20[municipality]%20[county]%20[stateProvince]%20[country]";
	//API key for localhost: Fmjtd%7Cluurnuutnl%2C8w%3Do5-9wr0ga
	
?>				