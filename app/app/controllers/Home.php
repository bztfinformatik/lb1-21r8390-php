<?php

class Home extends Controller
{

    /**
     * index - Hier könnte eine allgemeine Übersichts-Auswertung der aktuellen Auslastung der Mensa platziert werden
     *
     * @param  mixed $pagename - Page Title
     *
     * @return void
     */
    public function index($name = 'ChangeME - Home/Index')
    {
        $this->render('home/index', ['name' => $name]);
    }
}
