<?php

namespace Engagor;

use DateTimeImmutable;

final class Tokens
{
    private $accessToken;
    private $expireTime;
    private $scope;
    private $refreshToken;

    public function __construct(
        $accessToken,
        DateTimeImmutable $expireTime,
        array $scope,
        $refreshToken
    ) {
        $this->accessToken = (string) $accessToken;
        $this->expireTime = $expireTime;
        $this->scope = $scope;
        $this->refreshToken = (string) $refreshToken;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getExpireTime()
    {
        return $this->expireTime;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    public function toArray()
    {
        return array(
            'accessToken' => $this->accessToken,
            'expireTime' => $this->expireTime->format('c'),
            'scope' => implode(',', $this->scope),
            'refreshToken' => $this->refreshToken,
        );
    }
}
