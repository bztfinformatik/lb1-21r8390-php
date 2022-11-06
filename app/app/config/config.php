<?php

// The APP-Root 
define('APPROOT', dirname(dirname(__FILE__)));

// The URL-Root
define('URLROOT', 'http://localhost:' . getenv('ROOT_PORT'));

// Logstash Server
define('LOGSTASH', 'logstash:9001');

// Logging to ELK Stack
define('IS_LOGGING', getenv('IS_LOGGING') == 'true');
