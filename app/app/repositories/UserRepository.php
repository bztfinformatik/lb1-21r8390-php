<?php

require_once 'BaseRepository.php';

use Monolog\Logger;

class UserRepository extends BaseRepository
{
    /**
     * Saves the user to the database
     *
     * Saving or updating a user depends on whether the user has an id (`-1`) or not
     * 
     * @param User $user The user to save
     */
    public function save(User $user)
    {
        if (!isset($user)) {
            throw new InvalidArgumentException('User is null');
        }

        // Check if the user is new
        if (!isset($user->id) || $user->id == -1) {
            $this->logger->log("Saving user '$user->email' to the database", Logger::NOTICE);

            // Insert the user
            $this->db->query('INSERT INTO user (name, email, wantsUpdates, salt, password, role, profilePicture, isVerified, verificationCode, createdAt) 
                VALUES (:name, :email, :wantsUpdates, :salt, :password, :role, :profilePicture, :isVerified, :verificationCode, :createdAt)');

            $this->db->bind(':createdAt', date('Y-m-d H:i:s'));
        } else {
            $this->logger->log("Updating user '$user->email' in the database", Logger::INFO);

            // Update the user
            $this->db->query('UPDATE user SET 
                name = :name, email = :email, wantsUpdates = :wantsUpdates, salt = :salt, password = :password, role = :role,
                profilePicture = :profilePicture, isVerified = :isVerified, verificationCode = :verificationCode 
                WHERE id = :id');

            $this->db->bind(':id', $user->id);
        }

        // Bind the parameters
        $this->db->bind(':name', $user->name);
        $this->db->bind(':email', $user->email);
        $this->db->bind(':wantsUpdates', $user->wantsUpdates);
        $this->db->bind(':salt', $user->salt);
        $this->db->bind(':password', $user->password);
        $this->db->bind(':role', $user->role->value);
        $this->db->bind(':profilePicture', $user->profilePicture);
        $this->db->bind(':isVerified', $user->isVerified);
        $this->db->bind(':verificationCode', $user->verificationCode ?? '');

        // Execute the query
        if ($this->db->execute()) {
            $this->logger->log("User '$user->email' saved successfully", Logger::INFO);
        } else {
            $this->throwError("Failed to save user '$user->email' to the database");
        }
    }

    /**
     * Deletes the user from the database
     *
     * @param User $user The user to delete
     * @return bool Whether the user was deleted or not
     */
    public function delete(User $user): bool
    {
        if (!isset($user) || !isset($user->id) || $user->id == -1) {
            throw new InvalidArgumentException('User is not set or has no id');
        }

        $this->logger->log("Deleting user '$user->email' from the database", Logger::NOTICE);

        // Delete the user
        $this->db->query('DELETE FROM user WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $user->id);

        // Delete the user
        if (!$this->db->execute() || $this->db->rowCount() == 0) {
            $this->throwError("Failed to delete user '$user->email' from the database");
        }
        if ($this->db->rowCount() > 1) {
            $this->throwError("Deleted more than one user with id '$user->id' from the database");
        }

        $this->logger->log("User '$user->email' deleted successfully", Logger::DEBUG);
        return true;
    }

    /**
     * Returns a user by its id
     * 
     * @param int $id The id of the user
     * @return User The user if found, `null` otherwise
     */
    public function getUserById(int $id): User|null
    {
        $this->logger->log("Reading user with the id '$id' from the database", Logger::DEBUG);

        // Get the user
        $this->db->query('SELECT * FROM user WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $id);

        // Get the result
        $result = $this->db->single();

        if (!isset($result) || $result === false) {
            $this->throwError("Failed to read user with the id '$id' from the database");
        }

        // Return the user if found
        return $this->loadUser($result);
    }

    /**
     * Gets the user by the email
     *
     * @param string $email The email of the user
     * @return User The user if found, `null` otherwise
     */
    public function getUserByEmail(string $email): User|null
    {
        $this->logger->log("Searching for a user by the email '$email'", Logger::DEBUG);

        // Get the user
        $this->db->query('SELECT * FROM user WHERE UPPER(email) = :email LIMIT 1');
        $this->db->bind(':email', strtoupper($email));

        // Get the result
        $result = $this->db->single();

        // Check if the user was found
        return $this->loadUser($result);
    }

    /**
     * Gets the user by the verification code
     *
     * @param string $verificationCode The verification code of the user
     * @return User The user if found, `null` otherwise
     */
    public function getUserByVerificationCode(string $verificationCode): User|null
    {
        $this->logger->log("Searching for a user by the verification code '$verificationCode'", Logger::DEBUG);

        // Get the user
        $this->db->query('SELECT * FROM user WHERE verificationCode = :verificationCode LIMIT 1');
        $this->db->bind(':verificationCode', $verificationCode);

        // Get the result
        $result = $this->db->single();

        // Check if the user was found
        return $this->loadUser($result);
    }

    /**
     * Gets all the users from the database ordered by the creation date
     *
     * @return User[] All the users from the database
     */
    public function getAllUsers(): array
    {
        $this->logger->log("Reading all users from the database", Logger::DEBUG);

        // Get the users
        $this->db->query('SELECT * FROM user ORDER BY createdAt');

        // Get the results
        $results = $this->db->all();

        // Create the users
        $users = [];
        foreach ($results as $result) {
            // Add the user to the array
            $users[] = $this->loadUser($result);
        }

        // Return the users
        return $users;
    }

    #region Helper methods

    /**
     * Loads the user model and sets the properties
     * 
     * @param array $result The result from the database
     */
    private function loadUser($result): User|null
    {
        // Check if the user was found
        if ($result) {
            // Create the user
            $user = $this->loadModel('User');
            $user->id = $result['id'];
            $user->name = $result['name'];
            $user->email = $result['email'];
            $user->wantsUpdates = $result['wantsUpdates'];
            $user->salt = $result['salt'];
            $user->password = $result['password'];
            $user->role = $this->loadEnum('role', $result['role']);
            $user->profilePicture = $result['profilePicture'];
            $user->isVerified = $result['isVerified'];
            $user->verificationCode = $result['verificationCode'];
            $user->createdAt = $result['createdAt'];

            // Return the found user
            return $user;
        }

        // Return null because the user was not found
        return null;
    }

    #endregion

    #region Mock data

    /**
     * Returns a mocked user
     *
     * @return User The mocked user
     */
    private function getMockUser(): User
    {
        // Load the user model
        $user = $this->loadModel('User');

        // Set the properties
        $user->id = -1;
        $user->name = 'Test';
        $user->email = 'test@example.com';
        $user->wantsUpdates = true;
        $user->salt = 'salt';
        $user->password = password_hash($user->salt . '$' . 'Test123!Test123!', PASSWORD_DEFAULT);
        $user->role = $this->loadEnum('role', 'USER');
        $user->profilePicture = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///+/v7+jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII';
        $user->isVerified = true;
        $user->verificationCode = 'verificationCode';
        $user->createdAt = date('YYYY-mm-dd HH:MM:SS');

        // Return the mocked user
        return $user;
    }

    #endregion
}
