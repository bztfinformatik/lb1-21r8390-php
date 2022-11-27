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

// MariaDB Connection
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASSWD'));

// SendGrid
define('SENDGRID_API_KEY', getenv('SENDGRID_API_KEY'));
define('EMAIL_FROM', getenv('EMAIL_FROM'));
