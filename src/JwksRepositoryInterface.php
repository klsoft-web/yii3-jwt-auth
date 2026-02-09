<?php

namespace Klsoft\Yii3JwtAuth;

interface JwksRepositoryInterface
{
    /**
     * Get JWKS.
     *
     * @return ?array
     */
    public function getKeys(): ?array;
}
