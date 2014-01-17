<?php
/**
* Configuration parameters
* @details &copy;2014 - Marti Pericay
* @file conf.php
* @version 1.0
*/

/**
* Database connection type
*/
	define("_DB_TYPE","pgsql");

/**
* Database host
*/
	define("_DB_HOST","localhost");

/**
* Database port
*/
	define("_DB_PORT",5432);

/**
* Database name
*/
	define("_DB_NAME","dbname");

/**
* Database user
*/
	define("_DB_USER","user");

/**
* Database password
*/
	define("_DB_PWD","password");

/**
* Complete path to application root
*/
	define("_APICOLLECTOR_APP_ROOT","/srv/www/apps/whatever/");

/**
* Directory for CSV temp files
*/
	define("_APICOLLECTOR_TEMP_DIR",_APICOLLECTOR_APP_ROOT."csv/");
	
/**
* Complete path to the profiles configuration directory
*/
	define("_APICOLLECTOR_CONFIG_DIR",_APICOLLECTOR_APP_ROOT."config/");

/**
* Complete path to the default configuration file
*/
	define("_APICOLLECTOR_DEFAULT_CONFIG_FILE",_APICOLLECTOR_CONFIG_DIR."default.ini");

/**
* Complete path to error log file
*/
	define("_APICOLLECTOR_LOG_FILE",_APICOLLECTOR_APP_ROOT."logs/error.log");


?>