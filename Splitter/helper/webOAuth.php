<?php

declare(strict_types=1);

trait webOAuth
{
    /**
     * Starts the registration and gets the device code and the verification uri.
     *
     * @return void
     * @throws Exception
     */
    public function StartRegistration(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL             => 'https://login.tado.com/oauth2/device_authorize?client_id=1bb50063-6b0c-4d11-bd99-387f4a91cc46&scope=offline_access',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => round($this->ReadPropertyInteger('Timeout') / 1000),
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
        ]);
        $response = curl_exec($ch);
        $curlError = curl_errno($ch);
        $curlErrorMessage = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$curlError) {
            switch ($httpCode) {
                case 200: //OK
                    $this->SendDebug(__FUNCTION__, $this->Translate('Response') . ': ' . $response, 0);
                    //Check whether we have received a valid json string in response
                    if (!$this->CheckJson($response)) {
                        die('Abort, json string expected');
                    }
                    //Check properties
                    $objects = json_decode($response);
                    if (!property_exists($objects, 'device_code')) {
                        die('Abort, device code expected');
                    }
                    if (!property_exists($objects, 'verification_uri_complete')) {
                        die('Abort, verification uri expected');
                    }
                    //Cache data
                    $this->CacheData($response);
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $httpCode, 0);

            }
        } else {
            $this->SendDebug(__FUNCTION__, $this->Translate('An error has occurred') . ': ' . json_encode($curlErrorMessage), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', ' . $this->Translate('An error has occurred') . ': ' . json_encode($curlErrorMessage), KL_ERROR);
        }
        $verificationUri = $this->ReadAttributeString('VerificationUri');
        if ($verificationUri != '') {
            echo $verificationUri;
        }
    }

    /**
     * Gets the initial access and refresh token.
     *
     * @return void
     * @throws Exception
     */
    public function GetInitialTokens(): void
    {
        //If we already have an access and a refresh token, then we don't need the initial tokens again
        if ($this->CheckTokens()) {
            $this->SendDebug(__FUNCTION__, $this->Translate('Abort, tokens already exist!'), 0);
            return;
        }
        //If we don't have the initial tokens, we will check for a valid device token.
        $deviceCodeValid = false;
        $deviceCode = $this->ReadAttributeString('DeviceCode');
        $expiresIn = $this->ReadAttributeInteger('DeviceCodeExpires');
        $now = time();
        if ($deviceCode != '' && $expiresIn != '') {
            if ($expiresIn > $now) {
                $deviceCodeValid = true;
            }
        }
        if (!$deviceCodeValid) {
            $this->SendDebug(__FUNCTION__, $this->Translate('Abort, no valid device code found. Please restart the registration!'), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . $this->Translate('No valid device code found. Please restart the registration!'), KL_WARNING);
            $this->SetStatus(201);
            return;
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL             => 'https://login.tado.com/oauth2/token?client_id=1bb50063-6b0c-4d11-bd99-387f4a91cc46&device_code=' . $deviceCode . '&grant_type=urn%3Aietf%3Aparams%3Aoauth%3Agrant-type%3Adevice_code',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => round($this->ReadPropertyInteger('Timeout') / 1000),
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
        ]);
        $response = curl_exec($ch);
        $curlError = curl_errno($ch);
        $curlErrorMessage = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$curlError) {
            switch ($httpCode) {
                case 200: //OK
                    $this->SendDebug(__FUNCTION__, $this->Translate('Response') . ': ' . $response, 0);
                    //Check whether we have received a valid json string in response
                    if (!$this->CheckJson($response)) {
                        die('Abort, json string expected');
                    }
                    //Check properties
                    $objects = json_decode($response);
                    if (!property_exists($objects, 'access_token')) {
                        die('Abort, access token expected');
                    }
                    if (!property_exists($objects, 'refresh_token')) {
                        die('Abort, refresh token expected');
                    }
                    //Cache data
                    $this->CacheData($response);
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $httpCode, 0);

            }
        } else {
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($curlErrorMessage), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', An error has occurred: ' . json_encode($curlErrorMessage), KL_ERROR);
        }
        if ($this->CheckTokens()) {
            echo $this->Translate('Registration successful!');
            $this->SetStatus(102);
        }
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
        //Check if we already have a valid access token in cache
        $expires = $this->ReadAttributeInteger('AccessTokenExpires');
        if ($expires > 0) {
            if (time() < $expires) {
                $this->SendDebug(__FUNCTION__, $this->Translate('OK! Access token is valid until') . ' ' . date('d.m.y H:i:s', $expires), 0);
                return $this->ReadAttributeString('AccessToken');
            }
        }
        //If we slipped here we need to fetch the new access token
        $this->SendDebug(__FUNCTION__, $this->Translate('Expired! Get new access token!'), 0);
        $refreshToken = $this->ReadAttributeString('RefreshToken');
        if ($refreshToken == '') {
            $this->SendDebug(__FUNCTION__, $this->Translate('Abort, no refresh token found. Please start the registration!'), 0);
            return json_encode(['error' => 'No refresh token!']);
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL             => 'https://login.tado.com/oauth2/token?client_id=1bb50063-6b0c-4d11-bd99-387f4a91cc46&grant_type=refresh_token&refresh_token=' . $refreshToken,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => round($this->ReadPropertyInteger('Timeout') / 1000),
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
        ]);
        $response = curl_exec($ch);
        $curlError = curl_errno($ch);
        $curlErrorMessage = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$curlError) {
            switch ($httpCode) {
                case 200: //OK
                    $this->SendDebug(__FUNCTION__, $this->Translate('Response') . ': ' . $response, 0);
                    //Check whether we have received a valid json string in response
                    if (!$this->CheckJson($response)) {
                        return json_encode(['error' => 'Abort, json string expected']);
                    }
                    //Check properties
                    $objects = json_decode($response);
                    if (!property_exists($objects, 'access_token')) {
                        return json_encode(['error' => 'Abort, no access token found']);
                    }
                    if (!property_exists($objects, 'refresh_token')) {
                        return json_encode(['error' => 'Abort, no refresh token found']);
                    }
                    //Cache data
                    $this->CacheData($response);
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $httpCode, 0);

            }
        } else {
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($curlErrorMessage), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', An error has occurred: ' . json_encode($curlErrorMessage), KL_ERROR);
            return json_encode(['error' => $curlErrorMessage]);
        }
        return $this->ReadAttributeString('AccessToken');
    }

    ########## Private

    /**
     * Checks if a sting is json encoded.
     *
     * @param string $String
     * @return bool
     * false:   no json string
     * true:    json string
     */
    private function CheckJson(string $String): bool
    {
        json_decode($String);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Caches the data from the response.
     *
     * @param string $Data
     * @return void
     * @throws Exception
     */
    private function CacheData(string $Data): void
    {
        $objects = json_decode($Data);
        //Device code
        if (property_exists($objects, 'device_code')) {
            $deviceCode = $objects->device_code;
            $this->WriteAttributeString('DeviceCode', $deviceCode);
            $this->SendDebug(__FUNCTION__, 'Device code: ' . $deviceCode, 0);
            if (property_exists($objects, 'expires_in')) {
                $expiresIn = $objects->expires_in;
                $this->WriteAttributeInteger('DeviceCodeExpires', time() + $expiresIn);
                $date = date('d.m.y H:i:s', time() + $expiresIn);
                $this->WriteAttributeString('DeviceCodeValidUntil', $date);
                $this->SendDebug(__FUNCTION__, 'Device code valid until: ' . $date, 0);
            }
            //Verification uri
            if (property_exists($objects, 'verification_uri_complete')) {
                $verificationUri = $objects->verification_uri_complete;
                $this->WriteAttributeString('VerificationUri', $verificationUri);
                $this->SendDebug(__FUNCTION__, 'Verification uri: ' . $verificationUri, 0);
            }
        }
        //Access token
        if (property_exists($objects, 'access_token')) {
            $accessToken = $objects->access_token;
            $this->WriteAttributeString('AccessToken', $accessToken);
            $this->SendDebug(__FUNCTION__, 'Access token: ' . $accessToken, 0);
            if ($accessToken != '') {
                $this->UpdateFormField('AccessToken', 'caption', 'Access Token: ' . substr($accessToken, 0, 16) . ' ...');
            }
            if (property_exists($objects, 'expires_in')) {
                $expires = time() + $objects->expires_in;
                $this->WriteAttributeInteger('AccessTokenExpires', $expires);
                $date = date('d.m.y H:i:s', $expires);
                $this->WriteAttributeString('AccessTokenValidUntil', $date);
                $this->SendDebug(__FUNCTION__, $this->Translate('Access token valid until') . ' ' . $date, 0);
                if ($date != '') {
                    $this->UpdateFormField('TokenValidUntil', 'caption', $this->Translate('Valid until') . ': ' . $date);
                }
            }
        }
        //Refresh token
        if (property_exists($objects, 'refresh_token')) {
            $refreshToken = $objects->refresh_token;
            $this->WriteAttributeString('RefreshToken', $refreshToken);
            $this->SendDebug(__FUNCTION__, 'Refresh token: ' . $refreshToken, 0);
        }
    }

    /**
     * Checks for an existing access and refresh token.
     *
     * @return bool
     * false =  missing token(s)
     * true =   tokens exists
     *
     * @throws Exception
     */
    private function CheckTokens(): bool
    {
        $result = false;
        $accessToken = $this->ReadAttributeString('AccessToken');
        $accessTokenExists = false;
        if ($accessToken != '' && strlen($accessToken) > 1) {
            $accessTokenExists = true;
        }
        $refreshToken = $this->ReadAttributeString('RefreshToken');
        $refreshTokenExists = false;
        if ($refreshToken != '' && strlen($refreshToken) > 1) {
            $refreshTokenExists = true;
        }
        if ($accessTokenExists && $refreshTokenExists) {
            $result = true;
        }
        return $result;
    }
}