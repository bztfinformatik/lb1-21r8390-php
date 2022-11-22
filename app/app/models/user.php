<?php

/**
 * Undocumented class
 */
class User
{
    // Attributes
    public int $id = -1;
    public string $name;
    public string $email;
    public bool $wantsUpdates;
    public string $salt;
    public string $password;
    public Role $role;
    public string $profilePicture;
    public bool $isVerified;
    public string $verificationCode;
    public $createdAt;
}
