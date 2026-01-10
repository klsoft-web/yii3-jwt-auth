<?php

namespace Klsoft\Yii3JwtAuth;

interface JwksRepositoryInterface
{
    /**
     * Get JWKS.
     *
     * @return ?array
     */
    function getKeys(): ?array;
}
