<?php

use Monolog\Logger;

class UserController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = $this->loadRepository('UserRepository');
    }

    // --- Endpoints --- //
    #region Endpoints

    /**
     * Redirects to the login page
     */
    public function index()
    {
        redirect('login/signin');
    }

    /**
     * Shows the login page with the login form
     */
    public function signIn()
    {
        if (SessionManager::isLoggedIn()) {
            redirect('dashboard', true);
            return;
        }

        // Init the form data
        $data = [
            'email' => '',          // From field data
            'email_err' => '',      // Field error message
            'password' => '',
            'password_err' => '',
        ];
        $message = '';

        // Check if the form was submitted or requested
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            // Sanitize POST data
            $data['email'] = $email = trim(htmlspecialchars($_POST['email']));
            $data['password'] = $password = trim($_POST['password']);

            // Validate the form
            $data['email_err'] = $this->validateEmail($email);
            $data['password_err'] = $this->validatePassword($password);

            // Only if there are no errors, try to login
            if (empty($data['email_err']) && empty($data['password_err'])) {
                // Check if the user exists and the password is correct
                $user = $this->userRepository->getUserByEmail($email);
                if (!$user || !password_verify($user->salt . $this->getSaltSeparator() . $password, $user->password)) {
                    $data['email_err'] = $data['password_err'] = 'The email or password is incorrect';
                } elseif (!$user->isVerified) {
                    // Check if the user is verified
                    $message = 'The email is not verified';
                } else {
                    // Save the credentials in the session
                    SessionManager::login($user);

                    // Log that the user has logged in
                    $this->logger->log("User '$user->id' has successfully signed in", Logger::INFO);

                    // Redirect to the dashboard
                    redirect('dashboard', true);
                    return;
                }

                $this->logger->log("Sign in for user '$email' failed!", Logger::INFO);
            }

            // Show the form again with the errors
        }

        // Load the view
        $this->render('user/signin', [
            'urlroot' => URLROOT . '/login/signIn',
            'data' => $data,
            'message' => $message
        ]);
    }

    /**
     * Shows the register page with the registration form
     */
    public function signUp()
    {
        if (SessionManager::isLoggedIn()) {
            redirect('dashboard', true);
            return;
        }

        // Init the form data
        $data = [
            'name' => '',       // From field data
            'name_err' => '',   // Field error message
            'email' => '',
            'email_err' => '',
            'password' => '',
            'password_err' => '',
            'picture' => '',
            'picture_err' => '',
        ];
        $message = '';

        // Check if the form was submitted or requested
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            // Sanitize POST data
            $data['name'] = $name = trim(htmlspecialchars($_POST['name']));
            $data['email'] = $email = trim(htmlspecialchars($_POST['email']));
            $data['password'] = $password = trim($_POST['password']);
            $data['picture'] = $picture = trim(htmlspecialchars($_POST['picture']));

            // Validate the form
            $data['name_err'] = $this->validateName($name);
            $data['email_err'] = $this->validateEmail($email);
            $data['password_err'] = $this->validatePassword($password);
            $data['picture_err'] = $this->validatePicture($picture);

            // Only if there are no errors, try to register
            if (empty($data['name_err']) && empty($data['email_err']) && empty($data['password_err']) && empty($data['picture_err'])) {
                // Check if the user exists and the password is correct
                $user = $this->userRepository->getUserByEmail($email);
                if (!$user) {
                    $user = $this->createUser($email, $password, $name, $picture);

                    // Send the verification email
                    // TODO: Popup with the message that the email has been sent
                    $message = "The verification email has been sent. Please check your inbox.\nIf you don't see it, check your spam folder. \n\n!! This feature is not implemented yet, because it depends strongly on the database !!";

                    $this->userRepository->save($user);

                    // Log that the user has logged in
                    $this->logger->log("User '$user->email' has successfully signed up", Logger::INFO);
                } else {
                    $data['email_err'] = 'The email is already registered';
                    $this->logger->log("Sign up for user '$email' failed!", Logger::INFO);
                }
            }

            // Show the form again with the errors
        }

        // Load the view
        $this->render('user/signup', [
            'form_url' => URLROOT . '/login/signUp',
            'data' => $data,
            'message_title' => 'Next steps - Verification',
            'message' => $message
        ]);
    }

    /**
     * Shows the profile page with the profile form
     */
    public function profile()
    {
        // Check if the user is logged in
        // TODO: Make session manager working
        // if (!SessionManager::isLoggedIn()) {
        //     redirect('login/signin', true);
        //     return;
        // }

        // Init the form data
        $data = [
            'name' => '',       // From field data
            'name_err' => '',   // Field error message
            'email' => '',
            'email_err' => '',
            'wants_updates' => true,
            'password' => '',
            'password_err' => '',
            'picture' => '',
            'picture_err' => '',
            'csrf_token' => '',
        ];
        $message_title = '';
        $message = '';

        $passwordPlaceholder = 'The real password is not shown here!';

        // Check if the form was submitted or requested
        $isPost = strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';
        if ($isPost && !SessionManager::isCSRFTokenValid($_POST['csrf_token'])) {
            $this->logger->log('CSRF token of user ' . SessionManager::getCurrentUserId() . ' is invalid', Logger::WARNING);
            $message_title = 'The CSRF token is invalid';
            $message = 'Your request seems to be faulty. Please refresh the page and try again!';
        } elseif ($isPost) {
            // Sanitize POST data
            $data['name'] = $name = trim(htmlspecialchars($_POST['name']));
            $data['email'] = $email = trim(htmlspecialchars($_POST['email']));
            $data['wants_updates'] = $wantsUpdates = filter_has_var(INPUT_POST, 'wants_updates');
            $data['password'] = $password = trim($_POST['password']);
            $data['picture'] = $picture = trim(htmlspecialchars($_POST['picture']));

            // Validate the form
            $data['name_err'] = $this->validateName($name);
            $data['email_err'] = $this->validateEmail($email);
            if ($passwordPlaceholder != $password) {
                // Only validate the password if it was changed
                $data['password_err'] = $this->validatePassword($password);
            }
            $data['picture_err'] = $this->validatePicture($picture);

            // Only if there are no errors, try to register
            if (empty($data['name_err']) && empty($data['email_err']) && empty($data['password_err']) && empty($data['picture_err'])) {
                // Check if the user exists and the password is correct
                $user = $this->userRepository->getUserByEmail($email);
                if ($user && $user->id == SessionManager::getCurrentUserId()) {
                    // Check if email was changed
                    if ($user->email != $email) {
                        // Send the verification email
                        // TODO: Popup with the message that the email has been sent
                        $message_title = 'Verification needed';
                        $message = "Because you changed your email we send you a new verification link. Please reverify that this email is valid.\nIf you don't see it, check your spam folder. \n\n!! This feature is not implemented yet, because it depends strongly on the database !!";

                        // Generate a verification token and send it to the user
                        $user->isVerified = false;
                        $user->verificationToken = $this->generateSalt();
                    }

                    $user->name = $name;
                    $user->email = $email;
                    $user->wantsUpdates = $wantsUpdates;
                    // Only change the password if it was changed
                    if ($passwordPlaceholder != $password) {
                        // Generate a salt and hash the password
                        $user->salt = $this->generateSalt();
                        $user->password = password_hash($user->salt . $this->getSaltSeparator() . $password, PASSWORD_DEFAULT);
                    }
                    $user->picture = $picture;

                    $this->userRepository->save($user);

                    // Log that the user has logged in
                    $this->logger->log("User '$user->id' has successfully updated his profile", Logger::INFO);
                } else {
                    $data['email_err'] = 'The email is already registered';
                    $this->logger->log("Profile update for user '$email' failed!", Logger::INFO);
                }
            }
            // Show the form again with the errors
        } else {
            $user = $this->userRepository->getCurrentUser();

            if ($user) {
                $data['name'] = $user->name;
                $data['email'] = $user->email;
                $data['wants_updates'] = $user->wantsUpdates;
                $data['password'] = $passwordPlaceholder;
                $data['picture'] = $user->profilePicture;
            } else {
                $message_title = 'Profile not found';
                $message = 'Your profile could not be found. Please sign out of your account and try again to sign in!';
            }
            $data['csrf_token'] = SessionManager::getCsrfToken();
        }

        // Load the view
        $this->render('user/profile', ['form_url' => URLROOT . '/UserController/profile', 'data' => $data, 'message_title' => $message_title, 'message' => $message]);
    }

    #endregion

    // --- Validation methods --- //
    #region Validation methods

    /**
     * Validates the email address
     *
     * @param string $email The email address to validate
     * @return string The error message if there is one
     */
    private function validateEmail(string $email): string
    {
        // Store the error message
        $error = '';

        if (empty($email)) {
            $error = 'The email is required';
        } elseif (strlen($email) < 2 || strlen($email) > 255) {
            $error = 'The email must be between 2 and 255 characters';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'The email has an invalid format';
        } elseif (!checkdnsrr(substr(strrchr($email, "@"), 1), 'MX')) {
            // Check if the email domain has a valid MX record
            $this->logger->log("The MX record for the email domain '" . $email . "' could't be found", Logger::INFO);
            $error = 'The email domain could\'t be found';
        }

        // Return the error message if there is one
        return $error;
    }

    /**
     * Validates the password
     *
     * @param string $password The password to validate
     * @return string The error message if there is one
     */
    private static function validatePassword(string $password): string
    {
        // Store the error message
        $error = '';

        if (empty($password)) {
            $error = 'The password is required';
        } elseif (strlen($password) < 12) {
            $error = 'The password must be at least 12 characters long';
        } elseif (strlen($password) > 500) {
            $error = 'The password must be less than 500 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            // Check if has at least one uppercase letter
            $error = 'The password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            // Check if has at least one lowercase letter
            $error = 'The password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            // Check if has at least one number
            $error = 'The password must contain at least one number';
        } elseif (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            // Check if has at least one special character
            $error = 'The password must contain at least one special character';
        }

        // Return the error message if there is one
        return $error;
    }

    /**
     * Validates the name
     * 
     * @param string $name The name to validate
     * @return string The error message if there is one
     */
    private static function validateName(string $name): string
    {
        // Store the error message
        $error = '';

        if (empty($name)) {
            $error = 'The name is required';
        } elseif (strlen($name) < 2 || strlen($name) > 50) {
            $error = 'The name must be between 2 and 50 characters';
        }

        // Return the error message if there is one
        return $error;
    }

    /**
     * Validates the picture
     * 
     * @param string $picture The picture to validate
     * @return string The error message if there is one
     */
    private static function validatePicture(string $picture): string
    {
        // Store the error message
        $error = '';

        if (empty($picture)) {
            $error = 'The picture is required';
        } elseif (!preg_match('/^data:image\/\w+;base64,.*/i', $picture)) {
            $error = 'The picture must be a valid base64 encoded image';
        } elseif (ceil(((strlen($picture) * 6) / 8) / 1024) > 512) {
            // Check if the picture is bigger than 512KB
            $error = 'The picture must be at most 500KB';
        }

        // Return the error message if there is one
        return $error;
    }

    #endregion

    // --- Helper methods --- //
    #region Helper methods

    /**
     * Saves the user in the database
     *
     * @param string $email The email of the user
     * @param string $name The name of the user
     * @param string $password The password without the salt
     * @param string $profilePicture The base64 encoded profile picture
     * @return User The created user
     */
    private function createUser(string $email, string $password, string $name, string $profilePicture)
    {
        // Generate user model
        $user = $this->loadModel('User');

        // Fill the model with the data
        $user->name = $name;
        $user->email = $email;
        $user->wantsUpdates = true;
        $user->setRoles(array());
        $user->profilePicture = $profilePicture;

        // Generate a salt and hash the password
        $user->salt = $this->generateSalt();
        $user->password = password_hash($user->salt . $this->getSaltSeparator() . $password, PASSWORD_DEFAULT);

        // Generate a verification token and send it to the user
        $user->isVerified = false;
        $user->verificationToken = $this->generateSalt();

        return $user;
    }

    /**
     * Generates a random salt
     *
     * @return string The generated salt
     */
    private static function generateSalt(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Returns the separator for the salt
     *
     * So the separator can't be changed unintentionally
     *
     * @return string The separator (`$`) 
     */
    private function getSaltSeparator(): string
    {
        return '$';
    }

    #endregion
}
