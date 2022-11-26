<?php

class App
{
    // 'ErrorHandler'
    private readonly string $ERROR_CONTROLLER;

    protected $controller = 'NotFound';
    protected $method = 'index';

    protected $params = [];

    public function __construct()
    {
        $this->ERROR_CONTROLLER = 'ErrorHandler';

        // Parse the URL
        $url = $this->parseUrl();

        // Loads the controller
        $this->loadController($url);

        // Loads the method
        $this->loadMethod($url);

        // Gets all parameters which are not null to be passed to the method
        $this->params = isset($url) ? array_values($url) : [];

        try {
            // Call the method with the parameters of the controller
            call_user_func_array([$this->controller, $this->method], $this->params);
        } catch (Throwable $th) {
            // Call the error handler
            $this->callErrorHandler($th);
        }
    }

    /**
     * Parse the URL into an array
     *
     * @return string[] Array with the URL parts
     */
    private function parseUrl(): array
    {
        if (isset($_GET['url'])) {
            // Split URL into parts
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        // No URL given
        return [];
    }

    /**
     * Loads the controller from the URL
     *
     * @param array $url The URL as an array
     * @return void
     */
    private function loadController(array &$url)
    {
        // Gets the controller name from the URL or shows the landing page
        $controllerPath = $this->redirectController(isset($url[0]) ? $url[0] : $this->ERROR_CONTROLLER);

        // Checks if the controller exists and sets the controller
        if (file_exists('../app/controllers/' . $controllerPath . '.php')) {
            unset($url[0]);
        } else {
            // Could not find the controller
            $controllerPath = $this->ERROR_CONTROLLER;
            $this->method = 'notFound';
        }

        // Load the controller
        require_once '../app/controllers/' . $controllerPath . '.php';

        // Gets the controller name
        $controllerName = resolveComponentName($controllerPath);

        // Create the controller
        $this->controller = new $controllerName;
    }

    /**
     * Redirects a name to a different controller
     *
     * @param string $controllerName The name of the controller
     * @return string The new name of the controller
     */
    private static function redirectController(string $controllerName): string
    {
        switch (strtolower($controllerName)) {
            case 'login':
                return 'UserController';
            default:
                return $controllerName;
        }
    }

    /**
     * Load the method from the URL
     *
     * @param array $url The URL as an array
     * @return void
     */
    private function loadMethod(array &$url)
    {
        // Checks if the error controller is used
        $isError = strcmp($this->ERROR_CONTROLLER, $this->controller::class) == 0;

        // Check if url has a method and if the method exists
        if (!$isError && isset($url[1]) && method_exists($this->controller, $url[1])) {
            $this->method = $url[1];
            unset($url[1]);
        }
    }

    /**
     * Calls the error handler
     *
     * @param Throwable $th The exception which was thrown
     * @return void
     */
    private function callErrorHandler(Throwable $th)
    {
        // Load the error handler
        require_once '../app/controllers/' . $this->ERROR_CONTROLLER . '.php';

        // Gets the controller name
        $controllerName = resolveComponentName($this->ERROR_CONTROLLER);

        // Create the controller
        $this->controller = new $controllerName;

        // Call the error handler
        $this->controller->internalServerError($th);
    }
}
