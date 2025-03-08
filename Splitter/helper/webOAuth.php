<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

trait webOAuth
{
    public function InitiateDeviceCodeGrantFlow(): void
    {
        $verificationUri = '';
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL             => 'https://login.tado.com/oauth2/device_authorize?client_id=1bb50063-6b0c-4d11-bd99-387f4a91cc46&scope=offline_access',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
        ]);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (!curl_errno($curl)) {
            switch ($http_code) {
                case 200: # OK
                    $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
                    $data = json_decode($response);
                    if (!isset($data->verification_uri_complete)) {
                        die('Verification uri expected');
                    }
                    //Device code
                    $this->WriteAttributeString('DeviceCode', $data->device_code);
                    $this->WriteAttributeInteger('DeviceCodeExpires', time() + $data->expires_in);
                    $date = date('d.m.y H:i:s', time() + $data->expires_in);
                    $this->WriteAttributeString('DeviceCodeValidUntil', $date);
                    $this->SendDebug(__FUNCTION__, 'Valid until: ' . $date, 0);

                    //Verification uri
                    $verificationUri = $data->verification_uri_complete;
                    $this->WriteAttributeString('VerificationUri', $verificationUri);
                    $this->SendDebug(__FUNCTION__, 'Verification uri: ' . $verificationUri, 0);
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);

            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, $this->Translate('An error has occurred') . ': ' . json_encode($error_msg), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', ' . $this->Translate('An error has occurred') . ': ' . json_encode($error_msg), KL_ERROR);
        }
        curl_close($curl);
        if ($verificationUri != '') {
            echo $verificationUri;
        }
    }

    public function GetInitialTokens(): void
    {
        $execute = false;
        $deviceCode = $this->ReadAttributeString('DeviceCode');
        $expiresIn = $this->ReadAttributeInteger('DeviceCodeExpires');
        $now = time();
        if ($deviceCode != '' && $expiresIn != '') {
            if ($expiresIn > $now) {
                $execute = true;
            }
        }
        if (!$execute) {
            $this->SendDebug(__FUNCTION__, 'No valid device code found. Please restart the initialization process!', 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', No valid code found. Please restart the initialization process!', KL_ERROR);
            return;
        }
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL             => 'https://login.tado.com/oauth2/token?client_id=1bb50063-6b0c-4d11-bd99-387f4a91cc46&device_code=' . $deviceCode . '&grant_type=urn%3Aietf%3Aparams%3Aoauth%3Agrant-type%3Adevice_code',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
        ]);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (!curl_errno($curl)) {
            switch ($http_code) {
                case 200: # OK
                    $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
                    $data = json_decode($response);
                    if (!isset($data->access_token)) {
                        die('Access token expected');
                    }
                    //Access token
                    $accessToken = $data->access_token;
                    $this->SendDebug(__FUNCTION__, 'Initial access token: ' . $accessToken, 0);
                    $expires = time() + $data->expires_in;
                    $date = date('d.m.y H:i:s', $expires);
                    $this->SendDebug(__FUNCTION__, 'Initial access token valid until ' . $date, 0);
                    $this->WriteAttributeString('AccessToken', $accessToken);
                    $this->WriteAttributeInteger('AccessTokenExpires', $expires);
                    $this->WriteAttributeString('AccessTokenValidUntil', $date);
                    //Refresh token
                    $refreshToken = $data->refresh_token;
                    $this->SendDebug(__FUNCTION__, 'Refresh token: ' . $refreshToken, 0);
                    $this->WriteAttributeString('RefreshToken', $refreshToken);
                    if ($this->ReadPropertyBoolean('Active')) {
                        $this->SetStatus(102);
                        $this->ReloadForm();
                    }
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);

            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', An error has occurred: ' . json_encode($error_msg), KL_ERROR);
        }
        curl_close($curl);
    }

    /**
     * Gets the access and refresh token.
     * The bearer token (access token) is needed for all requests.
     * It is valid for 10 minutes, after this you can use the refresh token to get a new bearer token (access token).
     * This endpoint still uses refresh token rotation, meaning that the old refresh token is revoked and the new one is immediately valid.
     * Refresh tokens are valid for up to 30 days, or until they are used in the refresh token flow.
     *
     * @return string
     * Returns the access token or an error code
     *
     * @throws Exception
     */
    public function GetBearerToken(): string
    {
        $accessToken = $this->ReadAttributeString('AccessToken');
        $expires = $this->ReadAttributeInteger('AccessTokenExpires');

        //Check if we already have a valid access token in cache
        if ($expires > 0) {
            if (time() < $expires) {
                $this->SendDebug(__FUNCTION__, 'OK! Access Token is valid until ' . date('d.m.y H:i:s', $expires), 0);
                return $accessToken;
            }
        }
        //If we slipped here we need to fetch the new access token and refresh token
        $this->SendDebug(__FUNCTION__, 'Expired! Get new access and refresh token!', 0);
        $refreshToken = $this->ReadAttributeString('RefreshToken');
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL             => 'https://login.tado.com/oauth2/token?client_id=1bb50063-6b0c-4d11-bd99-387f4a91cc46&grant_type=refresh_token&refresh_token=' . $refreshToken,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
        ]);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (!curl_errno($curl)) {
            switch ($http_code) {
                case 200: # OK
                    $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
                    $data = json_decode($response);
                    if (!isset($data->access_token)) {
                        die('Access token expected');
                    }
                    //Access token
                    $accessToken = $data->access_token;
                    $this->SendDebug(__FUNCTION__, 'New access token: ' . $accessToken, 0);
                    $expires = time() + $data->expires_in;
                    $date = date('d.m.y H:i:s', $expires);
                    $this->SendDebug(__FUNCTION__, 'New access token valid until ' . $date, 0);
                    $this->WriteAttributeString('AccessToken', $accessToken);
                    $this->WriteAttributeInteger('AccessTokenExpires', $expires);
                    $this->WriteAttributeString('AccessTokenValidUntil', $date);
                    //Refresh token
                    $refreshToken = $data->refresh_token;
                    $this->SendDebug(__FUNCTION__, 'New refresh token: ' . $refreshToken, 0);
                    $this->WriteAttributeString('RefreshToken', $refreshToken);
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);

            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', An error has occurred: ' . json_encode($error_msg), KL_ERROR);
        }
        curl_close($curl);
        //Return current access token
        return $accessToken;
    }
}