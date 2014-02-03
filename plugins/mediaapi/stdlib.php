<?php
require_once 'curl_client.php';

/**
 * Is the session already started?
 * @return boolean
 */
function mediaapi_is_session_started()
{
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

/**
 * Starts a php session
 * @param void
 * @return null
 */
function mediaapi_session_start()
{
    if (mediaapi_is_session_started() === FALSE) {
        session_start();
    }
}

/**
 * Retrieve the resource derivative data
 * @param int $ref
 * @return array
 */
function mediaapi_get_derivative_resources($ref)
{
    return sql_query("SELECT * FROM mediaapi_derivatives WHERE alt_file_id='" . $ref . "'");
}

/**
 * Insert or update derivative data to mediaapi_derivatives table
 *
 * @param string $ref
 * @param array $data
 */
function mediaapi_upsert_derivative_resources($ref, array $data)
{
    $update = "alt_file_id='{$ref}', ";
    foreach ($data as $key => $val) {
        if ($val != "") {
            $update .= "{$key}='{$val}', ";
        }
    }
    $update = rtrim($update, ", ");
    sql_query("INSERT INTO mediaapi_derivatives SET " . $update . "
               ON DUPLICATE KEY UPDATE " . $update);
}

/**
 * Generate a derivate metadata
 *
 * @param string $ref            Resource id
 * @param string $derivative_ref Derivate id/Alternative id
 * @return array
 */
function mediaapi_generate_derivative_metadata($ref, $derivative_ref = -1)
{
    global $storagedir;

    $file_data     = ($derivative_ref !== -1) ? get_alternative_file($ref, $derivative_ref) : null;
    $res_data      = get_resource_data($ref);
    $dir_file_path = get_resource_path($ref, true, "", false, $res_data['file_extension'], -1, 1, false, "", $derivative_ref);

    $filename_rootpath = trim(str_replace($storagedir, '', $dir_file_path), '/ ');
    $filename  = substr($filename_rootpath, (strrpos($filename_rootpath, '/') + 1));
    $extension = $res_data['file_extension'] ?: substr($filename, (strrpos($filename, '.') + 1));

    $return = array();

    if (!empty($file_data['name'])) {
        $return['short_name'] = str_replace(".{$extension}", '', $file_data['name']);
    }

    $return['prefix']          = $extension;
    $return['file_path']       = trim(str_replace($filename, '', $filename_rootpath), '/ ');
    $return['file_name']       = str_replace(".{$extension}", '', $filename);
    $return['file_extension']  = $extension;
    $return['use_extension']   = ($extension === 'mp4') ? 'y' : 'n';
    $return['is_downloadable'] = 'y';
    $return['is_streamable']   = in_array($extension, array('mp4', 'mp3')) ? 'y' : 'n';

    return $return;
}

/**
 * Gather derivative data.
 * Checks for php globals first then defaults to $data if it exists
 *
 * @param  array $data Data seed
 * @return array
 */
function mediaapi_collect_derivative_data(array $data = null)
{
    $derivative = array();
    $derivative['short_name']      = getvalescaped("short_name", (isset($data['short_name']) ? $data['short_name'] : ""));
    $derivative['prefix']          = getvalescaped("prefix", (isset($data['prefix']) ? $data['prefix'] : ""));
    $derivative['file_path']       = getvalescaped("file_path", (isset($data['file_path']) ? $data['file_path'] : ""));
    $derivative['file_name']       = getvalescaped("file_name", (isset($data['file_name']) ? $data['file_name'] : ""));
    $derivative['file_extension']  = getvalescaped("file_extension", (isset($data['file_extension']) ? $data['file_extension'] : ""));
    $derivative['use_extension']   = getvalescaped("use_extension", (isset($data['use_extension']) ? $data['use_extension'] : ""));
    $derivative['is_downloadable'] = getvalescaped("is_downloadable", (isset($data['is_downloadable']) ? $data['is_downloadable'] : ""));
    $derivative['is_streamable']   = getvalescaped("is_streamable", (isset($data['is_streamable']) ? $data['is_streamable'] : ""));
    $derivative['is_primary']      = getvalescaped("is_primary", (isset($data['is_primary']) ? $data['is_primary'] : ""));

    return $derivative;
}

/**
 * Inserts derivative data to the mediaapi_derivatives table
 *
 * @param string $resource_ref    The resource id/primary key
 * @param string $derivative_ref  Derivative id/Alternative id
 * @param int    $ordinal         Ordinal for the derivative
 */
function mediaapi_insert_derivative_data($resource_ref, $derivative_ref, $ordinal = 1, array $data = null)
{
    if (null === $data) {
        $data = mediaapi_generate_derivative_metadata($resource_ref, $derivative_ref);
        $data['ordinal']    = $ordinal;
        $data['is_primary'] = ($ordinal === 1) ? 'y' : 'n';
    }

    mediaapi_upsert_derivative_resources($derivative_ref, $data);
}

function mediaapi_get_accesstoken()
{
    global $mediaapi_oauth_url, $oauth2_username, $oauth2_password, $oauth2_client_secret, $oauth2_client_id, $oauth2_scope;

    $now = date('Y-m-d H:i:s');

    // check first if there is an existing access_token or refresh_token that has not expired
    $token = sql_value('SELECT mediaapi_token as value FROM mediaapi_oauth_tokens WHERE mediaapi_type="access" AND mediaapi_expiration > "' . $now . '"', null);

    if (null !== $token) {
        return $token;
    }

    // if no access token, check for refresh token first
    $refresh_token = sql_value('SELECT mediaapi_token as value FROM mediaapi_oauth_tokens WHERE mediaapi_type="refresh" AND mediaapi_expiration > "' . $now . '"', null);
    if (null !== $refresh_token) {
        $curl = new CurlClient($mediaapi_oauth_url . '/request');
        $curl->setRequestType('POST');
        $curl->setBody(json_encode(array(
            "grant_type"    => "refresh_token",
            "refresh_token" => $refresh_token,
            "client_id"     => $oauth2_client_id,
            "client_secret" => $oauth2_client_secret,
        )));

        $response = json_decode($curl->send());
        if (!empty($response['access_token'])) {
            return $response['access_token'];
        }
    }

    // still no access token, make a regular request
    $curl = new CurlClient($mediaapi_oauth_url . '/request');
    $curl->setRequestType('POST');
    $curl->setBody(json_encode(array(
        "grant_type"    => "password",
        "username"      => $oauth2_username,
        "password"      => $oauth2_password,
        "client_id"     => $oauth2_client_id,
        "client_secret" => $oauth2_client_secret,
        "scope"         => $oauth2_scope,
    )));
var_dump($curl->send());die;
    $response = json_decode($curl->send());
    if (!empty($response['access_token'])) {
        return $response['access_token'];
    }
}
