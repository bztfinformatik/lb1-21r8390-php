<?php

require_once 'BaseRepository.php';

use Monolog\Logger;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Saves the user to the database
     *
     * @param User $user The user to save
     */
    public function save(User $user): int
    {
        $this->logger->log("Saving user '$user->email' to the database", Logger::INFO);

        // TODO Save the user to the database
        // Currently only mocked

        return random_int(0, 1000000);
    }

    public function getCurrentUser(): User|null
    {
        $user = $this->loadModel('User');

        $user->id = 1;
        $user->name = 'Test';
        $user->email = 'test@example.com';
        $user->wantsUpdates = true;
        $user->salt = 'salt';
        $user->password = password_hash($user->salt . '$' . 'Test123!Test123!', PASSWORD_DEFAULT);
        $user->setRoles(array($this->loadEnum('role', 0), $this->loadEnum('role', 1), $this->loadEnum('role', 2)));
        $user->profilePicture = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///+/v7+jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII';
        $user->isVerified = true;
        $user->verificationCode = 'verificationCode';
        $user->createdAt = '2020-01-01 00:00:00';

        return $user;
    }

    /**
     * Gets the user by the email
     *
     * @param string $email The email of the user
     * @return User The user
     */
    public function getUserByEmail(string $email): User|null
    {
        $this->logger->log("Getting the user by email '$email'", Logger::DEBUG);

        // TODO:
        // - Implement the query
        // - Set the query parameters (SQL-injection safe)
        // - Return the user
        // - Log the result (if the user was found or not)

        // Mock user
        if ($email === 'test@example.com') {
            $user = $this->loadModel('User');

            $user->id = -1;
            $user->name = 'Test';
            $user->email = 'test@example.com';
            $user->wantsUpdates = true;
            $user->salt = 'salt';
            $user->password = password_hash($user->salt . '$' . 'Test123!Test123!', PASSWORD_DEFAULT);
            $user->setRoles(array('admin', 'teacher', 'user'));
            $user->profilePicture = 'profilePicture';
            $user->isVerified = true;
            $user->verificationCode = 'verificationCode';
            $user->createdAt = '2020-01-01 00:00:00';

            return $user;
        }
        return null;
    }
}
