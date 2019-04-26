<?php

namespace Islandora\Crayfish\Commons\Syn;

use Symfony\Component\Security\Core\User\UserInterface;

class JwtUser implements UserInterface
{
    protected $username;
    protected $roles;

    public function __construct($username, $roles)
    {
        $this->username = $username;
        $this->roles = $roles;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        return null;
    }
}
