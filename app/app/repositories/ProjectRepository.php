<?php

require_once 'BaseRepository.php';

use Monolog\Logger;

class ProjectRepository extends BaseRepository
{
    /**
     * Saves the project to the database
     *
     * @param Project $project
     */
    public function save(Project $project)
    {
        if (!isset($project)) {
            throw new InvalidArgumentException('Project is null');
        }

        // Check if the project is new
        if (!isset($project->id) || $project->id == -1) {
            $this->logger->log("Saving project '$project->title' to the database", Logger::INFO);

            // Insert the project
            $this->db->query('INSERT INTO project (userId, title, description, createdAt, fromDate, toDate, docsRepo, codeRepo, wantReadme, wantIgnore, wantCSS, wantJS, wantPages, color, font, wantDarkMode, wantCopyright, wantSearch, wantTags, logo, wantJournal, wantExamples, structure, confirmedBy, comment, status, downloadUrl) 
                VALUES (:userId, :title, :description, :createdAt, :fromDate, :toDate, :docsRepo, :codeRepo, :wantReadme, :wantIgnore, :wantCSS, :wantJS, :wantPages, :color, :font, :wantDarkMode, :wantCopyright, :wantSearch, :wantTags, :logo, :wantJournal, :wantExamples, :structure, :confirmedBy, :comment, :status, :downloadUrl)');
        } else {
            $this->logger->log("Updating project '$project->title' in the database", Logger::INFO);

            // Update the project
            $this->db->query('UPDATE project SET
                userId = :userId, title = :title, description = :description, createdAt = :createdAt, fromDate = :fromDate, toDate = :toDate,
                docsRepo = :docsRepo, codeRepo = :codeRepo, wantReadme = :wantReadme, wantIgnore = :wantIgnore, wantCSS = :wantCSS, wantJS = :wantJS,
                wantPages = :wantPages, color = :color, font = :font, wantDarkMode = :wantDarkMode, wantCopyright = :wantCopyright, wantSearch = :wantSearch,
                wantTags = :wantTags, logo = :logo, wantJournal = :wantJournal, wantExamples = :wantExamples, structure = :structure, confirmedBy = :confirmedBy,
                comment = :comment, status = :status, downloadUrl = :downloadUrl
                WHERE id = :id');

            $this->db->bind(':id', $project->id);
        }

        // Bind the parameters
        $this->db->bind(':userId', $project->userId);
        $this->db->bind(':title', $project->title);
        $this->db->bind(':description', $project->description);
        $this->db->bind(':createdAt', $project->createdAt);
        $this->db->bind(':fromDate', $project->fromDate);
        $this->db->bind(':toDate', $project->toDate);
        $this->db->bind(':docsRepo', $project->docsRepo);
        $this->db->bind(':codeRepo', $project->codeRepo);
        $this->db->bind(':wantReadme', $project->wantReadme);
        $this->db->bind(':wantIgnore', $project->wantIgnore);
        $this->db->bind(':wantCSS', $project->wantCSS);
        $this->db->bind(':wantJS', $project->wantJS);
        $this->db->bind(':wantPages', $project->wantPages);
        $this->db->bind(':color', $project->color->value);
        $this->db->bind(':font', $project->font->value);
        $this->db->bind(':wantDarkMode', $project->wantDarkMode);
        $this->db->bind(':wantCopyright', $project->wantCopyright);
        $this->db->bind(':wantSearch', $project->wantSearch);
        $this->db->bind(':wantTags', $project->wantTags);
        $this->db->bind(':logo', $project->logo);
        $this->db->bind(':wantJournal', $project->wantJournal);
        $this->db->bind(':wantExamples', $project->wantExamples);
        $this->db->bind(':structure', $project->structure);
        $this->db->bind(':confirmedBy', $project->confirmedBy);
        $this->db->bind(':comment', $project->comment);
        $this->db->bind(':status', $project->status->value);
        $this->db->bind(':downloadUrl', $project->downloadUrl);

        echo var_dump($project->status->value);

        // Execute the query
        $this->db->execute();
    }

    /**
     * Deletes a project from the database
     *
     * @param int $projectId The ID of the project
     * @param int $userId The ID of the user
     */
    public function delete(int $projectId, int $userId)
    {
        $this->logger->log("Deleting project with ID '$projectId' from the database", Logger::INFO);

        // Delete the project
        $this->db->query('DELETE FROM project WHERE id = :id AND userId = :userId LIMIT 1');
        $this->db->bind(':id', $projectId);
        $this->db->bind(':userId', $userId);

        // Execute the query
        $this->db->execute();

        // Check if the user was deleted
        return $this->db->rowCount() > 0;
    }

    /**
     * Returns all projects from a user
     *
     * @param integer $userId The ID of the user
     * @return array The projects
     */
    public function getAllProjects(int $userId): array
    {
        $this->logger->log("Reading all projects from the database", Logger::DEBUG);

        // Get the projects
        $this->db->query('SELECT * FROM project WHERE userId = :userId ORDER BY createdAt DESC, status ASC');
        $this->db->bind(':userId', $userId);

        // Get the results
        $results = $this->db->all();

        // Create the projects
        $projects = [];
        foreach ($results as $result) {
            $projects[] = $this->loadProject($result);
        }

        return $projects;
    }

    /**
     * Returns a project by its ID
     *
     * @param integer $projectId The ID of the project
     * @param integer $userId The ID of the user
     * @return Project The project with the given ID
     */
    public function getProjectById(int $projectId, int $userId): Project
    {
        $this->logger->log("Getting project with ID '$projectId' from the database", Logger::DEBUG);

        // Get the project
        $this->db->query('SELECT * FROM project WHERE id = :id AND userId = :userId LIMIT 1');
        $this->db->bind(':id', $projectId);
        $this->db->bind(':userId', $userId);

        // Get the result
        $result = $this->db->single();

        // Return the project if found
        return $this->loadProject($result);
    }

    /**
     * Checks if a project with the given name exists
     *
     * @param string $title The title of the project
     * @param int $projectId The ID of the project
     * @param integer $userId The ID of the current user
     */
    public function existsProjectWithName(string $title, int $projectId, int $userId): bool
    {
        $this->logger->log("Checking if project with title '$title' exists in the database", Logger::DEBUG);
        // Get the project
        $this->db->query('SELECT id FROM project WHERE id != :id AND title = :title AND userId = :userId LIMIT 1');
        $this->db->bind(':id', $projectId);
        $this->db->bind(':title', $title);
        $this->db->bind(':userId', $userId);

        // Get the result
        $result = $this->db->single();

        // Return the project if found
        return is_bool($result) ? $result : isset($result);
    }

    #region Helper methods

    /**
     * Loads the project model and sets the properties 
     * 
     * @param array $result The result from the database
     */
    private function loadProject($result): Project|null
    {
        // Check if the project exists
        if ($result == null) {
            return null;
        }

        // Create the project
        $project = $this->loadModel('project/project');
        $project->id = $result['id'];
        $project->userId = $result['userId'];
        $project->title = $result['title'];
        $project->description = $result['description'];
        $project->createdAt = $result['createdAt'];
        $project->fromDate = $result['fromDate'];
        $project->toDate = $result['toDate'];
        $project->docsRepo = $result['docsRepo'];
        $project->codeRepo = $result['codeRepo'];
        $project->wantReadme = $result['wantReadme'];
        $project->wantIgnore = $result['wantIgnore'];
        $project->wantCSS = $result['wantCSS'];
        $project->wantJS = $result['wantJS'];
        $project->wantPages = $result['wantPages'];
        $project->color = $this->loadEnum('project/color', $result['color']);
        $project->font = $this->loadEnum('project/font', $result['font']);
        $project->wantDarkMode = $result['wantDarkMode'];
        $project->wantCopyright = $result['wantCopyright'];
        $project->wantSearch = $result['wantSearch'];
        $project->wantTags = $result['wantTags'];
        $project->logo = $result['logo'];
        $project->wantJournal = $result['wantJournal'];
        $project->wantExamples = $result['wantExamples'];
        $project->structure = $result['structure'];
        $project->confirmedBy = $result['confirmedBy'];
        $project->comment = $result['comment'];
        $project->status = $this->loadEnum('project/status', $result['status']);
        $project->downloadUrl = $result['downloadUrl'];

        return $project;
    }

    #endregion

    #region Mock data

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
        $project->logo = 'https://picsum.photos/200/300/?blur';

        // Folder structure
        $project->wantJournal = (bool)random_int(0, 1);
        $project->wantExamples = (bool)random_int(0, 1);

        // Confirmation
        $project->status = $this->loadEnum('project/status', random_int(0, 2));
        $project->comment = str_repeat('Lorem ipsum ', random_int(1, 20));

        return $project;
    }

    #endregion
}
