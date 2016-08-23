<?php

namespace UMA\Psr7HmacBundle\Security\Firewall;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AuthenticationManagerInterface $authManager
     * @param TokenStorageInterface          $tokenStorage
     * @param LoggerInterface|null           $logger
     */
    public function __construct(AuthenticationManagerInterface $authManager, TokenStorageInterface $tokenStorage, LoggerInterface $logger = null)
    {
        $this->authManager = $authManager;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        try {
            if (null === $apiKey = $request->headers->get('Api-Key')) {
                throw new AuthenticationCredentialsNotFoundException('Request is missing the API key HTTP header');
            }

            $this->tokenStorage->setToken(
                $this->authManager->authenticate(new HmacToken($apiKey))
            );
        } catch (AuthenticationException $e) {
            if (null !== $this->logger) {
                $this->logger->info('Hmac authentication failed.', ['exception' => $e]);
            }

            $event->setResponse(
                new JsonResponse('Unauthorized request', Response::HTTP_UNAUTHORIZED)
            );
        }
    }
}
