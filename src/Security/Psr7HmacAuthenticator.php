<?php

namespace UMA\Psr7HmacBundle\Security;

use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use UMA\Psr7Hmac\Verifier;
use UMA\Psr7HmacBundle\Definition\ApiClientInterface;

class Psr7HmacAuthenticator implements SimplePreAuthenticatorInterface
{
    /**
     * @var string
     */
    private $apiKeyHeader;

    /**
     * @var Verifier
     */
    private $verifier;

    /**
     * @param string   $apiKeyHeader
     * @param Verifier $verifier
     */
    public function __construct($apiKeyHeader, Verifier $verifier)
    {
        $this->apiKeyHeader = $apiKeyHeader;
        $this->verifier = $verifier;
    }

    /**
     * @param Request $request
     * @param string  $providerKey
     *
     * @return PreAuthenticatedToken
     *
     * @throws AuthenticationCredentialsNotFoundException
     */
    public function createToken(Request $request, $providerKey)
    {
        if (null === $request->headers->get($this->apiKeyHeader)) {
            throw new AuthenticationCredentialsNotFoundException('Request is missing the api key header');
        }

        return new PreAuthenticatedToken(
            'anon.',
            (new DiactorosFactory())->createRequest($request),
            $providerKey
        );
    }

    /**
     * @param TokenInterface        $token
     * @param UserProviderInterface $userProvider
     * @param string                $providerKey
     *
     * @return PreAuthenticatedToken
     *
     * @throws UsernameNotFoundException
     * @throws UnsupportedUserException
     * @throws BadCredentialsException
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        /** @var RequestInterface $psr7Request */
        $psr7Request = $token->getCredentials();

        $apiUser = $userProvider
            ->loadUserByUsername($apiKey = $psr7Request->getHeaderLine($this->apiKeyHeader));

        if (!$apiUser instanceof ApiClientInterface || !$apiUser instanceof UserInterface) {
            throw new UnsupportedUserException();
        }

        if (!$this->verifier->verify($psr7Request, $apiUser->getSharedSecret())) {
            throw new BadCredentialsException();
        }

        return new PreAuthenticatedToken(
            $apiUser, $apiKey, $providerKey, $apiUser->getRoles()
        );
    }

    /**
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}
