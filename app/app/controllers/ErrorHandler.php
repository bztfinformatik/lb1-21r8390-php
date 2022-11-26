<?php

class ErrorHandler extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Shows the landing page
     * 
     * If the user is signed in, it will redirect to the dashboard
     */
    public function index()
    {
        // If the user is signed in, redirect to the dashboard
        if (SessionManager::isLoggedIn()) {
            redirect('dashboard', true);
            return;
        }

        // Render the landing page
        $this->render('landingpage');
    }

    /**
     * Shows the 404 page
     *
     * @param string $page The page that was not found
     */
    public function notFound(string $page = 'you are looking for')
    {
        $this->render('error/404', ['notFoundPage' => $page]);
    }

    /**
     * Shows the 500 page
     *
     * @param Throwable $error The error that occurred
     */
    public function internalServerError(Throwable $error)
    {
        header('HTTP/1.1 500 Internal Server Error');
        $this->render('error/500', ['error' => $error->getMessage()]);
    }
}
