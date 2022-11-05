<?php

// Hilfsfunktion um einfacher Redirecten zu können
function redirect($page) {
    //echo 'location : ' . URLROOT . '/' . $page;
    header('location: ' . URLROOT . '/' . $page);
}