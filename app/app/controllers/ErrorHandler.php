<?php

class ErrorHandler extends Controller
{
    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * Shows the landing page
     */
    public function index()
    {
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
