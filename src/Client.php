<?php

namespace Engagor;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
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

    public function execute(RequestInterface $request)
    {
        $authorizedRequest = $request->withHeader(
            'Authorization',
            'Bearer ' . $this->tokens->getAccessToken()
        );

        try {
            $response = $this->client->sendRequest($authorizedRequest);
        } catch (ClientExceptionInterface $e) {
            throw ApiCallFailed::forRequest($request, $e);
        }

        $decodedResponse = json_decode($this->execute($request)->getBody(), true);

        return $decodedResponse;
    }

    public function me()
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            'https://api.engagor.com/me/'
        );

        return json_decode($this->execute($request)->getBody(), true);
    }
}
