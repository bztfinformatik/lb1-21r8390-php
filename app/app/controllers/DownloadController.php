<?php

use Monolog\Logger;

class DownloadController extends Controller
{
    private $projectRepository;
    private $userRepository;

    public function __construct()
    {
        parent::__construct();

        $this->projectRepository = $this->loadRepository('ProjectRepository');
        $this->userRepository = $this->loadRepository('UserRepository');
    }

    /**
     * Redirects to the download page
     */
    public function index()
    {
        $this->download(null);
    }

    /**
     * Returns the zip file for the given project
     * 
     * @param string $downloadCode The download code of the project
     */
    public function download(string|null $downloadCode)
    {
        // Validate the download code
        if (!isset($downloadCode) || empty($downloadCode)) {
            throw new ProjectGeneratorException('No download code given');
        }

        $this->logger->log('Downloading project with download code ' . $downloadCode, Logger::INFO);

        // Read the project from the database
        $project = $this->projectRepository->getProjectByDownloadCode($downloadCode);

        // Show 404 if the project does not exist
        if (!isset($project)) {
            http_response_code(404);
            $this->render('error/500', [
                'error_text' => '404 - Not Found',
                'error' => 'The project does not exist',
            ]);
            return;
        }

        // Show 403 if the project is not accepted
        if ($project->status->value != $this->loadEnum('project/status', 'accepted')->value) {
            http_response_code(403);
            $this->render('error/500', [
                'error_text' => '403 - Forbidden',
                'error' => "The project needs the status 'accepted' to be downloaded.",
            ]);
            return;
        }

        // Check if is valid md5
        if (!preg_match('/^[a-f0-9]{32}$/', $downloadCode)) {
            throw new ProjectGeneratorException('Invalid download code');
        }

        // Get the user who uploaded the project
        $user = $this->userRepository->getUserById(SessionManager::isLoggedIn() ? SessionManager::getCurrentUserId() : $project->userId);

        // Show loading bar if the project is not ready

        // Generate the template
        $generator = new ProjectGeneratorService();
        $generator->generate($project, $user);
    }
}
