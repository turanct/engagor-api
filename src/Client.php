<?php

namespace Engagor;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use DateTimeInterface;

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
     * Enable or disable a crisis plan
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fcrisis%2Fevent
     *
     * @param string $accountId The account id
     * @param string $crisisPlanId The crisis plan id
     * @param bool $activate Indicate if a crisis plan should be enabled or disabled
     * @param string $name Name of new crisis event
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A crisis_plan object
     */
    public function toggleCrisisEvent($accountId, $crisisPlanId, $activate = true, $name = '')
    {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/crisis/event"
        );

        $params = array(
            'id' => (string) $crisisPlanId,
            'activate' => $activate === true ? 'true' : 'false',
        );

        if (!empty($name)) {
            $params['crisis_name'] = (string) $name;
        }

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Returns the crisis plans of an account.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fcrisis%2Fplans
     *
     * @param string $accountId The account id
     * @param bool $activeOnly Show active plans only
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A list of crisis_plan objects
     */
    public function getCrisisPlansForAccount($accountId, $activeOnly = false)
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/crisis/plans"
        );

        if ($activeOnly === true) {
            $params = array(
                'active_only' => '1',
            );

            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Mark a todo-item as done or to do for a crisis in an account
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fcrisis%2Ftodo
     *
     * @param string $accountId The account id
     * @param string $crisisPlanId Id of a crisis plan
     * @param string $todoId Id of a todo-item
     * @param bool $done Indicate if a todo-item should be marked as done or to do
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A crisis_plan object
     */
    public function toggleCrisisPlanTodo($accountId, $crisisPlanId, $todoId, $done)
    {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/crisis/todo"
        );

        $params = array(
            'plan_id' => (string) $crisisPlanId,
            'todo_id' => (string) $todoId,
            'done' => $done === true ? 'true' : 'false',
        );

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Add new mentions to your topic (in bulk).
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fadd
     *
     * @param string $accountId The account id
     * @param array $mentions A JSON encoded array of mention items you want
     * to add to your topic. (Maximum of 500.)
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return bool Were all mentions added successfully?
     */
    public function addMentionsToInbox($accountId, array $mentions)
    {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/inbox/add"
        );

        $params = array(
            'mentions' => json_encode($mentions),
        );

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Returns a single social profile / contact.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bcontact_id%7D
     *
     * @param string $accountId The account id
     * @param string $contactId The contact id
     * @param array $topicIds List of topic ids to search for details.
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single contact item
     */
    public function getContact($accountId, $contactId, array $topicIds = array())
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$contactId}"
        );

        if (!empty($topicIds)) {
            $params = array(
                'topic_ids' => implode(',', $topicIds),
            );

            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Updates a single social profile / contact.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bcontact_id%7D
     *
     * @param string $accountId The account id
     * @param string $contactId The contact id
     * @param array $updates Changes you want to make. Structure of the array
     * should be like contact, with only those properties you want to update.
     * (Property `socialprofiles` can't be updated.)
     * @param array $options Options for the update. Supported keys:
     * 'customattributes_edit_mode' (possible values: 'update', 'overwrite' or
     * 'delete'; 'update' is default),
     * 'tags_edit_mode' (possible values: 'add', 'update', or 'delete'; 'update'
     * is default)
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single contact item
     */
    public function updateContact(
        $accountId,
        $contactId,
        array $updates,
        array $options = array()
    ) {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$contactId}"
        );

        $params = array(
            'updates' => json_encode($updates),
        );

        if (!empty($options)) {
            $params['options'] = json_encode($options);
        }

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Deletes a social profile / contact.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bcontact_id%7D
     *
     * @param string $accountId The account id
     * @param string $contactId The contact id
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return bool Boolean that indicates if contact details were deleted.
     */
    public function deleteContact($accountId, $contactId)
    {
        $request = $this->requestFactory->createRequest(
            'DELETE',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$contactId}"
        );

        return $this->execute($request);
    }

    /**
     * Returns a single social profile / contact.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bservice%7D%2F%7Bservice_id%7D
     *
     * @param string $accountId The account id
     * @param string $service The service
     * @param string $serviceId The service id
     * @param array $topicIds List of topic ids to search for details.
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single contact item
     */
    public function getContactByServiceId($accountId, $service, $serviceId, array $topicIds = array())
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$service}/{$serviceId}"
        );

        if (!empty($topicIds)) {
            $params = array(
                'topic_ids' => implode(',', $topicIds),
            );

            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Updates a single social profile / contact.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bservice%7D%2F%7Bservice_id%7D
     *
     * @param string $accountId The account id
     * @param string $service The service
     * @param string $serviceId The service id
     * @param array $updates Changes you want to make. Structure of the array
     * should be like contact, with only those properties you want to update.
     * (Property `socialprofiles` can't be updated.)
     * @param array $options Options for the update. Supported keys:
     * 'customattributes_edit_mode' (possible values: 'update', 'overwrite' or
     * 'delete'; 'update' is default),
     * 'tags_edit_mode' (possible values: 'add', 'update', or 'delete'; 'update'
     * is default)
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single contact item
     */
    public function updateContactByServiceId(
        $accountId,
        $service,
        $serviceId,
        array $updates,
        array $options = array()
    ) {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$service}/{$serviceId}"
        );

        $params = array(
            'updates' => json_encode($updates),
        );

        if (!empty($options)) {
            $params['options'] = json_encode($options);
        }

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Returns a list of contacts ordered by contact.id
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontacts
     *
     * @param string $accountId The account id
     * @param array $requiredFields A JSON encoded array of fields that should
     * be filled in for the returned contact objects.
     * Possible values: "email", "name", "company", "phone"
     * @param string $filter Optional filter rule for returned contacts.
     * (Currently only filters of type `contacttag:tagname` are supported.
     * `AND`, `OR` and `NOT`-clauses are also not supported yet.)
     * @param DateTimeInterface $updatedSince Optional date. When set, only contacts
     * updated after this time will be returned.
     * @param string $pageToken Paging parameter.
     * @param int $limit Amount of contacts to return
     * @param string $sort Ordering of the contacts.
     * Possible options: `dateadd:asc`, `dateadd:desc`, `lastupdate:asc`, `lastupdate:desc`
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array paged_list of contact items, ordered by contact.id
     */
    public function getContacts(
        $accountId,
        array $requiredFields = [],
        $filter = '',
        DateTimeInterface $updatedSince = null,
        $pageToken = '',
        $limit = 20,
        $sort = 'dateadd:asc'
    ) {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/inbox/contacts"
        );

        $params = [];

        if (!empty($requiredFields)) {
            $params['required_fields'] = json_encode($requiredFields);
        }

        if (!empty($filter)) {
            $params['filter'] = (string) $filter;
        }

        if ($updatedSince instanceof DateTimeInterface) {
            $params['updated_since'] = $updatedSince->format('c');
        }

        if (!empty($pageToken)) {
            $params['page_token'] = (string) $pageToken;
        }

        if (!empty($limit) && is_int($limit) && $limit <= 0) {
            $params['limit'] = $limit;
        }

        if (!empty($sort)) {
            $params['sort'] = (string) $sort;
        }

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
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
