<?php
require_once '/var/composer/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\SocketHandler;
use \Twig\Environment;
use \Twig\Loader\FilesystemLoader;

/**
 * The core controller class which is extended by all other controllers.
 *
 * It loads the models and the views.
 */
class Controller
{
    private $twig;
    private $logger;

    public function __construct()
    {
        $this->initLogger();
        $this->initTwig();
    }

    /**
     * Initialize the logger
     * 
     * If `IS_LOGGING` is set to true, the logger will send the logs to the ELK stack
     */
    private function initLogger()
    {
        // Create the logger
        $this->logger = new Logger('php-controller');

        // Now add some handlers
        if (IS_LOGGING) {
            // "logstash" is a host defined by docker-compose
            $handler = new SocketHandler(LOGSTASH, Logger::DEBUG);
            $this->logger->pushHandler($handler);
        }

        $this->log('The logger has been initialized', Logger::NOTICE);
    }

    /**
     * Initialize the twig template engine
     * 
     * The templates are located in the `app/views` folder
     */
    private function initTwig()
    {
        // Specify our Twig templates location
        $loader = new FilesystemLoader(__DIR__ . '/../views');

        // Instantiate our Twig
        $this->twig = new Environment($loader, [
            'debug' => true,
            'cache' => false,
            'autoescape' => 'html',
        ]);

        $this->log('The Twig has been initialized', Logger::NOTICE);
    }

    /**
     * Loads a model from the models folder (`app/models`) and returns it
     *
     * @param string $model The model to load
     * @return BaseModel The model that was loaded
     */
    protected function loadModel(string $model)
    {
        // Check if the model exists
        if (file_exists('../app/models/' . $model . '.php')) {
            // Load the model
            $this->log('Loading the model: ' . $model, Logger::INFO);
            require_once '../app/models/' . $model . '.php';
            // Instantiate the model
            return new $model();
        } else {
            $this->log('Model ' . $model . ' does not exists!', Logger::CRITICAL);
        }
    }

    /**
     * Render a view using Twig
     *
     * @param string $view The view to render
     * @param array $params The parameters to pass to the view
     */
    protected function render($path = '', $data = [])
    {
        $this->log('Rendering view: ' . $path, Logger::INFO);

        // Adding default data
        $path = $path . '.twig.html';

        $data['urlroot'] = URLROOT;

        // Render our view
        echo $this->twig->render($path, $data);
    }

    /**
     * Log a message using the logger
     *
     * @param string $message The message to log
     * @param Logger $level The level of the message (default: DEBUG)
     */
    public function log(string $message, $level = Logger::DEBUG)
    {
        $visitDetails = [
            'user_id' => $_SESSION['user_id'] ?? 'Unknown user_id',
            'user_email' => $_SESSION['user_email'] ?? 'Unknown user_email',
            'ip' => $_SERVER['REMOTE_ADDR'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'agent' => $_SERVER['HTTP_USER_AGENT'],
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'not set'
        ];

        switch ($level) {
            case Logger::INFO:
                $this->logger->info($message, $visitDetails);
                break;
            case Logger::NOTICE:
                $this->logger->notice($message, $visitDetails);
                break;
            case Logger::WARNING:
                $this->logger->warning($message, $visitDetails);
                break;
            case Logger::ERROR:
                $this->logger->error($message, $visitDetails);
                break;
            case Logger::CRITICAL:
                $this->logger->critical($message, $visitDetails);
                break;
            case Logger::ALERT:
                $this->logger->alert($message, $visitDetails);
                break;
            case Logger::EMERGENCY:
                $this->logger->emergency($message, $visitDetails);
                break;
            default:
                $this->logger->debug($message, $visitDetails);
                break;
        }
    }
}
