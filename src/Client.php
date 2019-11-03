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

        $params = [
            'id' => (string) $crisisPlanId,
            'activate' => $activate === true ? 'true' : 'false',
        ];

        if (!empty($name)) {
            $params['crisis_name'] = (string) $name;
        }

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Returns the crisis plans of an account
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
            $params = [
                'active_only' => '1',
            ];

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

        $params = [
            'plan_id' => (string) $crisisPlanId,
            'todo_id' => (string) $todoId,
            'done' => $done === true ? 'true' : 'false',
        ];

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Add new mentions to your topic (in bulk)
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fadd
     *
     * @param string $accountId The account id
     * @param array $mentions A JSON encoded array of mention items you want
     * to add to your topic (Maximum of 500)
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

        $params = [
            'mentions' => json_encode($mentions),
        ];

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Returns a single social profile / contact
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bcontact_id%7D
     *
     * @param string $accountId The account id
     * @param string $contactId The contact id
     * @param array $topicIds List of topic ids to search for details
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single contact item
     */
    public function getContact($accountId, $contactId, array $topicIds = [])
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$contactId}"
        );

        if (!empty($topicIds)) {
            $params = [
                'topic_ids' => implode(',', $topicIds),
            ];

            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Updates a single social profile / contact
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
        array $options = []
    ) {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$contactId}"
        );

        $params = [
            'updates' => json_encode($updates),
        ];

        if (!empty($options)) {
            $params['options'] = json_encode($options);
        }

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query($params));
        $request = $request->withUri($uri);

        return $this->execute($request);
    }

    /**
     * Deletes a social profile / contact
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bcontact_id%7D
     *
     * @param string $accountId The account id
     * @param string $contactId The contact id
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return bool Boolean that indicates if contact details were deleted
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
     * Returns a single social profile / contact
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Finbox%2Fcontact%2F%7Bservice%7D%2F%7Bservice_id%7D
     *
     * @param string $accountId The account id
     * @param string $service The service
     * @param string $serviceId The service id
     * @param array $topicIds List of topic ids to search for details
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single contact item
     */
    public function getContactByServiceId(
        $accountId,
        $service,
        $serviceId,
        array $topicIds = []
    ) {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$service}/{$serviceId}"
        );

        if (!empty($topicIds)) {
            $params = [
                'topic_ids' => implode(',', $topicIds),
            ];

            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Updates a single social profile / contact
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
        array $options = []
    ) {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/inbox/contact/{$service}/{$serviceId}"
        );

        $params = [
            'updates' => json_encode($updates),
        ];

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

        if (!empty($params)) {
            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Returns a list of services, and associated configuration options, for
     * publishing new messages on your social profiles
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fpublisher%2Fadd
     *
     * @param string $accountId The account id
     * @param string $type The type of action you want to do. To publish a new
     * tweet or message use 'post', other options include 'reply' (eg. Twitter
     * replies), 'privatemessage', 'comment' (eg. Facebook comments), 'like',
     * 'favorite', 'retweet', 'reblog', 'submit' (Tumblr)
     * @param string $topicId The topic's id of the mention you want to reply to
     * @param string $mentionId The id of the mention you want to reply to
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single publisher_mention item
     */
    public function getPublisherOptions(
        $accountId,
        $type = 'post',
        $topicId = null,
        $mentionId = null
    ) {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/publisher/add"
        );

        $params = [];

        if (!empty($type)) {
            $params['type'] = (string) $type;
        }

        if (!empty($topicId)) {
            $params['topic_id'] = (string) $topicId;
        }

        if (!empty($mentionId)) {
            $params['mention_id'] = (string) $mentionId;
        }

        if (!empty($params)) {
            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Publishes a new message to one or more of social profiles
     * (create drafts and send messages for approval)
     *
     * Note: We cannot expose real publishing functionality in our API per
     * third-party terms of service agreements (eg. Twitter Api Rules), so only
     * creating drafts, or queueing messages for approval is exposed to
     * non-Engagor applications.
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fpublisher%2Fadd
     *
     * @param string $accountId The account id
     * @param string $type The type of action you want to do. To publish a new
     * tweet or message use 'post', other options include 'reply' (eg. Twitter
     * replies), 'privatemessage', 'comment' (eg. Facebook comments), 'like',
     * 'favorite', 'retweet', 'reblog', 'submit' (Tumblr)
     * @param array $services An array of items with properties 'type' and
     * 'service_id'. One or more services you want to publish to (any of the
     * services retrieved by GET /:account_id/publisher/add).
     * Eg. '[{"type":"facebook","service_id":"999999999999"}]'
     * @param array $to An array of items with property 'id'. Eg.
     * '[{"id":"info@abstergostore.com"},{"id":"no-reply@abstergostore.com"}]'
     * @param string $subject The text of the subject to post
     * @param string $message The text of the message to post
     * @param string $status The status the message will be in;
     * possible values are 'draft' or 'awaitingapproval'
     * @param DateTimeInterface $publishDate date to publish the item
     * (Leave empty for 'now')
     * @param string $topicId Topic id of the mention you're replying to, retweeting...
     * @param string $mentionId Id of the mention you're replying to, retweeting...
     * @param array $media An array of the ids returned from the media/add endpoint.
     * @param string $cannedResponseId Id of a canned response (The id is
     * required for canned responses of the type CSAT, NPS® or Buttons). If
     * the id is given, the number of usages will increase. If you use a canned
     * response, the message of the response should be given in the message
     * field and images should be added to the media field
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single publisher_mention item
     */
    public function publish(
        $accountId,
        $type,
        array $services,
        array $to = [],
        $subject = '',
        $message = '',
        $status = '',
        DateTimeInterface $publishDate = null,
        $topicId = null,
        $mentionId = null,
        array $media = [],
        $cannedResponseId = null
    ) {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/publisher/add"
        );

        $params = [];

        if (!empty($type)) {
            $params['type'] = (string) $type;
        }

        if (!empty($services)) {
            $params['services'] = json_encode($services);
        }

        if (!empty($to)) {
            $params['to'] = json_encode($to);
        }

        if (!empty($subject)) {
            $params['subject'] = (string) $subject;
        }

        if (!empty($message)) {
            $params['message'] = (string) $message;
        }

        if (!empty($status)) {
            $params['status'] = (string) $status;
        }

        if ($publishDate instanceof DateTimeInterface) {
            $params['date_publish'] = $publishDate->format('c');
        }

        if (!empty($topicId)) {
            $params['topic_id'] = (string) $topicId;
        }

        if (!empty($mentionId)) {
            $params['mention_id'] = (string) $mentionId;
        }

        if (!empty($media)) {
            $params['media'] = json_encode($media);
        }

        if (!empty($cannedResponseId)) {
            $params['canned_response_id'] = (string) $cannedResponseId;
        }

        if (!empty($params)) {
            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Returns a single publisher_mention object
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fpublisher%2Fmention%2F%7Bid%7D
     *
     * @param string $accountId The account id
     * @param string $mentionId The mention id
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single publisher_mention item
     */
    public function getPublisherMention($accountId, $mentionId)
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            "https://api.engagor.com/{$accountId}/publisher/mention/{$mentionId}"
        );

        return $this->execute($request);
    }

    /**
     * Updates a single publisher_mention object
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fpublisher%2Fmention%2F%7Bid%7D
     *
     * @param string $accountId The account id
     * @param string $mentionId The mention id
     * @param array $updates An array of changes you want to make. Structured as
     * a publisher_mention item, but with only the keys you want to update.
     * @param array $options An array of options for the update.
     * Supported keys: "tags_edit_mode", "sendMail"
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single publisher_mention item
     */
    public function updatePublisherMention(
        $accountId,
        $mentionId,
        array $updates,
        array $options = []
    ) {
        $request = $this->requestFactory->createRequest(
            'POST',
            "https://api.engagor.com/{$accountId}/publisher/mention/{$mentionId}"
        );

        $params = [];

        if (!empty($updates)) {
            $params['updates'] = json_encode($updates);
        }

        if (!empty($options)) {
            $params['options'] = json_encode($options);
        }

        if (!empty($params)) {
            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }

        return $this->execute($request);
    }

    /**
     * Deletes a single publisher_mention object
     *
     * https://developers.engagor.com/documentation/endpoints/?url=%2F%7Baccount_id%7D%2Fpublisher%2Fmention%2F%7Bid%7D
     *
     * @param string $accountId The account id
     * @param string $mentionId The mention id
     * @param array $options An array of options for the update.
     * Supported keys: "tags_edit_mode", "sendMail"
     *
     * @throws ApiCallFailed when something went wrong
     *
     * @return array A single publisher_mention item
     */
    public function deletePublisherMention(
        $accountId,
        $mentionId,
        array $options = []
    ) {
        $request = $this->requestFactory->createRequest(
            'DELETE',
            "https://api.engagor.com/{$accountId}/publisher/mention/{$mentionId}"
        );


        if (!empty($options)) {
            $params = [
                'options' => json_encode($options),
            ];

            $uri = $request->getUri();
            $uri = $uri->withQuery(http_build_query($params));
            $request = $request->withUri($uri);
        }


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

        $params = [];

        if ($pageToken !== '') {
            $params['page_token'] = (string) $pageToken;
        }

        if ($limit !== 10) {
            $params['limit'] = (int) $limit;
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

        $params = [];

        if ($pageToken !== '') {
            $params['page_token'] = (string) $pageToken;
        }

        if ($limit !== 10) {
            $params['limit'] = (int) $limit;
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
