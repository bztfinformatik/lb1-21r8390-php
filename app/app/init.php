<?php
// Load our autoloader
require_once '/var/composer/vendor/autoload.php';

// Die Config-Variablen unserer App
require_once 'config/config.php';

// Helpers
require_once 'helpers/file_helper.php';
require_once 'helpers/url_helper.php';

// Die Main-Klassen unserer App
require_once 'core/App.php';
require_once 'core/LogManager.php';
require_once 'core/SessionManager.php';
require_once 'core/Database.php';
require_once 'core/Controller.php';

// Services
require_once 'services/ProjectGeneratorService.php';
require_once 'services/SendgridService.php';
