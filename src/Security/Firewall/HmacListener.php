<?php

namespace UMA\Psr7HmacBundle\Security\Firewall;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use UMA\Psr7HmacBundle\Security\Authentication\HmacToken;

class HmacListener implements ListenerInterface
{
    /**
     * @var AuthenticationManagerInterface
     */
    private $authManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param AuthenticationManagerInterface $authManager
     * @param TokenStorageInterface          $tokenStorage
     */
    public function __construct(AuthenticationManagerInterface $authManager, TokenStorageInterface $tokenStorage)
    {
        $this->authManager = $authManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        try {
            if (null === $apiKey = $request->headers->get('Api-Key')) {
                throw new AuthenticationCredentialsNotFoundException('Missing Api key header');
            }

            $this->tokenStorage->setToken(
                $this->authManager->authenticate(new HmacToken($apiKey))
            );
        } catch (AuthenticationException $e) {
            // TODO we'll see...
        }
    }
}
