<?php

class SessionManager
{
    /**
     * Checks if the user is logged in
     *
     * @return boolean True if the user is logged in, false otherwise
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Saves the user credentials in the session
     *
     * @param User $user The user to save in the session
     */
    public static function login(User $user)
    {
        session_start();

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_wants_updates'] = $user->wantsUpdates;
        $_SESSION['user_roles'] = $user->getRoles();
        $_SESSION['user_profile_picture'] = $user->profilePicture;
    }

    /**
     * Destroys the session
     */
    public static function logout()
    {
        session_destroy();
    }

    /**
     * Sets and returns the CSRF token for the current session
     * 
     * @return string The CSRF token
     */
    public static function getCSRFToken(): string
    {
        $csrf = bin2hex(random_bytes(32));

        $_SESSION['csrf_token'] = $csrf;
        return $csrf;
    }

    /**
     * Checks if the CSRF token is valid
     * 
     * Sets the `SERVER_PROTOCOL` to `HTTP/1.1 403 Forbidden` if the token is invalid
     * 
     * @param string $token The token to check
     * @return boolean True if the token is valid, false otherwise
     */
    public static function isCSRFTokenValid(string $token): bool
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
        return $token === $_SESSION['csrf_token'];
    }
}
