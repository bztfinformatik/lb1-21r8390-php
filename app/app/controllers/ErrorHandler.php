<?php

use Monolog\Logger;

/**
 * Handles errors and other types of exceptions.
 */
class ErrorHandler extends Controller
{
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

        $this->logger->log('Showing the landing page', Logger::INFO);

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
        $this->logger->log('Could not find page ' . $page, Logger::WARNING);
        $this->render('error/404', ['notFoundPage' => $page]);
    }

    /**
     * Shows the 500 page
     *
     * @param Throwable $error The error that occurred
     */
    public function internalServerError(Throwable $error)
    {
        $this->logger->log('Internal Server Error: ' . $error->getMessage() . ' with previous: ' . $error->getPrevious(), Logger::ERROR);
        header('HTTP/1.1 500 Internal Server Error');
        http_response_code(500);
        $this->render('error/500', [
            'error_text' => '500 - Internal Server Error',
            'error' => $error->getMessage(),
        ]);
    }
}
