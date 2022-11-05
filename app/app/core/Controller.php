<?php

class Controller
{
    protected $twig;

    public function __construct()
    {
        $this->loadTwig();
    }

    protected function model($model)
    {

        if (file_exists('../app/models/' . $model . '.php'))
        {
            require_once '../app/models/' . $model . '.php';
            return new $model();
        }
        else {
            echo 'Error : Model does not exists!';
        }
    }

    private function loadTwig() {
        // Load our autoloader
        require_once '/var/composer/vendor/autoload.php';

        // Specify our Twig templates location
        $loader = new Twig_Loader_Filesystem(__DIR__.'/../views');


        $this->twig = new Twig_Environment($loader, array('debug' => true));
        $this->twig->addExtension(new Twig_Extension_Debug());

        // Instantiate our Twig - Production, without Debugging
        // $this->twig = new Twig_Environment($loader);
    }
    
}
    
