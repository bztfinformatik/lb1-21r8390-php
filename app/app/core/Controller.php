<?php

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

        $this->logger->log('The Twig has been initialized', Logger::INFO);
    }

    /**
     * Loads a model from the models folder (`app/models`) and returns it
     *
     * @param string $model The model to load
     * @return Model The model that was loaded
     */
    protected function loadModel(string $model)
    {
        return loadModelHelper($model, $this->logger);
    }

    /**
     * Loads a enum from the models folder (`app/models`) and returns it
     *
     * @param string $enum The enum to load
     * @param mixed $value The value to get from the enum
     * @return Enum The enum that was loaded
     */
    protected function loadEnum(string $enum, $value)
    {
        return loadEnumHelper($enum, $value, $this->logger);
    }

    /**
     * Loads a repository from the repositories folder (`app/repositories`) and returns it
     *
     * @param string $repository The repository to load
     * @return Repository The repository that was loaded
     */
    protected function loadRepository(string $repository)
    {
        // Check if the model exists
        if (file_exists('../app/repositories/' . $repository . '.php')) {
            // Load the repository
            $this->logger->log('Loading the repository: ' . $repository, Logger::INFO);
            require_once '../app/repositories/' . $repository . '.php';
            // Instantiate the repository
            return new $repository();
        } else {
            $this->logger->log('Repository ' . $repository . ' does not exists!', Logger::CRITICAL);
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

        // Add default data
        $data['urlroot'] = URLROOT;
        $data['isAdmin'] = SessionManager::hasRole($this->loadEnum('role', 'ADMIN')->value);
        $data['isSignedIn'] = SessionManager::isLoggedIn();
        $data['user_profile_picture'] = SessionManager::getProfilePicture();

        // Render our view
        echo $this->twig->render($path, $data);
    }
}
