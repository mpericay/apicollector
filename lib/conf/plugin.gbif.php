<?php

	$this->config["dbtable"] = "api_gbif";
	//first query field is the one that mustn't be null for querying. The others are not checked
	$this->config["queryfield"] = array("genus", "classe_id", "ordre_id", "familia_id");
	$this->config["queryfieldencode"] = 1;
	$this->config["updatefield"] = "gbif_hits";
	$this->config["urlpattern"] = "http://api.gbif.org/v0.9/species/match?class=[classe_id]&order=[ordre_id]&family=[familia_id]&name=[genus]&rank=GENUS";
				
?>				