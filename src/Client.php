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

        $decodedResponse = json_decode($response->getBody(), true);

        return $decodedResponse;
    }

    /**
     * Returns details about the currently logged in user.
     * Use this function to identify who authorized your application.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2Fme
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single user item
     */
    public function me()
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            'https://api.engagor.com/me/'
        );

        return $this->execute($request);
    }

    /**
     * Returns a list of accounts (and associated projects,
     * topics and monitored profiles) the logged in user has access to.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2Fme%2Faccounts
     *
     * @param string $pageToken Paging parameter
     * @param int $limit Amount of accounts to return
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array paged_list of account items
     */
    public function getMyAccounts($pageToken = '', $limit = 10)
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            'https://api.engagor.com/me/accounts'
        );

        $params = array();

        if ($pageToken !== '') {
            $params = array(
                'page_token' => (string) $pageToken,
            );
        }
        if ($limit !== 10) {
            $params = array(
                'limit' => (int) $limit,
            );
        }

        if (!empty($params)) {
            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Returns a list of the connected profiles for the authenticated user
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2Fme%2Fconnectedprofiles
     *
     * @param string $pageToken Paging parameter
     * @param int $limit Amount of accounts to return
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return paged_list of connectedprofile items
     */
    public function getMyConnectedProfiles($pageToken = '', $limit = 10)
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            'https://api.engagor.com/me/connectedprofiles'
        );

        $params = array();

        if ($pageToken !== '') {
            $params = array(
                'page_token' => (string) $pageToken,
            );
        }
        if ($limit !== 10) {
            $params = array(
                'limit' => (int) $limit,
            );
        }

        if (!empty($params)) {
            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Returns a list of permissions your application has for the currently logged in user
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2Fme%2Fpermissions
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A list of permissions
     */
    public function getMyPermissions()
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            'https://api.engagor.com/me/permissions'
        );

        return $this->execute($request);
    }
}
