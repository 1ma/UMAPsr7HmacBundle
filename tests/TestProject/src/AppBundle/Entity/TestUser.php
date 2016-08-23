<?php

namespace TestProject\AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use UMA\Psr7HmacBundle\Definition\HmacApiClientInterface;

class TestUser implements UserInterface, HmacApiClientInterface
{
    const TEST_APIKEY = 'Ez/XiDJ7sEHRGkejwLe1BcIy';
    const TEST_SECRET = 'HgeBZGTOYPo8JwNL5l+2Hyf7';

    /**
     * {@inheritdoc}
     */
    public function getApiKey()
    {
        return static::TEST_APIKEY;
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedSecret()
    {
        return static::TEST_SECRET;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return ['ROLE_API_USER'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->getApiKey();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        return;
    }
}
