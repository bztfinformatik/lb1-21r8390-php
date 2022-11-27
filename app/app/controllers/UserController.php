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
     * There is no default action
     * 
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
        $allowReset = false;

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

                // Allow the user to reset the password if the account exists
                $allowReset = isset($user);

                if (!isset($user) || !password_verify($user->salt . $this->getSaltSeparator() . $password, $user->password)) {
                    $data['email_err'] = $data['password_err'] = 'The email or password is incorrect';
                } else {
                    if (!$user->isVerified) {
                        // Check if the user is verified
                        $message = 'The email is not verified. Please check your inbox for the verification email and click the link in it.';
                    } else {
                        // Save the credentials in the session
                        SessionManager::login($user);

                        // Log that the user has logged in
                        $this->logger->log("User '$user->id' has successfully signed in", Logger::INFO);

                        // // Redirect to the dashboard
                        redirect('dashboard', true);
                    }
                }

                $this->logger->log("Sign in for user '$email' failed!", Logger::INFO);
            }

            // Show the form again with the errors
        }

        // Load the view
        $this->render('user/signin', [
            'form_url' => URLROOT . '/login/signIn',
            'data' => $data,
            'message_title' => 'Verification error',
            'message' => $message,
            'allow_reset' => $allowReset,
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
        $message_title = '';
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
                    // Create the user
                    $user = $this->createUser($email, $password, $name, $picture);

                    // Save the credentials to the database
                    $this->userRepository->save($user);

                    try {
                        // Send the verification email
                        $sg = new SendgridService();
                        $sg->sendVerification($name, $email, $email . $this->getSaltSeparator() . $user->verificationCode);

                        // Inform the user that the email was sent
                        $message_title = 'Next steps - Verification';
                        $message = "The verification email has been sent. Please check your inbox.\nIf you don't see it, check your spam folder.";

                        // Log that the user has logged in
                        $this->logger->log("User '$user->email' has successfully created an account", Logger::INFO);
                    } catch (SendGridServiceException $e) {
                        $this->logger->log("Failed to send the verification email to '$email'! Error Info:" . $e->getMessage(), Logger::ERROR);

                        // Inform the user that the email was not sent
                        $message_title = 'Verification failed - SendGrid error';
                        $message = 'The verification email could not be sent. Please try again later. The error that occurred was' . $e->getMessage();
                    } catch (Exception $e) {
                        $message_title = 'Verification failed - Internal error';
                        $this->logger->log("Failed to send the verification email to '$email'! Error Info:" . $e->getMessage(), Logger::ERROR);

                        // Inform the user that the email was not sent
                        $message = 'The verification email could not be sent. Please try again later. If the problem persists, contact the administrator.';
                    }
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
            'message_title' => $message_title,
            'message' => $message
        ]);
    }

    /**
     * Shows the profile page with the profile form
     */
    public function profile()
    {
        // Only logged in users can access this page
        if (!SessionManager::isLoggedIn()) {
            redirect('login/signin', true);
            return;
        }

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

        // Do not show the real password in the form!
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
                if (isset($user) && $user->id != SessionManager::getCurrentUserId()) {
                    $data['email_err'] = 'The email is already registered';
                    $this->logger->log("Profile update for user '$email' failed!", Logger::DEBUG);
                } else {
                    $this->saveUser($user, $message_title, $message, $name, $email, $wantsUpdates, $picture, $password, $passwordPlaceholder);
                }
            }
            // Show the form again with the errors
        } else {
            $user = $this->userRepository->getUserById(SessionManager::getCurrentUserId());

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
        }

        // Set the CSRF token
        $data['csrf_token'] = SessionManager::getCsrfToken();

        // Load the view
        $this->render('user/profile', ['form_url' => URLROOT . '/UserController/profile', 'data' => $data, 'message_title' => $message_title, 'message' => $message]);
    }

    public function logout()
    {
        SessionManager::logout();
        redirect('', true);
    }

    /**
     * Saves the user to the database
     *
     * @param User $user The user to save
     * @param string $message_title The title of the message to show
     * @param string $message The message to show
     * @param string $name The name of the user
     * @param string $email The email of the user
     * @param boolean $wantsUpdates If the user wants to receive updates
     * @param string $picture The profile picture of the user
     * @param string $password The password of the user
     * @param string $passwordPlaceholder The placeholder for the password
     */
    private function saveUser(User|null &$user, string &$message_title, string &$message, string $name, string $email, bool $wantsUpdates, string $picture, string $password, string $passwordPlaceholder)
    {
        // Load the user if it is not loaded yet
        if (!isset($user)) {
            $user = $this->userRepository->getUserById(SessionManager::getCurrentUserId());
        }

        // Check if email was changed
        if (strcasecmp($user->email, $email) != 0) {
            try {
                // Generate a verification token and send it to the user
                $user->isVerified = false;
                $user->verificationToken = $this->generateSalt();

                // Send the verification email
                $sg = new SendgridService();
                $sg->sendVerification($name, $email, $email . $this->getSaltSeparator() . $user->verificationCode);

                // Inform the user that the email was sent
                // Send the verification email
                $message_title = 'Verification needed';
                $message = "Since you have changed your email address, we will send you a new verification link. Please confirm that this email is valid. Otherwise you will not be able to register!\n\nIf you don't see it, check your spam folder.";

                // Log that the email was changed and the verification email was sent
                $this->logger->log("Email of user '$user->email' was changed to '$email'. Verification email was sent!", Logger::DEBUG);
            } catch (SendGridServiceException $e) {
                $this->logger->log("Failed to send the verification email to '$email'! Error Info:" . $e->getMessage(), Logger::ERROR);

                // Inform the user that the email was not sent
                $message_title = 'Verification failed - SendGrid error';
                $message = 'The verification email could not be sent. Please try again later. The error that occurred was' . $e->getMessage();
            } catch (Exception $e) {
                $message_title = 'Verification failed - Internal error';
                $this->logger->log("Failed to send the verification email to '$email'! Error Info:" . $e->getMessage(), Logger::ERROR);

                // Inform the user that the email was not sent
                $message = 'The verification email could not be sent. Please try again later. If the problem persists, contact the administrator.';
            }
        }

        // Update the user
        $user->name = $name;
        $user->email = $email;
        $user->wantsUpdates = $wantsUpdates;
        // Only change the password if it was changed
        if ($passwordPlaceholder != $password) {
            // Generate a salt and hash the password
            $user->salt = $this->generateSalt();
            $user->password = password_hash($user->salt . $this->getSaltSeparator() . $password, PASSWORD_DEFAULT);
        }
        $user->profilePicture = $picture;

        // Save the updates to the database
        $this->userRepository->save($user);
        SessionManager::login($user);

        // Log that the user has logged in
        $this->logger->log("User '$user->id' has successfully updated his profile", Logger::INFO);
    }

    /**
     * Verifies or resets the password of the user with the given token
     *
     * @param string $token The token to verify the user
     */
    public function verify(string $token = '')
    {
        // Token schema: <user_id>$<verification_token>
        // /UserController/verify/21r8390@bztf.ch$0f9a757c27935879fcb7da9d7995eb08098917414af3cbb894da9b71549c7a58
        if (!isset($token) || empty($token)) {
            redirect('', true);
        }

        $this->logger->log("Trying to verify token: '$token'", Logger::DEBUG);

        // Check if the token is valid
        $isValid = false;

        // The data of the user
        $data = [
            'token' => $token,
            'password' => '',
            'password_err' => '',
        ];

        // Get the index of last $ from the token
        $lastToken = strrpos($token, $this->getSaltSeparator());
        if ($lastToken !== false) {
            // Get the user id and the verification token
            $userEmail = substr($token, 0, $lastToken);
            $verificationToken = substr($token, $lastToken + 1);

            // Get the user from the database
            $user = $this->userRepository->getUserByEmail($userEmail);
            if (!isset($user) || strcasecmp($user->verificationCode, $verificationToken) != 0) {
                $isValid = false;
                $this->logger->log("Verification token '$token' is invalid!", Logger::INFO);
            } elseif ($user->isVerified) {
                // The user is already verified so reset the password
                $isValid = true;
                if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
                    // Get the password
                    $data['password'] = $password = trim($_POST['password']);

                    // Validate the password
                    $data['password_err'] = $this->validatePassword($data['password']);

                    // Only if there are no errors, try to register
                    if (empty($data['password_err'])) {
                        // Generate a salt and hash the password
                        $user->salt = $this->generateSalt();
                        $user->password = password_hash($user->salt . $this->getSaltSeparator() . $password, PASSWORD_DEFAULT);

                        // Save the updates to the database
                        $this->userRepository->save($user);
                        SessionManager::login($user);

                        // Log that the user has logged in
                        $this->logger->log("User '$user->id' has successfully reset his password", Logger::INFO);

                        // Redirect to the profile page
                        redirect('UserController/profile', true);
                    } else {
                        $this->logger->log("User '$user->id' has tried to reset his password but the password was invalid", Logger::INFO);
                    }
                } else {
                    $this->logger->log("User '$user->id' is already verified! Resetting password now.", Logger::INFO);
                }
            } else {
                // Verify the user
                $user->isVerified = true;
                $user->verificationCode = '';
                $this->userRepository->save($user);

                // Save the credentials in the session
                SessionManager::login($user);

                // Log that the user has logged in
                $this->logger->log("User '$user->id' was successfully verified and is now signed in", Logger::INFO);

                // // Redirect to the dashboard
                redirect('dashboard', true);
            }
        }

        // Show the verification failed page
        $this->render('user/verify', ['is_valid' => $isValid, 'token' => $token]);
    }

    /**
     * Resets the password of the user with the given email
     *
     * @param string $email The email of the user as **url encoded** string
     */
    public function passwordReset(string $email = '')
    {
        // Check if the email is valid
        if (!isset($email) || empty($email)) {
            redirect('', true);
        }

        // Set form data
        $data = [
            'email' => '',
            'email_err' => '',
        ];
        $message_title = '';
        $message = '';

        $this->logger->log("Trying to reset password for email: '$email'", Logger::DEBUG);

        // Decoding the email
        $data['email'] = $email = urldecode($email);

        // Verify if the email is valid
        $data['email_err'] = $this->validateEmail($email);

        // Only if there are no errors, try to reset the password
        if (empty($data['email_err'])) {
            // Get the user from the database
            $user = $this->userRepository->getUserByEmail($email);

            // Check if the user exists
            if (isset($user)) {
                // Generate a new verification token and save it to the database
                $user->verificationCode = $this->generateSalt();
                $this->userRepository->save($user);

                try {
                    // Send the verification email
                    $sg = new SendgridService();
                    $sg->sendVerification($user->name, $user->email, $user->email . $this->getSaltSeparator() . $user->verificationCode);

                    // Inform the user that the email was sent
                    $message_title = 'Credential Reset';
                    $message = "The password reset or account verification email was sent successfully. Please follow the information in the email to continue.Older verification codes are now no longer valid.\nIf you can't find the email, check your spam folder or the address you entered!";
                } catch (SendGridServiceException $e) {
                    $this->logger->log("Failed to send the password reset email to '$email'! Error Info:" . $e->getMessage(), Logger::ERROR);

                    // Inform the user that the email was not sent
                    $message_title = 'Reset failed - SendGrid error';
                    $message = 'The reset email could not be sent. Please try again later. The error that occurred was' . $e->getMessage();
                } catch (Exception $e) {
                    $message_title = 'Reset failed - Internal error';
                    $this->logger->log("Failed to send the reset email to '$email'! Error Info:" . $e->getMessage(), Logger::ERROR);

                    // Inform the user that the email was not sent
                    $message = 'The reset email could not be sent. Please try again later. If the problem persists, contact the administrator.';
                }
            } else {
                $message_title = 'Password Reset Failed';
                $message = "The email address you entered is not registered. Please try again or create a new account.";
            }
        } else {
            $message_title = 'Password Reset Failed';
            $message = 'The password could not be reset because the associated email is invalid. Please try again or contact the administrator!';
        }

        // Load the view
        $this->render('user/signin', [
            'form_url' => URLROOT . '/login/signIn',
            'data' => $data,
            'message_title' => $message_title,
            'message' => $message,
        ]);
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
            $error = 'The name must be between 2 and 50 characters long';
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
        } elseif (!preg_match('/^data:image\/[\w+]+;base64,.*/i', $picture)) {
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
        $user->id = -1;
        $user->name = $name;
        $user->email = $email;
        $user->wantsUpdates = true;
        $user->role = $this->loadEnum('role', 'USER');
        $user->profilePicture = $profilePicture;

        // Generate a salt and hash the password
        $user->salt = $this->generateSalt();
        $user->password = password_hash($user->salt . $this->getSaltSeparator() . $password, PASSWORD_DEFAULT);

        // Generate a verification token and send it to the user
        $user->isVerified = false;
        $user->verificationCode = $this->generateSalt();

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
