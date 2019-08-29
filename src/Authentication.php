<?php

namespace Engagor;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class Authentication
{
    private $client;
    private $requestFactory;
    private $clientId;
    private $clientSecret;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        $clientId,
        $clientSecret
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->clientId = (string) $clientId;
        $this->clientSecret = (string) $clientSecret;
    }

    public function step1(array $scopes, $state = '')
    {
        $url = "https://app.engagor.com/oauth/authorize/";
        $url .= "?client_id={$this->clientId}";
        $url .= "&response_type=code";

        if (!empty($scopes)) {
            $scopes = implode('%20', $scopes);
            $url .= "&scope={$scopes}";
        }

        if (!empty($state)) {
            $url .= "&state={$state}";
        }

        return $url;
    }

    public function step2($code)
    {
        $url = "https://app.engagor.com/oauth/access_token/";

        $params = array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => (string) $code,
        );

        $request = $this->requestFactory->createRequest(
            'GET',
            $url . '?' . http_build_query($params)
        );

        $response = $this->client->sendRequest($request);

        return json_decode($response->getBody(), true);
    }
}
