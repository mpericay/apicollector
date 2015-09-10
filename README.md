apicollector
============

Apicollector launches calls to APIs from a field in a database and stores values. Works virtually for any DB, but has been tested in PostgreSQL and MySQL.

Syntax: http://{baseUrl}/index.php?PROFILE=google&LIMIT=5&SLEEP=100&DEBUG=true

Params:
* PROFILE: looks for a plugin file in lib/conf and loads the params (required)
* LIMIT: the maximum number of queries to do (default is no limit ... until table ends)
* SLEEP: milliseconds to wait between queries (default is 1000ms = 1sec)
* DEBUG: if "true", writes more stuff in log file
* ONLYNULL: if "true" (default), queries only the registries where the "updatefield" is null

It is important to note that that, depending on the plugin, some fields have to exist to be written to. The updatefield and the queryfield, plus:
* google: requires google_lat, google_lon
* mapquest: requires google_lat, google_lon
* gni: requires gni_resource_uri
* gni_detail and gbif: to be documented 

Plugins conf file:
* dbtable: which table to be used
* queryfield: the field(s) to be added to the urlpattern. Array. First element is the one that mustn't be null for querying. The others are not checked. 
* queryfieldencode: does queryfield have to be urlencoded?
* updatefield: which field (integer) to store the result of the query. It will write 1 if query and parsing OK, 0 otherwise. 
* urlpattern: pattern to be used in queries. Ex: http://maps.googleapis.com/maps/api/geocode/json?address=[locality]%20[municipality]%20[county]%20[stateProvince]%20[country]
	