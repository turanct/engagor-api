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

    /**
     * The first step in the OAuth2 authentication process.
     *
     * @param array $scopes The scopes we want to request.
     * pick from these: identify accounts_read accounts_write socialprofiles email
     *
     * @param string $state A security value so that you can check the redirect came from you
     *
     * @return string The auth URL to redirect your user to
     */
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

    /**
     * The first step in the OAuth2 authentication process.
     *
     * @param string $code The authentication code from the redirect URL
     *
     * @return array An associative array of OAuth2 authentication data
     */
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
