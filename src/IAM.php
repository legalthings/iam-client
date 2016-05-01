<?php

namespace LegalThings;

/**
 * Interface to the IAM API
 */
class IAM
{
    /**
     * The URL to the API
     * @var string
     */
    public static $url;
    
    /**
     * Additional guzzle options
     * @var array
     */
    public static $guzzleOptions = [
        'timeout' => 5
    ];

    
    /**
     * Get the HTTP client
     * 
     * @return \GuzzleHttp\Client
     */
    protected static function conn()
    {
        $options = static::$guzzleOptions;
        $options['base_url'] = static::$url;
        
        $options['defaults']['headers']['Accept'] = 'application/json, text/plain';
        $options['defaults']['headers']['Content-Type'] = 'application/json';
        
        return new GuzzleHttp\Client($options);
    }

    /**
     * Send a GET request to IAM
     * 
     * @param string $type     'GET', 'POST', 'PUT', 'DELETE'
     * @param string $url
     * @param object $payload  Payload for POST/PUT requests
     * @return object
     */
    protected static function request($type, $url, $payload = null)
    {
        $request = new Request(strtoupper($type), $url);
        if (isset($payload)) $request->setBody(GuzzleHttp\Stream\Stream::factory(json_encode($payload)));
        
        $response = static::conn()->get($request, ['exceptions' => false]);
        $status = $response->getStatusCode();
        $contentType = preg_replace('/\s*;.*$/', '', $response->getHeader('content-type'));

        // Not found
        if ($status === 404 && $contentType === 'text/plain') return null;
        
        // Unexpected response
        if ($status >= 300 || !in_array($contentType, ['application/json', 'text/plain'])) {
            $message = $contentType === 'text/plain'
                ? $response->getBody()
                : "Server responded with a $status status and $contentType";
            throw new Exception("Failed to fetch '$url' from IAM: $message");
        }
        
        // OK
        $data = json_decode($response->body());
        if (!$data) throw new Exception("Failed to fetch '$url' from IAM: Corrupt JSON response");
        
        return $data;
    }
    
    
    /**
     * Get a user by id
     *
     * @param string $id  User id
     * @return IAM\User
     */
    public static function getUser($id)
    {
        $data = static::request('GET', "/users/$id");
        return IAM\User::fromData($data);
    }
    
    /**
     * Get a organization by id
     *
     * @param string $id  Organization id
     * @return IAM\Organization
     */
    public static function getOrganization($id)
    {
        $data = static::request('GET', "/organizations/$id");
        return IAM\Organization::fromData($data);
    }

    
    /**
     * Get a session by id
     *
     * @param string $id  Session id
     * @return IAM\Session
     */
    public static function getSession($id)
    {
        $data = static::request('GET', "/sessions/$id");
        return IAM\Session::fromData($data);
    }
    
    /**
     * Create a one time session.
     * 
     * @param IAM\User|object $party  User or external party
     * @param string          $state  UI route state
     * @param array|object    $data   UI route data
     * @return IAM\Session
     */
    public static function createOneTimeSession($party, $state, $data = null)
    {
        $payload = [];
        
        $partyType = $party instanceof IAM\User ? 'user' : 'party';
        $payload[$partyType] = $party;

        $payload['action']['state'] = $state;
        if (isset($data)) $payload['action']['data'] = $data;

        $sessionData = static::request('POST', '/sessions', $payload);
        return IAM\Session::fromData($sessionData);
    }
}
