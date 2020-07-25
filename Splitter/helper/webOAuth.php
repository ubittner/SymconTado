<?php

declare(strict_types=1);

trait webOAuth
{
    /**
     * Gets the client secret for my.tado.com.
     *
     * @return string
     * Returns the client secret.
     */
    public function GetClientSecret(): string
    {
        $clientSecret = '';
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        // Send data to endpoint
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://my.tado.com/webapp/env.js',
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER     => [
                'Accept: */*',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Host: my.tado.com',
                'User-Agent: SymconTado/1.00-1',
                'accept-encoding: gzip, deflate',
                'cache-control: no-cache'
            ],
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!curl_errno($ch)) {
            switch ($http_code) {
                case 200:  # OK
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($response, 0, $header_size);
                    $body = substr($response, $header_size);
                    $this->SendDebug(__FUNCTION__, 'Header: ' . $header, 0);
                    $this->SendDebug(__FUNCTION__, 'Body: ' . $body, 0);
                    preg_match('/clientSecret:(.*)/', $response, $matches);
                    if (array_key_exists(1, $matches)) {
                        $clientSecret = str_replace(["'", ' '], '', $matches[1]);
                        $this->SendDebug(__FUNCTION__, 'ClientSecret: ' . $clientSecret, 0);
                        $this->SetBuffer('ClientSecret', json_encode(['ClientSecret' => $clientSecret]));
                    }
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);
            }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
            $this->LogMessage(__FUNCTION__ . ', An error has occurred: ' . json_encode($error_msg), 10205);
        }
        curl_close($ch);
        return $clientSecret;
    }

    public function FetchAccessToken()
    {
        $accessToken = '';
        // Check if we already have a valid access token in cache
        $data = $this->GetBuffer('AccessToken');
        if ($data != '') {
            $data = json_decode($data);
            if (time() < $data->Expires) {
                $this->SendDebug(__FUNCTION__, 'OK! Access Token is valid until ' . date('d.m.y H:i:s', $data->Expires), 0);
                return $data->AccessToken;
            }
        }
        // If we slipped here we need to fetch the new access token
        $this->SendDebug(__FUNCTION__, 'Expired! Get new Access Token!', 0);
        $userName = urlencode($this->ReadPropertyString('UserName'));
        $password = urlencode($this->ReadPropertyString('Password'));
        $clientSecret = $this->GetBuffer('ClientSecret');
        if (empty($clientSecret)) {
            $clientSecret = $this->GetClientSecret();
        } else {
            $clientSecret = json_decode($this->GetBuffer('ClientSecret'))->ClientSecret;
        }
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL             => 'https://auth.tado.com/oauth/token',
            CURLOPT_HEADER          => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POSTFIELDS      => 'client_id=tado-web-app&grant_type=password&scope=home.user&username=' . $userName . '&password=' . $password . '&client_secret=' . $clientSecret,
            CURLOPT_HTTPHEADER      => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!curl_errno($ch)) {
            switch ($http_code) {
                    case 200:  # OK
                        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                        $header = substr($response, 0, $header_size);
                        $body = substr($response, $header_size);
                        $this->SendDebug(__FUNCTION__, 'Header: ' . $header, 0);
                        $this->SendDebug(__FUNCTION__, 'Body: ' . $body, 0);
                        $data = json_decode($body);
                        if (!isset($data->token_type) || $data->token_type != 'bearer') {
                            die('Bearer Token expected');
                        }
                        // Update parameters to properly cache it in the next step
                        $accessToken = $data->access_token;
                        $expires = time() + $data->expires_in;
                        $this->SendDebug(__FUNCTION__, 'AccessToken: ' . $accessToken, 0);
                        $this->SendDebug(__FUNCTION__, 'New Access Token is valid until ' . date('d.m.y H:i:s', $expires), 0);
                        // Save current access token
                        $this->SetBuffer('AccessToken', json_encode(['AccessToken' => $accessToken, 'Expires' => $expires]));
                        // Save refresh token
                        $this->SetBuffer('RefreshToken', json_encode(['RefreshToken' => $data->refresh_token]));
                        // Save scope
                        $this->SetBuffer('Scope', json_encode(['Scope' => $data->scope]));
                        break;

                    default:
                        $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);
                }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
            $this->LogMessage(__FUNCTION__ . ', An error has occurred: ' . json_encode($error_msg), 10205);
        }
        curl_close($ch);
        // Return current access token
        return $accessToken;
    }

    // ToDo: Rework necessary

    /**
     * Refreshes the tokens for authentication against my.tado.com.
     * Writes the access token and the refresh token to their attributes.
     *
     * @return array
     * Returns an array of the tokens.
     *
     */
    private function RefreshTokens(): array
    {
        $clientSecret = $this->ReadAttributeString('ClientSecret');
        $refreshToken = $this->ReadAttributeString('RefreshToken');
        $scope = $this->ReadAttributeString('Scope');
        if (empty($clientSecret) || empty($refreshToken) || empty($scope)) {
            $this->SendDebug('RefreshTokens', 'Try to get tokens first!', 0);
            $this->GetBearerToken();
            return [];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://auth.tado.com/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=refresh_token&refresh_token=' . $refreshToken . '&client_id=tado-web-app&scope=' . $scope . '&client_secret=' . $clientSecret);
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers = [];
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            $this->SendDebug('Error', 'RefreshTokens: ' . $error, 0);
            $this->SetStatus(204);
        } else {
            $this->SendDebug('RefreshTokens', $result, 0);
            $data = json_decode($result, true);
            // Access token
            $accessToken = $data['access_token'];
            if (empty($accessToken)) {
                $this->Setstatus(204);
            }
            $this->SendDebug('AccessToken', $accessToken, 0);
            $this->WriteAttributeString('AccessToken', $accessToken);
            $tokens[0]['accessToken'] = $accessToken;
            // Refresh token
            $refreshToken = $data['refresh_token'];
            $this->SendDebug('RefreshToken', $refreshToken, 0);
            $this->WriteAttributeString('RefreshToken', $refreshToken);
            $tokens[0]['refreshToken'] = $refreshToken;
        }
        //$this->SetTimerInterval('RefreshTokens', 500000);
        return $tokens;
    }
}

