<?php

declare(strict_types=1);

trait tadoAPI
{
    /*
     * Structure:
     * Home -> Zone(s) -> Device(s)
     */

    //#################### Home and zones

    /**
     * Gets the account information.
     * Writes name, email, username, home id, etc. as a json string to the account information attribute.
     *
     * @return string
     * Returns a json string with information of the account.
     */
    public function GetAccountInformation(): string
    {
        $accessToken = $this->GetBearerToken();
        // Send data to endpoint
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://my.tado.com/api/v1/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = [];
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            $this->SendDebug('Error', 'GetAccountInformation: ' . $error, 0);
        } else {
            $this->SendDebug('GetAccountInformation', $result, 0);
            $this->WriteAttributeString('AccountInformation', $result);
        }
        return $result;
    }

    /**
     * Gets information about the home.
     * Writes the information as a json string to the home information attribute.
     *
     * @return string
     * Returns a json string with information of the home.
     */
    public function GetHomeInformation(): string
    {
        $accessToken = $this->GetBearerToken();
        $info = json_decode($this->ReadAttributeString('AccountInformation'), true);
        if (!empty($info)) {
            if (!array_key_exists('homeId', $info)) {
                return '';
            } else {
                $homeID = $info['homeId'];
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://my.tado.com/api/v2/homes/' . $homeID);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = [];
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            $this->SendDebug('Error', 'GetHomeInformation: ' . $error, 0);
        } else {
            $this->SendDebug('HomeInformation', $result, 0);
            $this->WriteAttributeString('HomeInformation', $result);
        }
        return $result;
    }

    /**
     * Gets all available zones of the home.
     * Writes the information as a json string to the zones attribute.
     *
     * @return string
     * Returns a string of all available zones.
     */
    public function GetZoneInformation(): string
    {
        $accessToken = $this->GetBearerToken();
        $info = json_decode($this->ReadAttributeString('AccountInformation'), true);
        if (!empty($info)) {
            if (!array_key_exists('homeId', $info)) {
                return '';
            } else {
                $homeID = $info['homeId'];
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://my.tado.com/api/v2/homes/' . $homeID . '/zones');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = [];
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            $this->SendDebug('Error', 'GetZoneInformation: ' . $error, 0);
        } else {
            $this->SendDebug('GetZoneInformation', $result, 0);
            $this->WriteAttributeString('ZoneInformation', $result);
        }
        return $result;
    }

    //#################### Devices

    /**
     * Gets all available devices from all zones.
     * Adds several information about the available devices as a json string to the devices attribute.
     *
     * @return array
     * Returns an array of available devices.
     */
    public function GetDevices(): array
    {
        // Get account information
        $this->GetAccountInformation();
        // Get home information
        $this->GetHomeInformation();
        // Get zone information
        $this->GetZoneInformation();
        $devices = [];

        $info = json_decode($this->ReadAttributeString('AccountInformation'), true);
        if (!empty($info)) {
            if (!array_key_exists('homeId', $info)) {
                return [];
            } else {
                $homeID = $info['homeId'];
            }
        }
        $zones = json_decode($this->GetZoneInformation(), true);
        if (empty($zones)) {
            return [];
        }
        $i = 0;
        foreach ($zones as $zone) {
            $availableDevices = $zone['devices'];
            if (!empty($availableDevices)) {
                foreach ($availableDevices as $availableDevice) {
                    $deviceType = $availableDevice['deviceType'];
                    switch ($deviceType) {
                        case 'RU01':
                            $deviceTypeName = 'Smart Thermostat';
                            break;

                        case 'VA02':
                            $deviceTypeName = 'Smart Radiator Thermostat';
                            break;

                        default:
                            $deviceTypeName = $deviceType;
                    }
                    $devices[$i]['deviceType'] = $deviceTypeName;
                    $devices[$i]['shortSerialNo'] = $availableDevice['shortSerialNo'];
                    $devices[$i]['homeId'] = $homeID;
                    $devices[$i]['zoneId'] = $zone['id'];
                    $devices[$i]['zoneName'] = $zone['name'];
                    $devices[$i]['type'] = $zone['type'];
                    $i++;
                }
            }
        }
        $this->SendDebug('Devices', json_encode($devices), 0);
        $this->WriteAttributeString('Devices', json_encode($devices));
        return $devices;
    }

    //#################### State

    /**
     * Gets the state of the zone.
     * Writes the information about the zone state as a json string to the zone state attribute.
     *
     * @param int $ZoneID
     * -1 = state of all zones
     *
     * @return array
     * Returns an array of information about the zone(s) state.
     */
    public function GetZoneState(int $ZoneID): array
    {
        $id = false;
        $state = [];
        // Get access token
        $accessToken = $this->ReadAttributeString('AccessToken');
        if (empty($accessToken)) {
            $this->SendDebug('GetZoneInformation', 'No access token found! Get tokens first and try again.', 0);
            return [];
        }
        // Get home
        $homeID = json_decode($this->ReadAttributeString('AccountInformation'), true)['homeId'];
        if (empty($homeID)) {
            return [];
        }
        $zones = [];
        // Get zones
        $availableZones = json_decode($this->ReadAttributeString('ZoneInformation'), true);
        if (empty($availableZones)) {
            return [];
        }
        if ($ZoneID == -1) {
            foreach ($availableZones as $availableZone) {
                $zones[] = $availableZone['id'];
            }
            $id = true;
        } else {
            // Check zone id
            foreach ($availableZones as $availableZone) {
                if ($ZoneID == $availableZone['id']) {
                    $zones[] = $availableZone['id'];
                    $id = true;
                }
            }
        }
        $this->SendDebug('GetZoneState', 'Zones: ' . json_encode($zones), 0);
        if ($id) {
            foreach ($zones as $zone) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://my.tado.com/api/v2/homes/' . $homeID . '/zones/' . $zone . '/state');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $headers = [];
                $headers[] = 'Authorization: Bearer ' . $accessToken;
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);
                if ($error) {
                    $this->SendDebug('Error', 'GetZoneState, ZoneID ' . $ZoneID . ': ' . $error, 0);
                } else {
                    $this->SendDebug('GetZoneState ' . $zone, $result, 0);
                    $state[$zone] = json_decode($result, true);
                }
            }
            $this->WriteAttributeString('ZoneState', json_encode($state));
            $this->SendDebug('GetZoneState ', json_encode($state), 0);
        }
        return $state;
    }

    /**
     * Gets the data from the tado° account.
     */
    public function GetAccountData(): void
    {
        $status = IPS_GetInstance($this->InstanceID)['InstanceStatus'];
        // Check user credentials
        if ($status != 201 && $status != 202 && $status != 203) {
            // Get tokens
            $this->GetClientSecret();
            $this->GetBearerToken();
            // Get account information
            $status = IPS_GetInstance($this->InstanceID)['InstanceStatus'];
            if ($status != 204) {
                $this->GetAccountInformation();
                $this->GetHomeInformation();
                $this->GetZoneInformation();
                $this->GetZoneState(-1);
                $this->GetDevices();
            }
        }
        $state = json_decode($this->ReadAttributeString('ZoneState'));
        $buffer = [];
        $buffer['Method'] = 'DataUpdate';
        $buffer['Data'] = $state;
        $data = [];
        $data['DataID'] = TADO_DEVICE_DATA_GUID;
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDataToChildren($data);
    }
}