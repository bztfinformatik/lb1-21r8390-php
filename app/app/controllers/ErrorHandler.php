<?php

class ErrorHandler extends Controller
{
    public function __construct()
    {
        parent::__construct(false);
    }

    public function index()
    {
        $this->render('landingpage');
    }

    public function notFound(string $page = 'Page not found')
    {
        $this->render('error/404', ['page' => $page]);
    }
}
