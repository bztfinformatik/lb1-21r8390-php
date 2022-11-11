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

        // $this->db->query('INSERT INTO users (name, email, wantsUpdates, salt, password, role, profilePicture, isVerified, verificationCode, createdAt) VALUES (:name, :email, :wantsUpdates, :salt, :password, :role, :profilePicture, :isVerified, :verificationCode, :createdAt)');
        // $this->db->bind(':name', $user->name);
        // $this->db->bind(':email', $user->email);
        // $this->db->bind(':wantsUpdates', $user->wantsUpdates);
        // $this->db->bind(':salt', $user->salt);
        // $this->db->bind(':password', $user->password);
        // $this->db->bind(':role', $user->role);
        // $this->db->bind(':profilePicture', $user->profilePicture);
        // $this->db->bind(':isVerified', $user->isVerified);
        // $this->db->bind(':verificationCode', $user->verificationCode);
        // $this->db->bind(':createdAt', $user->createdAt);
        // $this->db->execute();
        // return $this->db->lastInsertId();


        return random_int(0, 1000000);
    }

    /**
     * Gets the user by the email
     *
     * @param string $email The email of the user
     * @return User The user
     */
    public function getUserByEmail(string $email)
    {
        $this->logger->log("Getting the user by email '$email'", Logger::DEBUG);

        // TODO:
        // - Implement the query
        // - Set the query parameters (SQL-injection safe)
        // - Return the user
        // - Log the result (if the user was found or not)

        // Mock user
        if ($email == 'test@gmail.com') {
            return $this->loadModel('User');
        }
        return null;
    }
}
