<?php

use Monolog\Logger;

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
function redirect(string $page, bool $temporary = false)
{
    header('location: ' . URLROOT . '/' . $page, $temporary ? 301 : 308);
}

/**
 * Loads a model from the models folder (`app/models`) and returns it
 *
 * @param string $model The model to load
 * @return Model The model that was loaded
 */
function loadModelHelper(string $model, LogManager $logger)
{
    // Check if the model exists
    if (file_exists('../app/models/' . $model . '.php')) {
        // Load the model
        $logger->log('Loading the model: ' . $model, Logger::INFO);
        require_once '../app/models/' . $model . '.php';
        // Instantiate the model
        $model = resolveComponentName($model);
        return new $model();
    } else {
        $logger->log('Model ' . $model . ' does not exists!', Logger::CRITICAL);
    }
}
