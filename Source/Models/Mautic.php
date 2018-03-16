<?php

namespace Model;

use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

/**
 * Description of Mautic
 *
 * @author danny
 * Check Mautic REST API Documentation: https://developer.mautic.org/#rest-api
 */
class Mautic {

    private $settings;
    private $initAuth;
    private $auth;
    private $api;
    private $contexts;
    private $activeContext;

    function __construct(array $config = []) {
        $this->settings = (array) [
                    'baseUrl' => (string) ($config['baseUrl'] ?? MAUTIC_API['baseUrl'] ?? ''), // Base URL of the Mautic instance
                    'userName' => (string) ($config['userName'] ?? MAUTIC_API['userName'] ?? ''), // Create a new user       
                    'password' => (string) ($config['password'] ?? MAUTIC_API['password'] ?? ''), // Make it a secure password
                    'version' => (string) ($config['version'] ?? MAUTIC_API['version'] ?? 'basic'), // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
                    'clientKey' => (string) ($config['clientKey'] ?? MAUTIC_API['clientKey'] ?? ''), // Client/Consumer key from Mautic
                    'clientSecret' => (string) ($config['clientSecret'] ?? MAUTIC_API['clientSecret'] ?? ''), // Client/Consumer secret key from Mautic
                    'callback' => (string) ($config['callback'] ?? MAUTIC_API['callback'] ?? '')        // Redirect URI/Callback URI for this script
        ];
        $this->auth();
        $this->api = new MauticApi;
    }

    /**
     * Set Context to make API calls.
     * 
     * <b>Allowed Contexts</b>: To see all available contexts, check: https://developer.mautic.org/#rest-api
     * 
     * @param string $context
     */
    public function setContext(string $context) {
        $this->activeContext = $context;
        if (empty($this->contexts[$context])) :
            $this->contexts[$context] = $this->api->newApi($context, $this->auth, $this->settings['baseUrl']);
        endif;
    }

    /**
     * Return current context.
     * 
     * @return string
     */
    public function getContext() {
        return $this->activeContext;
    }

    /**
     * Return Mautic Version.
     * 
     * @return type
     */
    public function getVersion() {
        return $this->api->getMauticVersion();
    }

    /**
     * Return a single element with a given ID from seted context.
     * @param int $id - Element ID in your Mautic.
     * @return array
     */
    public function get(int $id) {
        $response = $this->contexts[$this->activeContext]->get($id);
        return $this->returnResponse($response, 'get');
    }

    /**
     * Return list of elements from seted context.
     * 
     * @param array $customOptions              Array de Opções. Veja as opções abaixo
     *     @option integer  "start"             Starting row for the entities returned. Defaults to 0.
     *     @option integer  "limit"             Limit number of entities to return. Defaults to the system configuration for pagination (30).
     *     @option string   "orderBy"           Column to sort by. Can use any column listed in the response. Defaults to "id".
     *     @option string   "orderByDir"        Sort direction: asc or desc. Defaults to "asc".
     *     @option boolean  "publishedOnly"     Sort direction: asc or desc. Defaults to "asc".
     *     @option boolean  "minimal"           Only return currently published entities. Defaults to false.
     * 
     * @param string $searchFilter - String or search command to filter entities by. Works like search bar in Mautic. Check the rules for each segments and test in your system before.

     * @param bool $minimal - Return only array of entities without additional lists in it. Defaults to false.

     * @return array
     */
    public function getList(string $searchFilter = null, array $customOptions = []) {
        $defaultOptions = [
            'start' => 0,
            'limit' => 30,
            'orderBy' => 'id',
            'orderByDir' => 'asc',
            'publishedOnly' => false,
            'minimal' => false
        ];
        $options = array_merge($defaultOptions, $customOptions);

        $response = $this->contexts[$this->activeContext]->getList($searchFilter, $options['start'], $options['limit'], $options['orderBy'], $options['orderByDir'], $options['publishedOnly'], $options['minimal']);
        if (!isset($response['errors'])) :
            return $response[$this->contexts[$this->activeContext]->listName()];
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->listName(), 'getList');
        endif;
    }

    public function getFirst(string $searchFilter = null, array $customOptions = []) {
        $options = array_merge(['limit' => 1], $customOptions);
        $contacts = $this->getList($searchFilter, $options);
        if ($contacts):
            foreach ($contacts as $c):
                $response = $c;
                break;
            endforeach;
        else:
            $response = null;
        endif;
        return $response;
    }

    /**
     * Return list of contacts in a campaign with a given id.
     * 
     * <b>IMPORTANT</b>: setContext to "campaigns" before
     * 
     * <b>Allowed Contexts</b>: campaigns
     * 
     * @param array $customOptions       Array de Opções. Veja as opções abaixo
     *     @option integer  "start"
     *     @option integer  "limit"
     *     @option array    "order"   
     *     @option array    "where"  
     * 
     * @return array
     */
    public function getContacts(int $campaignId = null, array $customOptions = []) {
        $defaultOptions = [
            'start' => null,
            'limit' => null,
            'order' => [],
            'where' => []
        ];
        $options = array_merge($defaultOptions, $customOptions);

        $response = $this->contexts[$this->activeContext]->getContacts($campaignId, $options['start'], $options['limit'], $options['order'], $options['where']);
        if (!isset($response['errors'])) :
            return $response['contacts'];
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->itemName(), 'getContacts');
        endif;
    }

    /**
     * Create element in seted context.
     * 
     * Check contexts for the right syntax. https://developer.mautic.org/#rest-api
     * 
     * @param array $data - Collection of data needed to create an element.
     * @return array
     */
    public function create(array $data) {
        $response = $this->contexts[$this->activeContext]->create($data);
        return $this->returnResponse($response, 'create');
    }

    /**
     * Edit element with a given ID from seted context.
     * 
     * @param int $id - Element ID
     * @param array $data
     * @param bool $createIfNotFound
     * @return array
     */
    public function edit(int $id, array $data, bool $createIfNotFound = false) {
        $response = $this->contexts[$this->activeContext]->edit($id, $data, $createIfNotFound);
        return $this->returnResponse($response, 'edit');
    }

    public function sync(string $searchFilter = null, array $data, array $customOptions = []) {
        $options = array_merge(['limit' => 1], $customOptions);
        $contacts = $this->getList($searchFilter, $options);
        if ($contacts):
            foreach ($contacts as $c):
                $response = $this->contexts[$this->activeContext]->edit($c['id'], $data, true);
                break;
            endforeach;
        else:
            $response = $this->contexts[$this->activeContext]->create($data);
        endif;
        return $this->returnResponse($response, 'sync');
    }

    /**
     * Delete a single element with a given ID from seted context.
     * 
     * @param int $id - Element ID in your Mautic.
     * @return bool Returns true if deletion succeeds.
     */
    public function delete(int $id) {
        $response = $this->contexts[$this->activeContext]->delete($id);
        if (isset($response['success'])) :
            return true;
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->itemName(), 'delete');
        endif;
    }

    /**
     * Add a contact to a single element from seted context, both with a given ID.
     * 
     * <b>IMPORTANT</b>: setContext to "campaigns", "segments" or "companies" before
     * 
     * <b>Allowed Contexts</b>: campaigns, segments, companies
     * 
     * @param int $contextId - Element ID in your Mautic.
     * @param int $contactId - Contact ID in your Mautic.
     * @return bool Returns True if add succeeds.
     */
    public function addContact(int $contextId, int $contactId) {
        $response = $this->contexts[$this->activeContext]->addContact($contextId, $contactId);
        if (isset($response['success'])) :
            return true;
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->itemName(), 'addContact');
        endif;
    }

    /**
     * Remove a contact to a single element from seted context, both with a given ID.
     * 
     * <b>IMPORTANT</b>: setContext to "campaigns", "segments" or "companies" before
     * 
     * <b>Allowed Contexts</b>: campaigns, segments, companies
     * 
     * @param int $contextId - Element ID in your Mautic.
     * @param int $contactId - Contact ID in your Mautic.
     * @return bool Returns True if remove succeeds.
     */
    public function removeContact(int $contextId, int $contactId) {
        $response = $this->contexts[$this->activeContext]->removeContact($contextId, $contactId);
        if (isset($response['success'])) :
            return true;
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->itemName(), 'removeContact');
        endif;
    }

    /**
     * Send email to a single contact, both with a given ID.
     * 
     * <b>IMPORTANT</b>: setContext to "emails" before
     * 
     * <b>Allowed Contexts</b>: emails
     * 
     * @param int $emailId - Email ID in your Mautic.
     * @param int $contactId - Contact ID in your Mautic.
     * @return bool Returns True if send succeeds.
     */
    public function sendToContact($emailId, $contactId) {
        $response = $this->contexts[$this->activeContext]->sendToContact($emailId, $contactId);
        if (isset($response['success'])) :
            return true;
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->itemName(), 'sendToContact');
        endif;
    }

    /**
     * Send a segment email to a linked segment(s).
     * 
     * <b>IMPORTANT</b>: setContext to "emails" before
     * 
     * <b>Allowed Contexts</b>: emails
     * 
     * @param int $emailId - Segment email ID in your Mautic.
     * @return bool Returns True if send succeeds.
     */
    public function sendToSegment($emailId) {
        $response = $this->contexts[$this->activeContext]->send($emailId);
        if (isset($response['success'])) :
            return true;
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->itemName(), 'sendToSegment');
        endif;
    }

    /**
     * Return all webhook triggers.
     * 
     * <b>IMPORTANT</b>: setContext to "webhooks" before
     * 
     * <b>Allowed Contexts</b>: webhooks
     * 
     * @return array
     */
    public function getTriggers() {
        $response = $this->contexts[$this->activeContext]->getTriggers();
        return $this->returnResponse($response, 'getTriggers');
    }

    private function returnResponse($response, $functionOrigin) {
        if (!isset($response['errors'])) :
            return (array) $response[$this->contexts[$this->activeContext]->itemName()];
        else :
            return $this->handleError($response, $this->contexts[$this->activeContext]->itemName(), $functionOrigin);
        endif;
    }

    private function handleError($response, $context, $functionOrigin) {
        $subject = "Error: Mautic API - {$this->settings['baseUrl']}";
        $content = ""
                . "<p>Context: {$context}</p>"
                . "<p>Function: {$functionOrigin}</p>"
                . "<p>ERRORS</p>:<br>";
        foreach ($response['errors'] as $k => $v) :
            $content .= "Index {$k}:"
                    . "code: {$v['code']}"
                    . "message: {$v['message']}"
                    . "<hr><br>";
        endforeach;
//        var_dump($subject, $content);
        return null;
    }

    private function auth() {
        $this->initAuth = new ApiAuth;
        switch ($this->settings['version']) {
            case 'basic':
                $this->basicAuth();
                break;

            case 'OAuth1a':
            case 'OAuth2':
                $this->OAuth();
                break;
        }
    }

    private function basicAuth() {
        $this->auth = $this->initAuth->newAuth($this->settings, 'BasicAuth');
    }

    private function OAuth() {
        $tokenSettings = $this->getToken() ?? [];

        $token = array_merge($this->settings, $tokenSettings);

        $this->auth = $this->initAuth->newAuth($token);

        try {
            if ($this->auth->validateAccessToken()) :

                $this->updateToken();

            endif;
        } catch (Exception $e) {
            $response = [
                'errors' => $e
            ];
            return $this->handleError($response, 'auth', 'OAuth');
        }
    }

    private function updateToken() {
        if ($this->auth->accessTokenUpdated()) :
            $accessTokenData = $this->auth->getAccessTokenData();
            $fetchedData = [
                'access_token' => $accessTokenData['access_token'],
                'accessTokenSecret' => $accessTokenData['access_token_secret'] ?? '',
                'accessTokenExpires' => $accessTokenData['expires'],
                'refreshToken' => $accessTokenData['refresh_token'] ?? ''
            ];
            $this->saveToken($fetchedData);
        endif;
    }

    private function getToken() {
        return $this->getTokenFromJSON();
    }

    private function saveToken() {
        return $this->saveTokenToJSON();
    }

    private function getTokenFromJSON() {
        if (file_exists(MAUTIC_API['tokenFileName']) && !is_dir(MAUTIC_API['tokenFileName'])) :
            return json_decode(file_get_contents(MAUTIC_API['tokenFileName']), true);
        else :
            return;
        endif;
    }

    private function saveTokenToJSON(array $data = []) {
        $fp = fopen(MAUTIC_API['tokenFileName'], 'w');
        fwrite($fp, json_encode($data));
        fclose($fp);
    }

}
