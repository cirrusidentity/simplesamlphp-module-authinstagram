<?php

$handler = new \SimpleSAML\Module\authoauth2\OAuth2ResponseHandler();
if ($handler->canHandleResponse()) {
    $handler->handleResponse();
    return;
}

/**
 * Handle linkback() response from Instagram.
 */

SimpleSAML_Logger::debug('authinstagram : linkback request=' . var_export($_REQUEST, TRUE));

if (!array_key_exists('state', $_REQUEST) || empty($_REQUEST['state'])) {
    throw new SimpleSAML_Error_BadRequest('Missing state parameter on Instagram linkback endpoint.');
}
$state = SimpleSAML_Auth_State::loadState($_REQUEST['state'], sspmod_authinstagram_Auth_Source_Instagram::STAGE_INIT);

// Find authentication source
if (!array_key_exists(sspmod_authinstagram_Auth_Source_Instagram::AUTHID, $state)) {
    throw new SimpleSAML_Error_BadRequest('No data in state for ' . sspmod_authinstagram_Auth_Source_Instagram::AUTHID);
}
$sourceId = $state[sspmod_authinstagram_Auth_Source_Instagram::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
    throw new SimpleSAML_Error_BadRequest('Could not find authentication source with id ' . var_export($sourceId, TRUE));
}

if (array_key_exists('code', $_REQUEST)) {
    $state['authinstagram:verification_code'] = $_REQUEST['code'];
} else {
    if ($_REQUEST['error'] === 'access_denied' && $_REQUEST['error_reason'] === 'user_denied') {
        $e = new SimpleSAML_Error_UserAborted();
        SimpleSAML_Auth_State::throwException($state, $e);
    }

    $e = new SimpleSAML_Error_AuthSource($sourceId, 'Authentication failed: ['.$_REQUEST['error'].'] '.$_REQUEST['error_description']);
    SimpleSAML_Auth_State::throwException($state, $e);
}

try {
    $source->finalStep($state);
} catch (SimpleSAML_Error_Exception $e) {
    SimpleSAML_Auth_State::throwException($state, $e);
} catch (Exception $e) {
    SimpleSAML_Auth_State::throwException($state, new SimpleSAML_Error_AuthSource($sourceId, 'Error on Instagram linkback endpoint.', $e));
}

SimpleSAML_Auth_Source::completeAuth($state);
