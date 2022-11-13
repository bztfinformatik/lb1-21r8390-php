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
        if ($projectId <= 0) {
            $this->projectRepository->delete($projectId);
        }

        redirect('', true);
    }

    /**
     * Shows the creation form for a new project
     * 
     * @param int $currentStep The current step of the creation process
     */
    public function create(int $currentStep = 0)
    {
    }

    /**
     * Edits an existing project
     *
     * @param int $projectId The ID of the project
     * @param int $currentStep The current step of the creation process
     */
    public function edit(int $projectId, int $currentStep = 0)
    {
        // Init form data
        $message = [
            'title' => '',
            'text' => '',
        ];
        $data = array();

        // Get the project from the database
        $project = $this->projectRepository->getById($projectId);

        // Check if the project exists
        if ($project === null) {
            redirect($this::class . '/create', true);
            return;
        }

        // Check CSRF token
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && !SessionManager::isCSRFTokenValid($_POST['csrf_token'])) {
            $this->logger->log('CSRF token of user ' . SessionManager::getCurrentUserId() . ' is invalid', Logger::WARNING);
            $message['title'] = 'The CSRF token is invalid';
            $message['text'] = 'Your request seems to be faulty. Please refresh the page and try again!';
        } else {
            $data = array_merge($data, $this->generalPage($project));
            $data = array_merge($data, $this->appearencePage($project));
            $data = array_merge($data, $this->structurePage($project));
            $data = array_merge($data, $this->evaluationPage($project));

            // Only admins can confirm projects
            // Confirmed or rejected projects can view the confirmation page
            // if (SessionManager::hasRole($this->loadEnum('role', 'admin')) || !$project->isInProgress()) {

            if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
                $hasErrors = false;
                foreach ($data as $key => $value) {
                    if (str_ends_with($key, '_err') && $value != '') {
                        $hasErrors = true;
                        break;
                    }
                }
                if (!$hasErrors) {
                    $currentStep++;
                }

                if ($currentStep >= 4) {
                    $this->projectRepository->save($project);
                    redirect('', true);
                    return;
                }
            }
        }

        // Set the CSRF token
        $data['csrf_token'] = SessionManager::getCsrfToken();

        // Render the view
        $this->render('project/base', [
            'form_url' => URLROOT . "/" . $this::class . '/edit/' . $projectId . '/',
            'progress' => ($currentStep + 1) * 25,
            'message' => $message,
            'currentPage' => $currentStep,
            'data' => $data,
        ]);
    }

    // --- Pages --- //
    #region Pages 

    /**
     * Shows the general page
     * 
     * @param Project $project The project to edit
     * @return array The data to pass to the view
     */
    public function generalPage(Project $project = null): array
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
            'want_readme' => true,
            'want_ignore' => true,
            'want_css' => false,
            'want_js' => false,
            'want_pages' => true,
        ];

        // Check if the form was submitted or requested
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            // Sanitize POST data
            $data['title'] = $title = trim(htmlspecialchars($_POST['title']));
            $data['description'] = $description = trim(htmlspecialchars($_POST['description']));
            $data['from'] = $from = date('Y-m-d', strtotime($_POST['from']));
            $data['to'] = $to = date('Y-m-d', strtotime($_POST['to']));
            $data['repo_docs'] = $repo_docs = trim(htmlspecialchars($_POST['repo_docs']));
            $data['repo_code'] = $repo_code = trim(htmlspecialchars($_POST['repo_code']));

            // Set the checkboxes
            $data['want_readme'] = $want_readme =  filter_has_var(INPUT_POST, 'want_readme');
            $data['want_ignore'] = $want_ignore =  filter_has_var(INPUT_POST, 'want_ignore');
            $data['want_css'] = $want_css =  filter_has_var(INPUT_POST, 'want_css');
            $data['want_js'] = $want_js =  filter_has_var(INPUT_POST, 'want_js');
            $data['want_pages'] = $want_pages =  filter_has_var(INPUT_POST, 'want_pages');

            // Validate the data
            $data['title_err'] = $this->validateLength('title', $title, 2, 60);
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
            $data['want_readme'] = $project->wantReadme;
            $data['want_ignore'] = $project->wantIgnore;
            $data['want_css'] = $project->wantCSS;
            $data['want_js'] = $project->wantJS;
            $data['want_pages'] = $project->wantPages;
        }

        // Return the data
        return $data;
    }

    /**
     * Shows the appearence page
     *
     * @param Project $project The project to edit
     * @return array The data to pass to the view
     */
    public function appearencePage(Project $project = null): array
    {
        // Init the form data
        $data = [
            'color' => $this->loadEnum('project/color', 0),
            'color_err' => '',
            'color_options' => array_column(Color::cases(), 'name'),
            'font' => $this->loadEnum('project/font', 0),
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
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
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
            $data['color'] = $project->color;
            $data['font'] = $project->font;
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
     * @return array The data to pass to the view
     */
    public function structurePage(Project $project = null): array
    {
        // Init the form data
        $defaultJson = '{ "docs": { "css": "folder", "mkdocs.yml": "file"}}';

        $data = [
            'wantJournal' => '',
            'wantExamples' => '',
            'structure' => $defaultJson,
            'structure_err' => '',
        ];

        // Check if the form was submitted or requested
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $data['wantJournal'] = filter_has_var(INPUT_POST, 'wantDarkMode');
            $data['wantExamples'] = filter_has_var(INPUT_POST, 'wantCopyright');
            $data['structure'] = $structure = trim($_POST['structure']);

            // Validate the data
            $data['structure_err'] = $this->validateStructure($structure);
        } elseif ($project) {
            // Set the form data from the project
            $data['wantJournal'] = $project->wantJournal;
            $data['wantExamples'] = $project->wantExamples;

            $data['structure'] = isset($project->structure) ? $project->structure :  $defaultJson;
        }

        // Return the data
        return $data;
    }

    public function evaluationPage(Project $project = null): array
    {
        $data = [
            'comment' => '',
            'comment_err' => '',
            'status' => $this->loadEnum('project/status', 0),
            'status_err' => '',
            'status_options' => array_column(Status::cases(), 'name'),
        ];

        // Check if the form was submitted or requested
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $data['comment'] = $comment = trim(htmlspecialchars($_POST['comment']));
            $data['status'] = $status = trim(htmlspecialchars($_POST['status']));

            // Validate the data
            $data['comment_err'] = $this->validateLength('comment', $comment, 10, 255);
            $data['status_err'] = $this->validateEnum('status', 'project/status', $status);
        } elseif ($project) {
            // Set the form data from the project
            $data['comment'] = $project->comment;
            $data['status'] = $project->status;
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
        $error = '';

        if (empty($value)) {
            $error = 'The ' . $name . ' is required';
        } elseif (!filter_var($value, FILTER_VALIDATE_URL)) {
            $error = 'The ' . $name . ' is not a valid URL';
        }

        return $error;
    }

    /**
     * Validates two dates to see if they are valid and in the correct order
     *
     * @param Date $dateFrom The start date
     * @param Date $dateTo The end date
     * @return string The error message
     */
    private static function validateDate($dateFrom, $dateTo): string
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
        } elseif (!(preg_match('/^data:image\/\w+;base64,.*/i', $logo) || filter_var($logo, FILTER_VALIDATE_URL))) {
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
}
