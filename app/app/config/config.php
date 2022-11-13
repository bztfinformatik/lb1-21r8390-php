<?php

// The APP-Root 
define('APPROOT', dirname(dirname(__FILE__)));

// The URL-Root
define('URLROOT', 'http://localhost:' . getenv('ROOT_PORT'));

// Logstash Server
define('LOGSTASH', 'logstash:9001');

// Kibana Port
define('KIBANA_PORT', getenv('KIBANA_PORT'));

// Logging to ELK Stack
define('IS_LOGGING', getenv('IS_LOGGING') == 'true');
