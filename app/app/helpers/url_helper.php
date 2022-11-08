<?php

/**
 * Resolves the component path to its name
 *
 * @param string $pathWithSlashes The path to resolve
 * @return string The resolved name
 */
function resolveComponentName(string $pathWithSlashes): string
{
    // Get index of last slash
    $lastSlashIndex = strrpos($pathWithSlashes, '/');

    if ($lastSlashIndex === false) {
        // No slashes found
        return $pathWithSlashes;
    }

    // Get the name of the controller
    return substr($pathWithSlashes, $lastSlashIndex + 1);
}

/**
 * Helper function to redirect to a specific page
 * The HTTP status code is set to 308 to indicate a permanent redirect
 * 
 * @param string $page The page to redirect to
 */
function redirect(string $page)
{
    header('location: ' . URLROOT . '/' . $page, 308);
}
