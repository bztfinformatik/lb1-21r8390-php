<?php

use Monolog\Logger;

class Dashboard extends Controller
{
    private $projectRepository;

    public function __construct()
    {
        parent::__construct();

        if (!SessionManager::isLoggedIn()) {
            redirect('', true);
            return;
        }

        $this->projectRepository = $this->loadRepository('ProjectRepository');
    }

    /**
     * Shows the dashboard
     */
    public function index()
    {
        $this->logger->log('Showing the dashboard', Logger::DEBUG);

        $projects = $this->projectRepository->getAllProjects(SessionManager::getCurrentUserId());

        $this->render('dashboard/index', array('projects' => $projects));
    }

    /**
     * Redirects to the Kibana dashboard (Port: `12345`)
     */
    public function kibana()
    {
        // Only allow access to the Kibana dashboard if the user is logged in and has the role 'admin'
        if (!SessionManager::isLoggedIn() || !SessionManager::hasRole($this->loadEnum('role', 'ADMIN')->value)) {
            redirect('', true);
            return;
        }

        $this->logger->log('Showing the kibana dashboard', Logger::DEBUG);

        // Remove the port from the URL
        $lastSlashIndex = strrpos(URLROOT, ':');

        // Redirect to the Kibana dashboard
        header('location: ' . substr(URLROOT, 0, $lastSlashIndex + 1) . KIBANA_PORT, 308);
    }
}
