<?php

use Monolog\Logger;

class ProjectController extends Controller
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
     * There is no default action
     * 
     * Redirects to the dashboard
     */
    public function index()
    {
        redirect($this::class . '/create', true);
    }

    /**
     * Deletes an existing project
     *
     * @param int $projectId The ID of the project
     */
    public function delete(int $projectId = -1)
    {
        if ($projectId > 0) {
            $this->projectRepository->delete($projectId, SessionManager::getCurrentUserId());
        }

        redirect('', true);
    }

    /**
     * Shows the creation form for a new project
     * 
     * @param int $currentStep The current step of the creation process
     * @param bool $prev Indicates if the user wants to go back to the previous step
     */
    public function create(int $currentStep = 0, bool $prev = false)
    {
        // Create can only be called with a valid step
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET' && $currentStep != 0) {
            // GET requests should start from the beginning
            redirect($this::class . '/create/' . '0', true);
            return;
        }

        // The url where the form is submitted to
        $formUrl = URLROOT . "/" . $this::class . '/create/';

        // Show the view
        $this->projectView($formUrl, $currentStep, null, 3, $prev, true);
    }

    /**
     * Edits an existing project
     *
     * @param int $projectId The ID of the project
     * @param int $currentStep The current step of the creation process
     * @param int $maxPage The maximum page of the process
     * @param bool $prev Indicates if the user wants to go back to the previous step
     */
    public function edit(int $projectId = 0, int $currentStep = 0, bool $prev = false)
    {
        if ($projectId <= 0) {
            redirect($this::class . '/create', true);
            return;
        }

        // Get the project from the database
        $isAdmin = SessionManager::hasRole($this->loadEnum('role', 'admin')->value);
        $project = $this->projectRepository->getProjectById($projectId, SessionManager::getCurrentUserId(), $isAdmin);

        // Check if the project exists
        if ($project === null) {
            redirect($this::class . '/create', true);
            return;
        }

        // The url where the form is submitted to
        $formUrl = URLROOT . "/" . $this::class . '/edit/' . $projectId . '/';

        // Show the view
        $isOwner = $project->userId == SessionManager::getCurrentUserId();
        $this->projectView($formUrl, $currentStep, $project, 4, $prev, $isOwner);
    }

    /**
     * Shows the project view
     *
     * @param string $formUrl The url where the form is submitted to
     * @param integer $currentStep The current step of the creation process
     * @param Project|null $project The project to edit
     * @param integer $maxPage The maximum page of the process
     * @param bool $prev Indicates if the user wants to go back to the previous step
     */
    public function projectView(string $formUrl, int $currentStep, Project|null $project, int $maxPage, bool $prev, bool $isOwner)
    {
        $this->logger->log('Showing the project view on page ' . $currentStep, Logger::DEBUG);

        // Init form data
        $message = [
            'title' => '',
            'text' => '',
        ];
        $data = array();
        $prevStep = max($currentStep - 1, 0);

        // Check if the form was submitted
        $isPost = strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';

        // Check CSRF token
        if ($isPost && !SessionManager::isCSRFTokenValid($_POST['csrf_token'])) {
            $this->logger->log('CSRF token of user ' . SessionManager::getCurrentUserId() . ' is invalid', Logger::WARNING);
            $message['title'] = 'The CSRF token is invalid';
            $message['text'] = 'Your request seems to be faulty. Please refresh the page and try again!';
        } else {
            // Confirmed or rejected projects can view the confirmation page
            $isAdmin = SessionManager::hasRole($this->loadEnum('role', 'admin')->value);

            // Load all sites and only allow owners to edit them
            $generalData = $this->generalPage($project, $isPost && $isOwner);
            $appearenceData = $this->appearencePage($project, $isPost && $isOwner);
            $structureData = $this->structurePage($project, $isPost && $isOwner);
            // Only admins can confirm projects
            $evaluationData = $this->evaluationPage($project, $isPost && $isAdmin);

            // Merge all data to one array
            $data = array_merge($data, $generalData, $appearenceData, $structureData, $evaluationData);

            if ($isPost) {
                // Check if there are any errors
                $hasErrors = false;
                switch ($currentStep) {
                    case 0:
                        $hasErrors = $this->hasError($generalData);
                        break;
                    case 1:
                        $hasErrors = $this->hasError($appearenceData);
                        break;
                    case 2:
                        $hasErrors = $this->hasError($structureData);
                        break;
                    case 3:
                        $hasErrors = $this->hasError($evaluationData);
                        break;
                    default:
                        $this->logger->log('Invalid step ' . $currentStep . ' of user ' . SessionManager::getCurrentUserId(), Logger::WARNING);
                        $hasErrors = true;
                        break;
                }

                // Do not go to next page if there are errors
                if (!$hasErrors) {
                    $currentStep++;

                    // Has reached last page
                    if ($currentStep >= $maxPage && !$prev) {
                        // Merge all data
                        $this->saveProject($project, $data);
                    }
                }
            }
        }

        // Set the CSRF token
        $data['csrf_token'] = SessionManager::getCsrfToken();

        $currentStep = $prev ? $prevStep : $currentStep;

        // Render the view
        $this->render('project/base', [
            'form_url' => $formUrl,
            'progress' => ($currentStep + 1) * (100.0 / (float)$maxPage),
            'message' => $message,
            'currentPage' => $currentStep,
            'data' => $data,
            'is_not_owner' => isset($project) && !$isOwner,
        ]);
    }

    // --- Pages --- //
    #region Pages 

    /**
     * Shows the general page
     * 
     * @param Project $project The project to edit
     * @param boolean $isPost Whether the form was submitted
     * @return array The data to pass to the view
     */
    public function generalPage(Project|null $project, bool $isPost): array
    {
        // Init the form data
        $data = [
            'title' => '',          // From field data
            'title_err' => '',      // Field error message
            'from' => date('Y-m-d'),
            'to' => date('Y-m-d'),
            'date_err' => '',
            'description' => '',
            'description_err' => '',
            'repo_docs' => '',
            'repo_docs_err' => '',
            'repo_code' => '',
            'repo_code_err' => '',
            'wantReadme' => true,
            'wantIgnore' => true,
            'wantCSS' => false,
            'wantJS' => false,
            'wantPages' => true,
        ];

        // Check if the form was submitted or requested
        if ($isPost) {
            // Sanitize POST data
            $data['title'] = $title = trim(htmlspecialchars($_POST['title']));
            $data['description'] = $description = trim(htmlspecialchars($_POST['description']));
            $data['from'] = $from = date('Y-m-d', strtotime($_POST['from']));
            $data['to'] = $to = date('Y-m-d', strtotime($_POST['to']));
            $data['repo_docs'] = $repo_docs = trim(htmlspecialchars($_POST['repo_docs']));
            $data['repo_code'] = $repo_code = trim(htmlspecialchars($_POST['repo_code']));

            // Set the checkboxes
            $data['wantReadme'] = filter_has_var(INPUT_POST, 'wantReadme');
            $data['wantIgnore'] = filter_has_var(INPUT_POST, 'wantIgnore');
            $data['wantCSS'] = filter_has_var(INPUT_POST, 'wantCSS');
            $data['wantJS'] = filter_has_var(INPUT_POST, 'wantJS');
            $data['wantPages'] = filter_has_var(INPUT_POST, 'wantPages');

            // Validate the data
            $data['title_err'] = $this->validateLength('title', $title, 2, 60);
            if (empty($data['title_err'])) {
                $isAdmin = SessionManager::hasRole($this->loadEnum('role', 'admin')->value);
                if (!$isAdmin && $this->projectRepository->existsProjectWithName($title, isset($project) ? $project->id : -1, SessionManager::getCurrentUserId())) {
                    $data['title_err'] = 'A project with this name already exists';
                }
            }

            $data['description_err'] = $this->validateLength('description', $description, 10, 255);

            $data['repo_docs_err'] = $this->validateUrl('documentation repository', $repo_docs);
            $data['repo_code_err'] = $this->validateUrl('source code repository', $repo_code);

            $data['date_err'] = $this->validateDate($from, $to);

            // Show the form again with the errors
        } elseif ($project) {
            // Set the form data from the project
            $data['title'] = $project->title;
            $data['description'] = $project->description;
            $data['from'] = $project->fromDate;
            $data['to'] = $project->toDate;
            $data['repo_docs'] = $project->docsRepo;
            $data['repo_code'] = $project->codeRepo;
            $data['wantReadme'] = $project->wantReadme;
            $data['wantIgnore'] = $project->wantIgnore;
            $data['wantCSS'] = $project->wantCSS;
            $data['wantJS'] = $project->wantJS;
            $data['wantPages'] = $project->wantPages;
        }

        // Return the data
        return $data;
    }

    /**
     * Shows the appearence page
     *
     * @param Project $project The project to edit
     * @param boolean $isPost Whether the form was submitted
     * @return array The data to pass to the view
     */
    public function appearencePage(Project|null $project, bool $isPost): array
    {
        // Init the form data
        $data = [
            'color' => $this->loadEnum('project/color', 0)->name,
            'color_err' => '',
            'color_options' => array_column(Color::cases(), 'name'),
            'font' => $this->loadEnum('project/font', 0)->name,
            'font_err' => '',
            'font_options' => array_column(Font::cases(), 'name'),
            'wantDarkMode' => true,
            'wantCopyright' => true,
            'wantSearch' => true,
            'wantTags' => false,
            'logo' => '',
            'logo_err' => '',
        ];

        // Check if the form was submitted or requested
        if ($isPost) {
            // Sanitize POST data
            $data['color'] = $color = trim(htmlspecialchars($_POST['color']));
            $data['font'] = $font = trim(htmlspecialchars($_POST['font']));
            $data['wantDarkMode'] = filter_has_var(INPUT_POST, 'wantDarkMode');
            $data['wantCopyright'] = filter_has_var(INPUT_POST, 'wantCopyright');
            $data['wantSearch'] = filter_has_var(INPUT_POST, 'wantSearch');
            $data['wantTags'] = filter_has_var(INPUT_POST, 'wantTags');
            $data['logo'] = $logo = trim(htmlspecialchars($_POST['logo']));

            // Validate the data
            $data['color_err'] = $this->validateEnum('color', 'project/color', $color);
            $data['font_err'] = $this->validateEnum('font', 'project/font', $font);
            $data['logo_err'] = $this->validateLogo($logo);

            // Show the form again with the errors
        } elseif ($project) {
            // Set the form data from the project
            $data['color'] = $project->color->name;
            $data['font'] = $project->font->name;
            $data['wantDarkMode'] = $project->wantDarkMode;
            $data['wantCopyright'] = $project->wantCopyright;
            $data['wantSearch'] = $project->wantSearch;
            $data['wantTags'] = $project->wantTags;
            $data['logo'] = $project->logo;
        }

        // Return the data
        return $data;
    }

    /**
     * Shows the structure page
     *
     * @param Project $project The project to edit
     * @param boolean $isPost Whether the form was submitted
     * @return array The data to pass to the view
     */
    public function structurePage(Project|null $project, bool $isPost): array
    {
        // Init the form data
        $defaultJson = '[ "docs" ]';

        $data = [
            'wantJournal' => true,
            'wantExamples' => false,
            'structure' => $defaultJson,
            'structure_err' => '',
        ];

        // Check if the form was submitted or requested
        if ($isPost) {
            $data['wantJournal'] = filter_has_var(INPUT_POST, 'wantJournal');
            $data['wantExamples'] = filter_has_var(INPUT_POST, 'wantExamples');
            $data['structure'] = $structure = trim($_POST['structure']);

            // Validate the data
            $data['structure_err'] = $this->validateStructure($structure);
        } elseif ($project) {
            // Set the form data from the project
            $data['wantJournal'] = $project->wantJournal;
            $data['wantExamples'] = $project->wantExamples;

            $data['structure'] = isset($project->structure) && !empty($project->structure) ? $project->structure :  $defaultJson;
        }

        // Return the data
        return $data;
    }

    /**
     * Shows the evaluation page
     *
     * @param Project $project The project to edit
     * @param boolean $isPost Whether the form was submitted
     * @return array The data to pass to the view
     */
    public function evaluationPage(Project|null $project, bool $isPost): array
    {
        $data = [
            'comment' => '',
            'comment_err' => '',
            'status' => $this->loadEnum('project/status', 0)->name,
            'status_err' => '',
            'status_options' => array_column(Status::cases(), 'name'),
        ];

        // Check if the form was submitted or requested
        if ($isPost) {
            $data['comment'] = $comment = trim(htmlspecialchars($_POST['comment']));
            $data['status'] = $status = trim(htmlspecialchars($_POST['status']));

            // Validate the data
            $data['comment_err'] = $this->validateLength('comment', $comment, 10, 255);
            $data['status_err'] = $this->validateEnum('status', 'project/status', $status);
        } elseif ($project) {
            // Set the form data from the project
            $data['comment'] = $project->comment;
            $data['status'] = $project->status->name;
        }

        // Return the data
        return $data;
    }

    #endregion

    // --- Validation methods --- //
    #region Validation methods

    /**
     * Validates the length of a string
     *
     * @param string $name The name of the field
     * @param string $value The value of the field
     * @param integer $min The minimum length
     * @param integer $max The maximum length
     * @return string The error message
     */
    private static function validateLength(string $name, string $value, int $min, int $max): string
    {
        // Store the error message
        $error = '';

        if (empty($value)) {
            $error = 'The ' . $name . ' is required';
        } elseif (strlen($value) < $min || strlen($value) > $max) {
            $error = 'The ' . $name . ' must be between ' . $min . ' and ' . $max . ' characters long';
        }

        return $error;
    }

    /**
     * Validates a url
     *
     * @param string $name The name of the field
     * @param string $value The url to validate
     * @return string The error message
     */
    private static function validateUrl(string $name, string $value): string
    {
        // Store the error message
        $error = ProjectController::validateLength($name, $value, 10, 255);

        if (empty($error) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $error = 'The ' . $name . ' is not a valid URL';
        }

        return $error;
    }

    /**
     * Validates two dates to see if they are valid and in the correct order
     *
     * @param string $dateFrom The start date
     * @param string $dateTo The end date
     * @return string The error message
     */
    private static function validateDate(string $dateFrom, string $dateTo): string
    {
        // Store the error message
        $error = '';

        if (empty($dateFrom)) {
            $error = 'The from date is required';
        } elseif (empty($dateTo)) {
            $error = 'The to date is required';
        } elseif (strtotime($dateFrom) > strtotime($dateTo)) {
            $error = 'The start date must be before the end date';
        }

        return $error;
    }

    private function validateEnum(string $name, string $enum, string $value): string
    {
        // Store the error message
        $error = '';

        if (empty($value)) {
            $error = 'The ' . $name . ' is required';
        } elseif (!$this->loadEnum($enum, $value)) {
            $error = 'The ' . $name . ' is not a valid option';
        }

        return $error;
    }

    /**
     * Validates the logo
     * 
     * @param string $logo The logo to validate
     * @return string The error message if there is one
     */
    private static function validateLogo(string $logo): string
    {
        // Store the error message
        $error = '';

        if (empty($logo)) {
            $error = 'The logo is required';
        } elseif (!(preg_match('/^data:image\/[\w+]+;base64,.*/i', $logo) || filter_var($logo, FILTER_VALIDATE_URL))) {
            $error = 'The logo must be a valid base64 encoded image';
        } elseif (ceil(((strlen($logo) * 6) / 8) / 1024) > 512) {
            // Check if the logo is bigger than 512KB
            $error = 'The logo must be at most 500KB';
        }

        // Return the error message if there is one
        return $error;
    }

    /**
     * Validates the structure
     * 
     * @param string $structure The structure to validate
     * @return string The error message if there is one
     */
    private static function validateStructure(string $structure): string
    {
        // Store the error message
        $error = '';

        // Check if the structure is valid JSON
        if (empty($structure)) {
            $error = 'The structure is required';
        } else if (strlen($structure) > 10000) {
            $error = 'The structure must be at most 10000 characters long';
        } elseif (!json_decode($structure)) {
            $error = 'The structure must be valid JSON';
        }

        return $error;
    }

    #endregion

    // --- Helper methods --- //
    #region Helper methods

    /**
     * Loads the project model and sets the properties 
     * 
     * @param array $result The result from the database
     */
    private function loadProject(array $result): Project|null
    {
        // Check if the project exists
        if ($result == null) {
            return null;
        }

        // Create the project
        $project = $this->loadModel('project/project');
        $project->id = $result['id'] ?? -1;
        $project->userId = SessionManager::getCurrentUserId();
        $project->title = $result['title'];
        $project->description = $result['description'];
        $project->createdAt = date('Y-m-d H:i:s');
        $project->fromDate = date('Y-m-d H:i:s', strtotime($result['from']));
        $project->toDate = date('Y-m-d H:i:s', strtotime($result['to']));
        $project->docsRepo = $result['repo_docs'];
        $project->codeRepo = $result['repo_code'];
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
        $project->confirmedBy = $result['confirmedBy'] ?? null;
        $project->comment = $result['comment'] ?? '';
        $project->status = $this->loadEnum('project/status', $result['status']);
        $project->downloadUrl = $result['downloadUrl'] ?? '';

        return $project;
    }

    /**
     * Saves the project to the database and sends an email to the user
     * 
     * If it has been edited, the status will be reset to 'IN_PROGRESS'
     *
     * @param Project $project The project to save
     * @param array $data The data to save
     */
    private function saveProject(Project|null $project, array $data)
    {
        // Save the project
        $newProject = $this->loadProject($data);

        // Check if the project is new
        if (isset($project) && $project->id > 0) {
            // Set the id of the project
            $newProject->id = $project->id;

            if ($this->hasChanges($newProject, $project)) {
                // Reset the status
                $newProject->status = $this->loadEnum('project/status', 0);
                $this->logger->log('The project ' . $newProject->id . ' was edited by ' . SessionManager::getCurrentUserId(), Logger::INFO);
            } elseif ($project->status->value != $newProject->status->value) {
                // Set the confirmed by
                $newProject->confirmedBy = SessionManager::getCurrentUserId();
                $this->logger->log('The status of project ' . $newProject->id . ' has changed to ' . $newProject->status->value . ' by ' . $newProject->confirmedBy, Logger::INFO);

                // Load the user repository
                $userRepository = $this->loadRepository('UserRepository');
                $owner = $userRepository->getUserById($project->userId);

                // Check if the owner exists
                if (isset($owner)) {
                    if ($owner->wantsUpdates) {
                        // Project URL
                        $projectUrl = URLROOT . '/ProjectController/edit/' . $newProject->id . '/3';

                        // Inform the user about the status change
                        $sg = new SendgridService();
                        $sg->sendStatusChanged($owner->name, $owner->email, $newProject->title, $projectUrl, $newProject->status->name);
                    } else {
                        $this->logger->log('User ' . $owner->name . 'does not want to receive updates by mail', Logger::INFO);
                    }
                } else {
                    $this->logger->log('Owner of project ' . $newProject->id . ' does not exist', Logger::WARNING);
                }
            }

            // Set the download URL
            if ((!isset($newProject->downloadUrl) ||  empty($newProject->downloadUrl)) && $newProject->status->value == $this->loadEnum('project/status', 1)->value) {
                $newProject->downloadUrl = md5($newProject->id . $newProject->title . $newProject->userId . $newProject->createdAt);
                $this->logger->log('The download URL of project ' . $newProject->id . ' has been set to ' . $newProject->downloadUrl, Logger::INFO);
            }
        }

        // // Save the project
        $this->projectRepository->save($newProject);
        redirect('', true);
    }

    /**
     * Checks if the array has errors
     *
     * @param array $data The array to check
     * @return bool True if there are errors, false otherwise
     */
    private function hasError(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_err') && !empty($value)) {
                $this->logger->log('The project could not be saved because of an error in the ' . $key . ' field', Logger::DEBUG);
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the project properties have changed
     *
     * @param Project $newProject The new project
     * @param Project $oldProject The old project
     * @return boolean True if the project has changed, false otherwise
     */
    private function hasChanges(Project $newProject, Project $oldProject): bool
    {
        return $newProject->title != $oldProject->title
            || $newProject->description != $oldProject->description
            || $newProject->docsRepo != $oldProject->docsRepo
            || $newProject->codeRepo != $oldProject->codeRepo
            || $newProject->wantReadme != $oldProject->wantReadme
            || $newProject->wantIgnore != $oldProject->wantIgnore
            || $newProject->wantCSS != $oldProject->wantCSS
            || $newProject->wantJS != $oldProject->wantJS
            || $newProject->wantPages != $oldProject->wantPages
            || $newProject->color->value != $oldProject->color->value
            || $newProject->font->value != $oldProject->font->value
            || $newProject->wantDarkMode != $oldProject->wantDarkMode
            || $newProject->wantCopyright != $oldProject->wantCopyright
            || $newProject->wantSearch != $oldProject->wantSearch
            || $newProject->wantTags != $oldProject->wantTags
            || $newProject->logo != $oldProject->logo
            || $newProject->wantJournal != $oldProject->wantJournal
            || $newProject->wantExamples != $oldProject->wantExamples
            || $newProject->structure != $oldProject->structure;
    }

    #endregion
}
