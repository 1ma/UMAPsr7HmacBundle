<?php

namespace UMA\Psr7HmacBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UMA\Psr7Hmac\Verifier;
use UMA\Psr7HmacBundle\Definition\HmacApiUserInterface;
use UMA\Psr7HmacBundle\Diactoros\Psr7Transformer;

class HmacProvider implements AuthenticationProviderInterface
{
    /**
     * @var Psr7Transformer
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
     * @param Psr7Transformer       $transformer
     * @param RequestStack          $requestStack
     * @param UserProviderInterface $userProvider
     * @param Verifier              $verifier
     */
    public function __construct(Psr7Transformer $transformer, RequestStack $requestStack, UserProviderInterface $userProvider, Verifier $verifier)
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

        if (!$apiUser instanceof HmacApiUserInterface) {
            throw new AuthenticationServiceException('the injected UserProvider must provide HmacApiUserInterface instances');
        }

        $psr7Request = $this->transformer->transform(
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
