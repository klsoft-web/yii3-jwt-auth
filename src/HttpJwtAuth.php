<?php

namespace Klsoft\Yii3JwtAuth;

use InvalidArgumentException;
use DomainException;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Di\NotFoundException;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Http\Header;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

/**
 * HttpJwtAuth is a Yii 3 authentication method based on a JWT token.
 */
class HttpJwtAuth implements AuthenticationMethodInterface
{
    private string $headerName = Header::AUTHORIZATION;
    private string $headerTokenPattern = '/^Bearer\s+(.*?)$/';
    private string $realm = 'api';
    private string $identifier = 'sub';

    /**
     * @param IdentityRepositoryInterface $identityRepository
     * @param JwksRepositoryInterface $jwksRepository
     */
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private JwksRepositoryInterface     $jwksRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        $token = $this->getAuthenticationToken($request);
        if ($token !== null) {
            $jwks = $this->jwksRepository->getKeys();
            if ($jwks != null) {
                try {
                    $payload = JWT::decode(
                        $token,
                        JWK::parseKeySet($jwks));

                    return $this->identityRepository->findIdentity((string) ((array) $payload)[$this->identifier]);
                } catch (InvalidArgumentException|DomainException|UnexpectedValueException) {
                    return null;
                }
            }
        }

        return null;
    }

    private function getAuthenticationToken(ServerRequestInterface $request): ?string
    {
        $authHeaders = $request->getHeader($this->headerName);
        $authHeader = reset($authHeaders);
        if (!empty($authHeader) && preg_match($this->headerTokenPattern, $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function challenge(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader(Header::WWW_AUTHENTICATE, "{$this->headerName} realm=\"{$this->realm}\"");
    }

    /**
     * @param string $realm The HTTP authentication realm.
     *
     * @return self
     */
    public function withRealm(string $realm): self
    {
        $new = clone $this;
        $new->realm = $realm;
        return $new;
    }

    /**
     * @param string $headerName Authorization header name.
     *
     * @return self
     */
    public function withHeaderName(string $headerName): self
    {
        $new = clone $this;
        $new->headerName = $headerName;
        return $new;
    }

    /**
     * @param string $headerTokenPattern Regular expression to use for getting a token from authorization header.
     * Token value should match first capturing group.
     *
     * @return self
     */
    public function withHeaderTokenPattern(string $headerTokenPattern): self
    {
        $new = clone $this;
        $new->headerTokenPattern = $headerTokenPattern;
        return $new;
    }

    /**
     * @param string $identifier Identifier to check claims for.
     *
     * @return self
     */
    public function withIdentifier(string $identifier): self
    {
        $new = clone $this;
        $new->identifier = $identifier;
        return $new;
    }
}
