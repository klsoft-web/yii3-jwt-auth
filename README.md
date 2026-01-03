
# YII3-JWT-AUTH

 The package provides a Yii 3 authentication method based on a JWT token.

## Requirement

 - PHP 8.0 or higher.

## Installation

```bash
composer require klsoft/yii3-jwt-auth
```

## How to use

### 1. Implement Klsoft\Yii3JwtAuth\JwksRepositoryInterface

Example:

```php
namespace MyNamespace;

use Klsoft\Yii3JwtAuth\JwksRepositoryInterface;

class JwksRepository implements JwksRepositoryInterface
{
    private const JWKS = 'jwks';

    public function __construct(
        private string         $jwksUrl,
        private int            $jwksCacheDuration,
        private CacheInterface $cache)
    {
    }

    function getKeys(): ?array
    {
        $keys = $this->cache->getOrSet(JwksRepository::JWKS, function () {
            $options = [
                'http' => [
                    'method' => 'GET'
                ],
            ];
            $responseData = file_get_contents($this->jwksUrl, false, stream_context_create($options));
            if (!empty($responseData)) {
                return json_decode($responseData, true);
            }
            return [];
        }, $this->jwksCacheDuration);

        if (empty($keys)) {
            $this->cache->remove(JwksRepository::JWKS);
            return null;
        } else {
            return $keys;
        }
    }
}
```

### 2. Add the JWKS  URL to param.php

Example:

```php
return [
    'jwksUrl' => 'http://localhost:8080/realms/myrealm/protocol/openid-connect/certs',
    'jwksCacheDuration' => 60 * 3
];
```

### 3. Register dependencies

Example:

```php
IdentityRepositoryInterface::class => IdentityRepository::class,
CacheInterface::class => [
        'class' => Cache::class,
        '__construct()' => [
            'handler' => new ArrayCache()
        ],
],
JwksRepositoryInterface::class => static function (ContainerInterface $container) use ($params) {
        return new JwksRepository(
            $params['jwksUrl'],
            $params['jwksCacheDuration'],
            $container->get(CacheInterface::class));
},
AuthenticationMethodInterface::class => HttpJwtAuth::class
```

### 4. Add Authentication to the application middleware.

Example:

```php
Application::class => [
        '__construct()' => [
            'dispatcher' => DynamicReference::to([
                'class' => MiddlewareDispatcher::class,
                'withMiddlewares()' => [
                    [
                        Authentication::class,
                        FormatDataResponseAsJson::class,
                        static fn() => new ContentNegotiator([
                            'application/xml' => new XmlDataResponseFormatter(),
                            'application/json' => new JsonDataResponseFormatter(),
                        ]),
                        ErrorCatcher::class,
                        static fn(ExceptionResponderFactory $factory) => $factory->create(),
                        RequestBodyParser::class,
                        Router::class,
                        NotFoundMiddleware::class,
                    ],
                ],
            ]),
        ],
    ]
```
