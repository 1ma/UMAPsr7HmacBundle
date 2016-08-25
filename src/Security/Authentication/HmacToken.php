<?php

namespace UMA\Psr7HmacBundle\Security\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use UMA\Psr7HmacBundle\Definition\HmacApiClientInterface;

class HmacToken implements TokenInterface
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var UserInterface|HmacApiClientInterface|null
     */
    private $user = null;

    /**
     * @var bool
     */
    private $authenticated = false;

    /**
     * @var Role[]
     */
    private $roles = [];

    /**
     * @var mixed[]
     */
    private $attributes = [];

    /**
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->getCredentials();
    }

    /**
     * {@inheritdoc}
     *
     * @return UserInterface|HmacApiClientInterface|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInterface|HmacApiClientInterface $user
     */
    public function setUser($user)
    {
        if (!$user instanceof UserInterface || !$user instanceof HmacApiClientInterface) {
            throw new AuthenticationServiceException('$user must implement both the UserInterface and the HmacApiClientInterface');
        }

        if ($user->getApiKey() !== $this->apiKey) {
            throw new AuthenticationServiceException('the user api key must match the one from the token');
        }

        $this->user = $user;
        $this->roles = $this->objectifyRoles($this->user);
        $this->setAuthenticated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthenticated($authenticated)
    {
        $this->authenticated = (bool) $authenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        if (null !== $this->getUser() && $this->getUser() instanceof UserInterface) {
            $this->getUser()->eraseCredentials();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('This token has no "%s" attribute.', $name));
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->apiKey,
                is_object($this->user) ? clone $this->user : $this->user,
                $this->roles,
                $this->attributes,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->apiKey, $this->user, $this->roles, $this->attributes) = unserialize($serialized);

        $this->authenticated = $this->user instanceof UserInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf(
            '%s(user="%s", authenticated=%s, roles="%s")',
            substr(get_class($this), strrpos(get_class($this), '\\') + 1),
            $this->getUsername(),
            json_encode($this->isAuthenticated()),
            implode(', ', $this->getRoles())
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return Role[]
     */
    private function objectifyRoles(UserInterface $user)
    {
        $roles = [];

        foreach ($user->getRoles() as $role) {
            if (is_string($role)) {
                $role = new Role($role);
            } elseif (!$role instanceof RoleInterface) {
                throw new \InvalidArgumentException(sprintf('User roles must be an array of strings, or RoleInterface instances, but got %s.', gettype($role)));
            }

            $roles[] = $role;
        }

        return $roles;
    }
}
