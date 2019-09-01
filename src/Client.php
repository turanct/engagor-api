<?php

namespace Engagor;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

final class Client
{
    private $client;
    private $requestFactory;
    private $tokens;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        Tokens $tokens
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->tokens = $tokens;
    }

    private function request(RequestInterface $request)
    {
        $uri = $request->getUri();

        $authorizedRequest = $request->withHeader(
            'Authorization',
            'Bearer ' . $this->tokens->getAccessToken()
        );

        return $this->client->sendRequest($authorizedRequest);
    }

    public function me()
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            'https://api.engagor.com/me/'
        );

        return json_decode($this->request($request)->getBody(), true);
    }
}
