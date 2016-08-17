<?php

namespace UMA\Psr7HmacBundle\Security\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use UMA\Psr7HmacBundle\Definition\HmacApiUserInterface;

class HmacToken implements TokenInterface
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var HmacApiUserInterface
     */
    private $apiUser = null;

    /**
     * @var bool
     */
    private $authenticated = false;

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
        return null !== $this->getUser() ?
            $this->getUser()->getRoles() : [];
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
     */
    public function getUser()
    {
        return $this->apiUser;
    }

    /**
     * @param HmacApiUserInterface $apiUser
     */
    public function setUser($apiUser)
    {
        if (!$apiUser instanceof HmacApiUserInterface) {
            throw new AuthenticationServiceException('$user must be an instanceof HmacApiUserInterface');
        }

        if ($apiUser->getApiKey() !== $this->apiKey) {
            throw new AuthenticationServiceException('the user api key must match the one from the token');
        }

        $this->apiUser = $apiUser;
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
        if (null !== $this->getUser()) {
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
                is_object($this->apiUser) ? clone $this->apiUser : $this->apiUser,
                $this->authenticated,
                $this->attributes,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->apiUser, $this->authenticated, $this->attributes) = unserialize($serialized);
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
}
