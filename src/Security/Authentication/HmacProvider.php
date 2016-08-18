<?php

namespace UMA\Psr7HmacBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UMA\Psr7Hmac\Verifier;
use UMA\Psr7HmacBundle\Definition\HmacApiClientInterface;
use UMA\Psr7HmacBundle\Psr7\RequestTransformer;

class HmacProvider implements AuthenticationProviderInterface
{
    /**
     * @var RequestTransformer
     */
    private $transformer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var Verifier
     */
    private $verifier;

    /**
     * @param RequestTransformer    $transformer
     * @param RequestStack          $requestStack
     * @param UserProviderInterface $userProvider
     * @param Verifier              $verifier
     */
    public function __construct(RequestTransformer $transformer, RequestStack $requestStack, UserProviderInterface $userProvider, Verifier $verifier)
    {
        $this->transformer = $transformer;
        $this->requestStack = $requestStack;
        $this->userProvider = $userProvider;
        $this->verifier = $verifier;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        $apiUser = $this->userProvider
            ->loadUserByUsername($token->getUsername());

        if (!$apiUser instanceof UserInterface || !$apiUser instanceof HmacApiClientInterface) {
            throw new AuthenticationServiceException('the injected UserProvider must provide user objects that implement both the UserInterface and HmacApiClientInterface');
        }

        $psr7Request = $this->transformer->toPsr7(
            $this->requestStack->getMasterRequest()
        );

        if (!$this->verifier->verify($psr7Request, $apiUser->getSharedSecret())) {
            throw new AuthenticationException('the HMAC authentication failed');
        }

        $authToken = new HmacToken($apiUser->getApiKey());
        $authToken->setUser($apiUser);

        return $authToken;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof HmacToken;
    }
}
