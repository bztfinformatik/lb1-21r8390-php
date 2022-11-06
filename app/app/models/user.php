<?php

/**
 * Undocumented class
 */
class User extends BaseModel
{
    // Attributes
    public string $name;
    public string $email;
    public bool $wantsUpdates;
    public string $password;
    public string $salt;
    private string $role;
    public string $profilePicture;
    public bool $isVerified;
    public $createdAt;

    /**
     * Sets the user's roles because they are saved in a json format
     * 
     * @param array $roles The roles to set
     */
    public function setRoles(array $roles)
    {
        $this->role = json_encode($roles);
    }
    /**
     * Gets the user's roles
     *
     * @return array The user's roles
     */
    public function getRoles(): array
    {
        return json_decode($this->role);
    }
}
