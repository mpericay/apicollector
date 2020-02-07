apicollector
============

Apicollector launches calls to APIs from a field in a database and stores values. Has been tested in PostgreSQL and MySQL.

##Deployment
It runs under PHP5, so a good idea is to use Docker, mounting your local dir as a volume
`docker run -d -p 8080:80 -v /Users/marti/Documents/github/apicollector:/var/www/html nimmis/apache-php5`

##How-to
Syntax: http://{baseUrl}/index.php?PROFILE=google&LIMIT=5&SLEEP=100&DEBUG=true

Params:
* PROFILE: looks for a plugin file in lib/conf and loads the params (required)
* LIMIT: the maximum number of queries to do (default is no limit ... until table ends)
* SLEEP: milliseconds to wait between queries (default is 1000ms = 1sec)
* DEBUG: if "true", writes more stuff in log file
* ONLYNULL: if "true" (default), queries only the registries where the "updatefield" is null. If false, starts from the beginning

Plugins conf file:
* dbtable: which table to be used
* queryfield: the field(s) to be added to the urlpattern. Array. First element is the one that mustn't be null for querying. The others are not checked.
* queryfieldencode: does queryfield have to be urlencoded?
* updatefield: which field (integer) to store the result of the query. It will write 1 if query and parsing OK, 0 otherwise.
* urlpattern: pattern to be used in queries. Ex: http://maps.googleapis.com/maps/api/geocode/json?address=[locality]%20[municipality]%20[county]%20[stateProvince]%20[country]

It is important to note that two fields are required in the table before running the query:
* updatefield set in profile conf plugin. Must be an integer, and its initial value must be null to point that the record hasn't been queried.  
* queryfield set in profile conf plugin. Must be an array, the first element must exist and musn't be null.
