<?php

/**
 * Authenticate using Instagram.
 */
class sspmod_authinstagram_Auth_Source_Instagram extends SimpleSAML_Auth_Source {

    /** Authorization endpoint. */
    const INSTAGRAM_AUTHORIZATION_ENDPOINT = 'https://api.instagram.com/oauth/authorize';

    /** Access Token endpoint. */
    const INSTAGRAM_ACCESS_TOKEN_ENDPOINT = 'https://api.instagram.com/oauth/access_token';

    /** String used to identify our states. */
    const STAGE_INIT = 'instagram:init';

    /** Key of AuthId field in state. */
    const AUTHID = 'instagram:AuthId';

    /** Client ID. */
    private $client_id;

    /** Client Secret. */
    private $client_secret;

    /**
     * Constructor for this authentication source.
     *
     * @param array $info Information about this authentication source.
     * @param array $config Configuration.
     */
    public function __construct(array $info, array $config) {

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $configObject = SimpleSAML_Configuration::loadFromArray($config, 'authsources[' . var_export($this->authId, TRUE) . ']');

        $this->client_id = $configObject->getString('client_id');
        $this->client_secret = $configObject->getString('client_secret');
    }

    /**
     * TODO
     *
     * @param array $state
     */
    public function authenticate(&$state) {
        assert('is_array($state)');

        SimpleSAML\Logger::debug('authinstagram : start authentication');

        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;

        $stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

        SimpleSAML\Logger::debug("authinstagram : saved state with stateID=$stateID");

        // TODO urlencode redirect_uri and state
        $authorizeURLParams = array(
            'client_id' => $this->client_id,
            'redirect_uri' => SimpleSAML\Module::getModuleURL('authinstagram/linkback.php'),
            'response_type' => 'code',
            'state' => $stateID,
        );

        $authorizeURL = \SimpleSAML\Utils\HTTP::addURLParameters(self::INSTAGRAM_AUTHORIZATION_ENDPOINT, $authorizeURLParams);

        SimpleSAML\Logger::debug("authinstagram : redirecting to authorizeURL=$authorizeURL");

        \SimpleSAML\Utils\HTTP::redirectTrustedURL($authorizeURL);
    }

    /**
     * TODO
     *
     * @param $state
     *
     * @throws Exception
     */
    public function finalStep(&$state) {

        SimpleSAML\Logger::debug('authinstagram : finish authentication state=' . var_export($state, TRUE));

        // retrieve Access Token
        // documentation at: TODO
        // TODO urlencode redirect URI
        $postData = 'client_id=' . urlencode($this->client_id)
            . '&client_secret=' . urlencode($this->client_secret)
            . '&grant_type=authorization_code'
            . '&redirect_uri=' . SimpleSAML\Module::getModuleURL('authinstagram/linkback.php')
            . '&code=' . urlencode($state['authinstagram:verification_code']);

        $context = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postData,
            ),
        );

        $result = \SimpleSAML\Utils\HTTP::fetch(self::INSTAGRAM_ACCESS_TOKEN_ENDPOINT, $context);

        $response = json_decode($result, true);

        SimpleSAML\Logger::debug('authinstagram : access token endpoint response=' . var_export($response, TRUE));

        // TODO check for access token

        // attributes
        $attributes = array();
        foreach ($response['user'] as $key => $value) {
            if (is_string($value)) {
                $attributes['instagram.' . $key] = array((string)$value);
            }
        }
        $state['Attributes'] = $attributes;
        SimpleSAML\Logger::debug('authinstagram : attributes: ' . implode(", ", array_keys($attributes)));

        SimpleSAML\Logger::debug('authinstagram : finished authentication');
    }

}