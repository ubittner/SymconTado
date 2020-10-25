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
        //Send data to endpoint
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
                case 200:  #OK
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
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', ' . ', An error has occurred: ' . json_encode($error_msg), KL_ERROR);
        }
        curl_close($ch);
        return $clientSecret;
    }

    /**
     * Gets the Access and Refresh Token.
     * The bearer token is needed for all requests.
     * It is valid for 10 minutes, after this you can use the refresh token or just get a new bearer token.
     *
     * @return string
     * Returns the access token or an error code
     */
    public function GetBearerToken(): string
    {
        $buffer = json_decode($this->GetBuffer('AccessToken'));
        $accessToken = $buffer->AccessToken;
        $accessTokenExpires = $buffer->Expires;
        //Check if we already have a valid access token in cache
        if (!empty($accessTokenExpires)) {
            if (time() < $accessTokenExpires) {
                $this->SendDebug(__FUNCTION__, 'OK! Access Token is valid until ' . date('d.m.y H:i:s', $accessTokenExpires), 0);
                return $accessToken;
            }
        }
        //If we slipped here we need to fetch the new access token
        $this->SendDebug(__FUNCTION__, 'Expired! Get new Access Token!', 0);
        $userName = urlencode($this->ReadPropertyString('UserName'));
        $password = urlencode($this->ReadPropertyString('Password'));
        if (empty($userName) || empty($password)) {
            return $accessToken;
        }
        $clientSecret = json_decode($this->GetBuffer('ClientSecret'))->ClientSecret;
        if (empty($clientSecret)) {
            $clientSecret = $this->GetClientSecret();
        }
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        //Send data to endpoint
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
                    case 200: #OK
                        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                        $header = substr($response, 0, $header_size);
                        $body = substr($response, $header_size);
                        $this->SendDebug(__FUNCTION__, 'Header: ' . $header, 0);
                        $this->SendDebug(__FUNCTION__, 'Body: ' . $body, 0);
                        $data = json_decode($body);
                        if (!isset($data->token_type) || $data->token_type != 'bearer') {
                            die('Bearer Token expected');
                        }
                        //Update parameters to properly cache it in the next step
                        //Save current access token
                        $accessToken = $data->access_token;
                        $expires = time() + $data->expires_in;
                        $this->SendDebug(__FUNCTION__, 'AccessToken: ' . $accessToken, 0);
                        $this->SendDebug(__FUNCTION__, 'New Access Token is valid until ' . date('d.m.y H:i:s', $expires), 0);
                        $this->SetBuffer('AccessToken', json_encode(['AccessToken' => $accessToken, 'Expires' => $expires]));
                        //Save refresh token
                        $refreshToken = $data->refresh_token;
                        $this->SendDebug(__FUNCTION__, 'RefreshToken: ' . $refreshToken, 0);
                        $this->SetBuffer('RefreshToken', json_encode(['RefreshToken' => $refreshToken]));
                        //Save scope
                        $scope = $data->scope;
                        $this->SendDebug(__FUNCTION__, 'Scope: ' . $scope, 0);
                        $this->SetBuffer('Scope', json_encode(['Scope' => $scope]));
                        break;

                    default:
                        $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);
                        $accessToken = json_encode(['error' => $http_code]);

                }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', An error has occurred: ' . json_encode($error_msg), KL_ERROR);
            $accessToken = json_encode(['error' => $error_msg]);
        }
        curl_close($ch);
        //Return current access token
        return $accessToken;
    }
}