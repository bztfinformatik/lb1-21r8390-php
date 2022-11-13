<?php

require_once 'BaseRepository.php';

use Monolog\Logger;

class ProjectRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Saves the project to the database
     *
     * @param Project $project
     * @return integer The ID of the saved project
     */
    public function save(Project $project): int
    {
        $this->logger->log("Saving project '$project->title' to the database", Logger::INFO);

        // TODO Save the project to the database
        // Currently only mocked

        return random_int(0, 1000000);
    }

    /**
     * Deletes a project from the database
     *
     * @param int $projectId The ID of the project
     */
    public function delete(int $projectId)
    {
        $this->logger->log("Deleting project with ID '$projectId' from the database", Logger::INFO);

        // TODO Delete the project from the database
        // Currently only mocked
    }

    /**
     * Returns all projects from a user
     *
     * @param integer $userId The ID of the user
     * @return array The projects
     */
    public function getAllProjects(int $userId): array
    {
        $this->logger->log("Getting all projects from the database", Logger::DEBUG);

        $projects = array();
        for ($i = 0; $i < random_int(0, 20); $i++) {
            $projects[] = $this->generateFakeProject();
        }

        return $projects;
    }

    /**
     * Returns a project by its ID
     *
     * @param integer $projectId The ID of the project
     * @return Project The project with the given ID
     */
    public function getById(int $projectId): Project
    {
        $this->logger->log("Getting project with ID '$projectId' from the database", Logger::DEBUG);

        return $this->generateFakeProject();
    }

    /**
     * Generates a fake project for testing purposes
     *
     * @return Project The generated project
     */
    private function generateFakeProject(): Project
    {
        $project = $this->loadModel('project/project');

        $project->id = random_int(0, 100);

        // General
        $project->title = 'Test';
        $project->description = str_repeat('Lorem ipsum ', random_int(2, 20));
        $project->createdAt = new DateTime();
        $project->fromDate = new DateTime();
        $project->toDate = $project->fromDate->add(new DateInterval('P1M'));
        $project->docsRepo = "https://github.com/username/$project->id/docs/repo";
        $project->codeRepo = "https://github.com/username/$project->id/code/repo";

        // Project specific
        $project->wantReadme = (bool)random_int(0, 1);
        $project->wantReadme = (bool)random_int(0, 1);
        $project->wantIgnore = (bool)random_int(0, 1);
        $project->wantCSS = (bool)random_int(0, 1);
        $project->wantJS = (bool)random_int(0, 1);
        $project->wantPages = (bool)random_int(0, 1);

        // Appearance
        $project->color = $this->loadEnum('project/color', random_int(0, 20));
        $project->font = $this->loadEnum('project/font', random_int(0, 6));
        $project->wantDarkMode = (bool)random_int(0, 1);
        $project->wantCopyright = (bool)random_int(0, 1);
        $project->wantSearch = (bool)random_int(0, 1);
        $project->wantTags = (bool)random_int(0, 1);
        $project->footerLinks = array();
        $project->logo = 'https://picsum.photos/200/300/?blur';

        // Folder structure
        $project->wantJournal = (bool)random_int(0, 1);
        $project->wantExamples = (bool)random_int(0, 1);
        // $project->structure = $this->generateFakeStructure();

        // Confirmation
        $project->status = $this->loadEnum('project/status', random_int(0, 2));

        return $project;
    }
}
