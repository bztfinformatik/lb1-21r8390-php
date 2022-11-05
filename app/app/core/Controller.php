<?php
require_once '/var/composer/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\SocketHandler;

class Controller
{
    protected $twig;
    protected $logger;

    public function __construct()
    {
        $this->loadTwig();
        $this->loadLogger();
    }

    protected function model($model)
    {
        if (file_exists('../app/models/' . $model . '.php')) {
            require_once '../app/models/' . $model . '.php';
            return new $model();
        } else {
            echo 'Error : Model does not exists!';
        }
    }

    private function loadTwig()
    {
        // Specify our Twig templates location
        $loader = new Twig_Loader_Filesystem(__DIR__ . '/../views');


        $this->twig = new Twig_Environment($loader, array('debug' => true));
        $this->twig->addExtension(new Twig_Extension_Debug());

        // Instantiate our Twig - Production, without Debugging
        // $this->twig = new Twig_Environment($loader);
    }

    private function loadLogger()
    {
        $this->logger = new Logger('elk');

        // "logstash" is a host defined by docker-compose
        $handler = new SocketHandler(LOGSTASH, Logger::DEBUG);
        $this->logger->pushHandler($handler);

        $visitDetails = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'agent' => $_SERVER['HTTP_USER_AGENT'],
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'not set'
        ];
        $this->logger->info("Example of a request being logged", $visitDetails);

        // $this->logger = new MonologLogger('channel-name');
        // $this->logger->pushHandler(new StreamHandler(__DIR__ . '/app.log', Logger::DEBUG));
        // $this->logger->info('My logger is now ready');
    }
}
