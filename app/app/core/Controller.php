<?php

require_once '/var/composer/vendor/autoload.php';

use Monolog\Logger;
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
    protected LogManager $logger;

    /**
     * Initializes the base controller
     */
    public function __construct()
    {
        $this->logger = new LogManager('php-controller-' . get_class($this));
        $this->initTwig();
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

        $this->logger->log('The Twig has been initialized', Logger::NOTICE);
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
            $this->logger->log('Loading the model: ' . $model, Logger::INFO);
            require_once '../app/models/' . $model . '.php';
            // Instantiate the model
            $model = resolveComponentName($model);
            return new $model();
        } else {
            $this->logger->log('Model ' . $model . ' does not exists!', Logger::CRITICAL);
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
        $this->logger->log('Rendering view: ' . $path, Logger::INFO);

        // Adding default data
        $path = $path . '.twig.html';

        $data['urlroot'] = URLROOT;

        // Render our view
        echo $this->twig->render($path, $data);
    }
}
