<?php

use Monolog\Logger;

/**
 * An error thrown by the SendGrid Service
 */
class ProjectGeneratorException extends Exception
{
}

/**
 * Generates the project files for the download
 */
class ProjectGeneratorService
{
    private LogManager $logger;

    public function __construct()
    {
        $this->logger = new LogManager('php-project-generator');
        $this->logger->log('The project generator service has been initialized', Logger::DEBUG);
    }

    /**
     * Throws an exception and logs it
     *
     * @param string $error The error message
     * @throws Exception The exception that was thrown
     */
    protected function throwError(string $error)
    {
        $this->logger->log($error, Logger::ERROR);
        throw new ProjectGeneratorException($error);
    }

    /**
     * Returns the path to the project directory
     *
     * @return string The path to the project directory
     */
    private static function getTemplatePath(): string
    {
        return '../app/templates/mkdocs/';
    }

    /**
     * Replaces the placeholders in the template files with the actual values
     *
     * @param string $path The path to the template file
     * @param array $literals The literals to replace
     */
    private function replaceTemplateLiterals(string $path, array $literals)
    {
        $this->logger->log('Replacing template literals in ' . $path . ' with ' . json_encode($literals), Logger::DEBUG);
        $license = file_get_contents($path);

        // Replace each literal
        foreach ($literals as $key => $value) {
            $license = str_replace('{{ ' . $key . ' }}', $value, $license);
        }

        file_put_contents($path, $license);
    }

    public function start(Project|null $project, User|null $owner)
    {
        // Validate the project
        if (!isset($project)) {
            $this->throwError('The project is could not be found');
        }
        if (!isset($owner)) {
            $this->throwError("The owner of the project '$project->id' could not be found");
        }

        $this->logger->log("Starting the project generator for project '$project->id'", Logger::INFO);

        // Create the project directory
        $projectDirectory = $this->createProjectDirectory($project);

        // Copy the docker-compose.yml file and the Docker directory
        $this->copyDocker($projectDirectory);

        // Copy the .gitignore file
        if ($project->wantIgnore) {
            $this->copyGitIgnore($projectDirectory);
        }

        // Copy the github actions directory
        if ($project->wantPages) {
            $this->copyGithubActions($projectDirectory);
        }

        // Create the LICENSE file
        if ($project->wantLicense) {
            $this->createLicense($projectDirectory, $owner);
        }

        // Create the README.md file
        if ($project->wantReadme) {
            $this->createReadme($projectDirectory, $project, $owner);
        }

        // Create the docs directory
        $docsDirectory = $projectDirectory . '/docs';
        if (mkdir($docsDirectory)) {
            $this->logger->log("Created the docs directory in '$projectDirectory'", Logger::DEBUG);
        } else {
            $this->throwError("Could not create the /docs directory in '$projectDirectory'");
        }

        // Copy examples
        if ($project->wantExamples) {
            $this->copyExamples($docsDirectory);
        }

        // Copy the index.md file
        $this->copyIndex($docsDirectory, $project);

        // Copy the favicon
        $this->copyFavicon($docsDirectory, $project);

        // Copy the tags
        if ($project->wantTags) {
            $this->copyTags($docsDirectory);
        }

        // Copy the CSS
        if ($project->wantCSS) {
            $this->copyCss($docsDirectory);
        }

        // Copy the JS
        if ($project->wantJS) {
            $this->copyJs($docsDirectory);
        }

        // Create the mkdocs.yml file
        $this->createMkdocsYml($docsDirectory, $project, $owner);

        // Create the structure
        $this->createStructure($docsDirectory, $project);

        // Create the journal
        if ($project->wantJournal) {
            $this->createWeeklyJournal($docsDirectory, $project);
        }

        $this->logger->log("Finished the project generator for project '$project->id'", Logger::INFO);

        // Create a zip file
        $zipPath = $this->createZip($projectDirectory);

        // Download the zip file
        $this->downloadZip($zipPath);

        // Delete the project directory
        $this->deleteProjectDirectory($projectDirectory);
    }

    /**
     * Creates the project directory
     *
     * @param Project $project The project to create the directory for
     * @return string The path to the project directory
     */
    private function createProjectDirectory(Project $project): string
    {
        $this->logger->log("Creating the project directory for project '$project->id'", Logger::DEBUG);

        // Create the directory in the temp directory
        $baseDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        // Check if the directory is valid
        if (!is_dir($baseDir) || !is_writable($baseDir)) {
            $this->throwError("The directory '$baseDir' is not valid");
        }

        // Set the base directory path
        $baseDir = $baseDir . DIRECTORY_SEPARATOR .  strval($project->id);

        // Check if folder already exists
        for ($i = 0; $i < 300; $i++) {
            // Create a unique folder name
            $dir = $baseDir . uniqid();

            // Check if the folder exists and create it if it doesn't
            if (!is_dir($dir) && mkdir($dir)) {
                // Return the path to the directory
                $this->logger->log("The project directory for project '$project->id' has been created at '$dir'", Logger::DEBUG);
                return $dir;
            }
        }

        // If we get here, we couldn't create a directory
        $this->throwError('Could not create a unique directory');
    }

    /**
     * Copies the docker files to the project directory
     *
     * @param string $projectDirectory The path to the project directory
     */
    private function copyDocker(string $projectDirectory)
    {
        $this->logger->log("Copying the docker files to the project directory '$projectDirectory'", Logger::DEBUG);

        // Copy the docker-compose.yml file
        $dockerComposePath = $projectDirectory . DIRECTORY_SEPARATOR . 'docker-compose.yml';
        if (!copy(self::getTemplatePath() . 'docker-compose.yml', $dockerComposePath)) {
            $this->throwError("Could not copy the docker-compose.yml file to '$dockerComposePath'");
        }

        // Copy the Docker directory
        $dockerPath = $projectDirectory . DIRECTORY_SEPARATOR . 'docker';
        if (!copy(self::getTemplatePath() . 'docker', $dockerPath)) {
            $this->throwError("Could not copy the Docker directory to '$dockerPath'");
        }

        $this->logger->log("The docker files have been copied to the project directory '$projectDirectory'", Logger::DEBUG);
    }

    /**
     * Copies the .gitignore file to the project directory
     *
     * @param string $projectDirectory The path to the project directory
     */
    private function copyGitIgnore(string $projectDirectory)
    {
        $this->logger->log("Copying the .gitignore file to the project directory '$projectDirectory'", Logger::DEBUG);

        // Get the path to the .gitignore file
        $gitIgnorePath = self::getTemplatePath() . '.gitignore';

        // Check if the file exists
        if (!is_file($gitIgnorePath)) {
            $this->throwError("The .gitignore file could not be found at '$gitIgnorePath'");
        }

        // Copy the file to the project directory
        if (!copy($gitIgnorePath, $projectDirectory . DIRECTORY_SEPARATOR . '.gitignore')) {
            $this->throwError("The .gitignore file could not be copied to '$projectDirectory'");
        }

        $this->logger->log("The .gitignore file has been copied to the project directory '$projectDirectory'", Logger::DEBUG);
    }

    /**
     * Copies the github actions directory to the project directory
     * 
     * @param string $projectDirectory The path to the project directory
     */
    private function copyGithubActions(string $projectDirectory)
    {
        $this->logger->log("Copying the github actions directory to the project directory '$projectDirectory'", Logger::DEBUG);

        // Get the path to the github actions directory
        $githubActionsPath = self::getTemplatePath() . '.github';

        // Check if the directory exists
        if (!is_dir($githubActionsPath)) {
            $this->throwError("The github actions directory could not be found at '$githubActionsPath'");
        }

        // Copy the directory to the project directory
        if (!copy($githubActionsPath, $projectDirectory . DIRECTORY_SEPARATOR . '.github')) {
            $this->throwError("The github actions directory could not be copied to '$projectDirectory'");
        }

        $this->logger->log("The github actions directory has been copied to the project directory '$projectDirectory'", Logger::DEBUG);
    }

    /**
     * Creates the LICENSE file in the project directory
     *
     * @param string $projectDirectory The path to the project directory
     */
    private function createLicense(string $projectDirectory, User $owner)
    {
        $this->logger->log("Creating the LICENSE file in the project directory '$projectDirectory'", Logger::DEBUG);

        // Get the path to the LICENSE file
        $licensePath = self::getTemplatePath() . 'LICENSE';

        // Check if the file exists
        if (!is_file($licensePath)) {
            $this->throwError("The LICENSE file could not be found at '$licensePath'");
        }

        // Copy the file to the project directory
        $projectLicensePath = $projectDirectory . DIRECTORY_SEPARATOR . 'LICENSE';
        if (!copy($licensePath, $projectLicensePath)) {
            $this->throwError("The LICENSE file could not be copied to '$projectDirectory'");
        }

        // Replace the year
        $this->replaceTemplateLiterals($projectLicensePath, [
            'year' => date('Y'),
            'author' => $owner->name,
        ]);

        $this->logger->log("The LICENSE file has been created in the project directory '$projectDirectory'", Logger::DEBUG);
    }

    /**
     * Creates the README.md file in the project directory
     *
     * @param string $projectDirectory The path to the project directory
     */
    private function createReadme(string $projectDirectory, Project $project, User $owner)
    {
        $this->logger->log("Creating the README.md file in the project directory '$projectDirectory'", Logger::DEBUG);

        // Get the path to the README.md file
        $readmePath = self::getTemplatePath() . 'README.md';

        // Check if the file exists
        if (!is_file($readmePath)) {
            $this->throwError("The README.md file could not be found at '$readmePath'");
        }

        // Copy the file to the project directory
        $projectReadmePath = $projectDirectory . DIRECTORY_SEPARATOR . 'README.md';
        if (!copy($readmePath, $projectReadmePath)) {
            $this->throwError("The README.md file could not be copied to '$projectDirectory'");
        }

        // Replace the author
        $this->replaceTemplateLiterals($projectReadmePath, [
            'author' => $owner->name,
            'title' => $project->title,
            'docs' => $project->docsRepo,
            'source' => $project->codeRepo,
        ]);

        $this->logger->log("The README.md file has been created in the project directory '$projectDirectory'", Logger::DEBUG);
    }

    /**
     * Copy the examples directory to the project directory
     * 
     * @param string $docsDirectory The path to the docs directory
     */
    private function copyExamples(string $docsDirectory)
    {
        $this->logger->log("Copying the examples to the docs directory '$docsDirectory'", Logger::DEBUG);

        // Get the path to the examples directory
        $examplesPath = self::getTemplatePath() . 'examples';

        // Check if the directory exists
        if (!is_dir($examplesPath)) {
            $this->throwError("The examples directory could not be found at '$examplesPath'");
        }

        // Copy the directory to the docs directory
        if (!copy($examplesPath, $docsDirectory . DIRECTORY_SEPARATOR . 'examples')) {
            $this->throwError("The examples directory could not be copied to '$docsDirectory'");
        }

        $this->logger->log("The examples have been copied to the docs directory '$docsDirectory'", Logger::DEBUG);
    }

    /**
     * Copy the index.md file to the docs directory
     * 
     * @param string $docsDirectory The path to the docs directory
     * @param Project $project The project
     */
    private function copyIndex(string $docsDirectory, Project $project)
    {
        $this->logger->log("Copying the index.md file to the docs directory '$docsDirectory'", Logger::DEBUG);

        // Get the path to the index.md file
        $indexPath = self::getTemplatePath() . 'index.md';

        // Check if the file exists
        if (!is_file($indexPath)) {
            $this->throwError("The index.md file could not be found at '$indexPath'");
        }

        // Copy the file to the docs directory
        if (!copy($indexPath, $docsDirectory . DIRECTORY_SEPARATOR . 'index.md')) {
            $this->throwError("The index.md file could not be copied to '$docsDirectory'");
        }

        // Replace the placeholders
        $this->replaceTemplateLiterals($docsDirectory . DIRECTORY_SEPARATOR . 'index.md', [
            'title' => $project->title,
            'description' => $project->description,
            'source' => $project->codeRepo,
        ]);

        $this->logger->log("The index.md file has been copied to the docs directory '$docsDirectory'", Logger::DEBUG);
    }

    /**
     * Copy the favicon to the docs directory
     * 
     * @param string $docsDirectory The path to the docs directory
     * @param Project $project The project
     */
    private function copyFavicon(string $docsDirectory, Project $project)
    {
        $this->logger->log("Copying the favicon to the docs directory '$docsDirectory'", Logger::DEBUG);

        // Get the path to the favicon
        $faviconPath = self::getTemplatePath() . 'favicon.svg';

        // Check if the file exists
        if (!is_file($faviconPath)) {
            $this->throwError("The favicon could not be found at '$faviconPath'");
        }

        // Copy the file to the docs directory
        if (!copy($faviconPath, $docsDirectory . DIRECTORY_SEPARATOR . 'favicon.svg')) {
            $this->throwError("The favicon could not be copied to '$docsDirectory'");
        }

        // Replace the placeholders
        $this->replaceTemplateLiterals($docsDirectory . DIRECTORY_SEPARATOR . 'favicon.svg', [
            'favicon' => $project->logo,
        ]);

        $this->logger->log("The favicon has been copied to the docs directory '$docsDirectory'", Logger::DEBUG);
    }

    /**
     * Copy the tags to the docs directory
     * 
     * @param string $docsDirectory The path to the docs directory
     */
    private function copyTags(string $docsDirectory)
    {
        $this->logger->log("Copying the tags to the docs directory '$docsDirectory'", Logger::DEBUG);

        // Get the path to the tags directory
        $tagsPath = self::getTemplatePath() . 'tags.md';

        // Check if the file exists
        if (!is_file($tagsPath)) {
            $this->throwError("The tags file could not be found at '$tagsPath'");
        }

        // Copy the file to the docs directory
        if (!copy($tagsPath, $docsDirectory . DIRECTORY_SEPARATOR . 'tags.md')) {
            $this->throwError("The tags file could not be copied to '$docsDirectory'");
        }

        $this->logger->log("The tags have been copied to the docs directory '$docsDirectory'", Logger::DEBUG);
    }

    /**
     * Copy the css directory to the docs directory
     * 
     * @param string $docsDirectory The path to the docs directory
     */
    private function copyCss(string $docsDirectory)
    {
        $this->logger->log("Copying the css to the docs directory '$docsDirectory'", Logger::DEBUG);

        // Get the path to the css directory
        $cssPath = self::getTemplatePath() . 'css';

        // Check if the directory exists
        if (!is_dir($cssPath)) {
            $this->throwError("The css directory could not be found at '$cssPath'");
        }

        // Copy the directory to the docs directory
        if (!copy($cssPath, $docsDirectory . DIRECTORY_SEPARATOR . 'css')) {
            $this->throwError("The css directory could not be copied to '$docsDirectory'");
        }

        $this->logger->log("The css has been copied to the docs directory '$docsDirectory'", Logger::DEBUG);
    }

    /**
     * Copy the js directory to the docs directory
     * 
     * @param string $docsDirectory The path to the docs directory
     */
    private function copyJs(string $docsDirectory)
    {
        $this->logger->log("Copying the js to the docs directory '$docsDirectory'", Logger::DEBUG);

        // Get the path to the js directory
        $jsPath = self::getTemplatePath() . 'js';

        // Check if the directory exists
        if (!is_dir($jsPath)) {
            $this->throwError("The js directory could not be found at '$jsPath'");
        }

        // Copy the directory to the docs directory
        if (!copy($jsPath, $docsDirectory . DIRECTORY_SEPARATOR . 'js')) {
            $this->throwError("The js directory could not be copied to '$docsDirectory'");
        }

        $this->logger->log("The js has been copied to the docs directory '$docsDirectory'", Logger::DEBUG);
    }

    /**
     * Copy the mkdocs.yml file to the project directory and replace the placeholders
     *
     * @param string $docsDirectory The path to the docs directory
     * @param Project $project The project
     * @param User $owner The owner of the project
     */
    private function createMkdocsYml(string $docsDirectory, Project $project, User $owner)
    {
        $this->logger->log("Creating the mkdocs.yml file in the docs directory '$docsDirectory'", Logger::DEBUG);

        // Get the path to the mkdocs.yml file
        $mkdocsPath = self::getTemplatePath() . 'mkdocs.yml';

        // Check if the file exists
        if (!is_file($mkdocsPath)) {
            $this->throwError("The mkdocs.yml file could not be found at '$mkdocsPath'");
        }

        // Copy the file to the docs directory
        $projectMkdocsPath = $docsDirectory . DIRECTORY_SEPARATOR . 'mkdocs.yml';
        if (!copy($mkdocsPath, $projectMkdocsPath)) {
            $this->throwError("The mkdocs.yml file could not be copied to '$docsDirectory'");
        }

        // Darkmode
        $this->replaceTemplateLiterals($projectMkdocsPath, [
            'darkmode' => $project->wantDarkMode ? '# Dark theme toggle
                - media: "(prefers-color-scheme: light)"
                  scheme: {{ color }}
                  toggle:
                      icon: material/weather-sunny
                      name: Switch to dark mode
                - media: "(prefers-color-scheme: dark)"
                  scheme: slate
                  toggle:
                      icon: material/weather-night
                      name: Switch to light mode'
                : 'scheme: {{ color }}',
        ]);


        // CSS
        $this->replaceTemplateLiterals($projectMkdocsPath, [
            'custom_css' => $project->wantCSS ? '# Custom CSS file
                extra_css:
                    - css/custom.css'
                : '',
        ]);

        // JS
        $this->replaceTemplateLiterals($projectMkdocsPath, [
            'custom_js' => $project->wantJS ? '# Custom JS file
                extra_javascript:
                    - js/custom.js'
                : '',
        ]);

        // Tags
        $this->replaceTemplateLiterals($projectMkdocsPath, [
            'tags' => $project->wantTags ? '- tags:
                tags_file: "tags.md"'
                : '',
        ]);

        // Examples
        $this->replaceTemplateLiterals($projectMkdocsPath, [
            'examples' => $project->wantExamples ? '- Examples:
                - Start:  "examples/Start.md"
                - Admonition: "examples/Admonition.md"
                - "Code Blocks": "examples/CodeBlock.md"
                - Tabs:  "examples/Tabs.md"'
                : '',
        ]);

        // Search
        $this->replaceTemplateLiterals($projectMkdocsPath, [
            'search' => $project->wantSearch ? '- search' : '',
        ]);

        // Replace the author
        $this->replaceTemplateLiterals($projectMkdocsPath, [
            'title' => $project->title,
            'color' => strtolower(str_replace('_', ' ', $project->color->name)),
            'favicon' => $project->favicon,
            'font' => ucwords(str_replace('_', ' ', $project->font->name)),
            'year' => date('Y'),
            'author' => $owner->name,
            'docs_repo' => $project->docsRepo,
            'code_repo' => $project->codeRepo,
        ]);

        $this->logger->log("The mkdocs.yml file has been created in the docs directory '$docsDirectory'", Logger::DEBUG);
    }

    /**
     * Create the structure of the docs directory
     * 
     * @param string $docsDirectory The path to the docs directory
     * @param Project $project The project
     */
    private function createStructure(string $docsDirectory, Project $project)
    {
        // Check if structure was not changed
        if ($project->structure == '[docs]') {
            $this->logger->log("The structure of the docs directory '$docsDirectory' has not been changed", Logger::DEBUG);
            return;
        }

        $this->logger->log('Creating the structure of the docs directory JSON', Logger::DEBUG);

        // Decode the JSON-encoded structure
        $structure = json_decode($project->structure);

        try {
            // Create the structure
            $this->createStructureRecursive($docsDirectory, $structure);
            $this->logger->log('The structure of the docs directory JSON has been created', Logger::DEBUG);
        } catch (Exception $e) {
            $this->logger->log('The structure of the docs directory could not be created because of ' . $e->getMessage(), Logger::ERROR);
        }
    }

    /**
     * Create the structure of the docs directory recursively
     *
     * @param string $docsDirectory
     * @param array $structure
     */
    private function createStructureRecursive(string $docsDirectory, array $structure)
    {
        foreach ($structure as $item) {
            // Create the file
            $filePath = $docsDirectory . DIRECTORY_SEPARATOR . $item->text;

            $isFolder = $item->type == 'folder';

            // Create the file or directory
            if ($isFolder) {
                if (!mkdir($filePath)) {
                    $this->throwError("The directory '$filePath' could not be created");
                }
                // Check if any item has the parent id of the current item
                $children = array_filter($structure, function ($subitem) use ($item) {
                    return $subitem->parent == $item->id;
                });

                // Create the substructure
                if (isset($children)) {
                    $this->createStructureRecursive($docsDirectory, $children);
                }
            } else {
                if (!touch($filePath)) {
                    $this->throwError("The file '$filePath' could not be created");
                }
            }
        }
    }

    /**
     * Create a weekly journal
     *
     * @param string $docsDirectory The path to the docs directory
     * @param Project $project The project
     */
    private function createWeeklyJournal(string $docsDirectory, Project $project)
    {
        $this->logger->log('Creating the weekly journal', Logger::DEBUG);

        // While the current date is before the end date
        $currentDate = $project->fromDate;

        // Create the weekly journal
        $week = 1;
        while ($currentDate <= $project->toDate) {
            // Create the filename with the date 
            $fileName = str_pad($week, 4, '0', STR_PAD_LEFT) . '_Week.md';

            // Get the path to the file
            $filePath = $docsDirectory . DIRECTORY_SEPARATOR . $fileName;

            // Create the file
            if (!touch($filePath)) {
                $this->throwError("The file '$filePath' could not be created");
            }

            $week++;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' + 7 days'));
        }

        $this->logger->log('The weekly journal has been created', Logger::DEBUG);
    }

    /**
     * Create a zip file of the docs directory
     * 
     * @param string $projectDirectory The path to the project directory
     */
    private function createZip(string $projectDirectory): string
    {
        $this->logger->log('Creating the zip file', Logger::DEBUG);

        // Get the path to the zip file
        $zipPath = $projectDirectory . DIRECTORY_SEPARATOR . 'project.zip';

        // Create the zip file
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            $this->throwError("The zip file could not be created at '$zipPath'");
        }

        // Add the directory to the zip file
        $this->addDirectoryToZip($zip, $projectDirectory);

        // Close the zip file
        $zip->close();

        $this->logger->log("The zip file has been created at '$zipPath'", Logger::DEBUG);

        return $zipPath;
    }

    /**
     * Add a directory to a zip file
     * 
     * @param ZipArchive $zip The zip file
     * @param string $directory The path to the directory
     * @param string $parentDirectory The path to the parent directory
     */
    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $parentDirectory = '')
    {
        // Get the files and directories in the directory
        $files = scandir($directory);

        // Add the files and directories to the zip file
        foreach ($files as $file) {
            // Skip the current and parent directory
            if ($file == '.' || $file == '..') {
                continue;
            }

            // Get the path to the file
            $filePath = $directory . DIRECTORY_SEPARATOR . $file;

            // Check if the file is a directory
            if (is_dir($filePath)) {
                // Add the directory to the zip file
                $this->addDirectoryToZip($zip, $filePath, $parentDirectory . $file . DIRECTORY_SEPARATOR);
            } else {
                // Add the file to the zip file
                $zip->addFile($filePath, $parentDirectory . $file);
            }
        }
    }

    /**
     * Download the zip file
     * 
     * @param string $zipPath The path to the zip file
     */
    private function downloadZip(string $zipPath)
    {
        $this->logger->log('Downloading the zip file', Logger::DEBUG);

        if (file_exists($zipPath)) {
            // Get the name of the zip file
            $zipName = basename($zipPath);

            // Set the headers
            // https://www.w3docs.com/snippets/php/automatic-download-file.html
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($zipPath));
            flush(); // Flush system output buffer

            // Read the zip file
            readfile($zipPath);
        } else {
            $this->throwError("The zip file could not be found at '$zipPath'");
        }

        $this->logger->log('The zip file has been downloaded', Logger::INFO);
    }

    /**
     * Delete the project directory and its contents
     *
     * @param string $projectDirectory The path to the project directory
     */
    private function deleteProjectDirectory(string $projectDirectory)
    {
        $this->logger->log("Deleting the project directory '$projectDirectory'", Logger::DEBUG);

        // Delete the project directory
        if (!rmdir($projectDirectory)) {
            $this->throwError("The project directory '$projectDirectory' could not be deleted");
        }

        $this->logger->log("The project directory '$projectDirectory' has been deleted", Logger::DEBUG);
    }
}
