<?php

/**
 * Helper function to redirect to a specific page
 *
 * @param string $page The page to redirect to
 */
function redirect(string $page)
{
    header('location: ' . URLROOT . '/' . $page);
}
