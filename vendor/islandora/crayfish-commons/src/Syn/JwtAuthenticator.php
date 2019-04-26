<?php

namespace Islandora\Crayfish\Commons\Syn;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use InvalidArgumentException;

class JwtAuthenticator extends AbstractGuardAuthenticator
{
    protected $logger;
    protected $staticTokens;
    protected $sites;
    protected $jwtFactory;

    public function __construct(
        SettingsParser $parser,
        JwtFactory $jwtFactory,
        LoggerInterface $logger = null
    ) {
        if ($logger == null) {
            $this->logger = new NullLogger();
        } else {
            $this->logger = $logger;
        }

        $this->jwtFactory = $jwtFactory;
        $this->staticTokens = $parser->getStaticTokens();
        $this->sites = $parser->getSites();
    }

    public function getCredentials(Request $request)
    {
        // Check headers
        $token = $request->headers->get('Authorization');
        if (!$token) {
            $this->logger->info('Token missing');
            return null;
        }
        if (0 !== strpos(strtolower($token), 'bearer ')) {
            $this->logger->info('Token malformed');
            return null;
        }
        $token = substr($token, 7);
        $this->logger->debug("Token: $token");

        // Check if this is a static token
        if (isset($this->staticTokens[$token])) {
            $staticToken = $this->staticTokens[$token];
            return [
                'token' => $staticToken['token'],
                'jwt' => null,
                'name' => $staticToken['user'],
                'roles' => $staticToken['roles']
            ];
        }

        // Decode token
        try {
            $jwt = $this->jwtFactory->load($token);
        } catch (InvalidArgumentException $exception) {
            $this->logger->info('Invalid token. ' . $exception->getMessage());
            return null;
        }

        // Check correct properties
        $payload = $jwt->getPayload();
        if (!isset($payload['webid'])) {
            $this->logger->info('Token missing webid');
            return null;
        }
        if (!isset($payload['iss'])) {
            $this->logger->info('Token missing iss');
            return null;
        }
        if (!isset($payload['sub'])) {
            $this->logger->info('Token missing sub');
            return null;
        }
        if (!isset($payload['roles'])) {
            $this->logger->info('Token missing roles');
            return null;
        }
        if (!isset($payload['iat'])) {
            $this->logger->info('Token missing iat');
            return null;
        }
        if (!isset($payload['exp'])) {
            $this->logger->info('Token missing exp');
            return null;
        }

        if ($jwt->isExpired()) {
            $this->logger->info('Token expired');
            return null;
        }

        return [
            'token' => $token,
            'jwt' => $jwt,
            'name' => $payload['sub'],
            'roles' => $payload['roles']
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $user = new JwtUser($credentials['name'], $credentials['roles']);
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // If this is a static token then no more verification needed
        if ($credentials['jwt'] === null) {
            $this->logger->info('Logged in with static token: ' . $credentials['name']);
            return true;
        }

        $jwt = $credentials['jwt'];
        $payload = $jwt->getPayload();
        $url = $payload['iss'];
        if (isset($this->sites[$url])) {
            $site = $this->sites[$url];
        } elseif (isset($this->sites['default'])) {
            $site = $this->sites['default'];
        } else {
            $this->logger->info('No site matches');
            return false;
        }

        return $jwt->isValid($site['key'], $site['algorithm']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => $exception->getMessageKey(),
        );
        return new JsonResponse($data, 403);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            'message' => 'Authentication Required',
        );
        return new JsonResponse($data, 401);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
