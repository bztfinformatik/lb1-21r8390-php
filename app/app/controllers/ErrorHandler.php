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
}
