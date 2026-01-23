# YII3-JWT-AUTH

 The package provides a [Yii 3](https://yii3.yiiframework.com) authentication method based on a JWT token.
 
 See also:

 -  [YII3-KEYCLOAK-AUTHZ](https://github.com/klsoft-web/yii3-keycloak-authz) - The package provides Keycloak authorization for the web service APIs of [Yii 3](https://yii3.yiiframework.com)
 -  [PHP-KEYCLOAK-CLIENT](https://github.com/klsoft-web/php-keycloak-client) - A PHP library that can be used to secure web applications with Keycloak

## Requirement

 - PHP 8.1 or higher.

## Installation

```bash
composer require klsoft/yii3-jwt-auth
```

## How to use

### 1. Implement Klsoft\Yii3JwtAuth\JwksRepositoryInterface

Example:

```php
namespace MyNamespace;

use Yiisoft\Cache\CacheInterface;
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
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\ArrayCache;
use Klsoft\Yii3JwtAuth\JwksRepositoryInterface;
use Yiisoft\Definitions\Reference;

IdentityRepositoryInterface::class => IdentityRepository::class,
CacheInterface::class => [
        'class' => Cache::class,
        '__construct()' => [
            'handler' => new ArrayCache()
        ],
],
JwksRepositoryInterface::class => [
        'class' => JwksRepository::class,
        '__construct()' => [
            'jwksUrl' => $params['jwksUrl'],
            'jwksCacheDuration' => $params['jwksCacheDuration'],
            'cache' => Reference::to(CacheInterface::class)
        ]
],
AuthenticationMethodInterface::class => HttpJwtAuth::class
```

### 4. Add Authentication to the application middlewares.

Example:

```php
use Yiisoft\Auth\Middleware\Authentication;

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
