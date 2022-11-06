<?php

function resolveComponentName($pathToController): string
{
    // Get index of last slash
    $lastSlashIndex = strrpos($pathToController, '/');

    if ($lastSlashIndex === false) {
        // No slash found
        return $pathToController;
    }

    // Get the name of the controller
    return substr($pathToController, $lastSlashIndex + 1);
}

/**
 * Helper function to redirect to a specific page
 *
 * @param string $page The page to redirect to
 */
function redirect(string $page)
{
    header('location: ' . URLROOT . '/' . $page);
}
