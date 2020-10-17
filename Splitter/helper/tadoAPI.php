<?php

declare(strict_types=1);

trait tadoAPI
{
    #################### GET

    /**
     * This GET endpoint provides general information about the authenticated users, the homes and the devices.
     *
     * @return string
     */
    public function GetAccount(): string
    {
        $endpoint = 'https://my.tado.com/api/v2/me';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the selcted home.
     *
     * @param int $HomeID
     * @return string
     */
    public function GetHome(int $HomeID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID;
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about weather details for the selected home.
     *
     * @param int $HomeID
     * @return string
     */
    public function GetWeather(int $HomeID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/weather';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the TADO hardware installed in the selected home.
     * You will be able to see for example the battery state, software version, capabilities, etc.
     *
     * IB01 = Internet bridge
     * RU01 = Smart thermostat
     * VA01 = Smart radiator thermostat
     *
     * @param int $HomeID
     * @return string
     */
    public function GetDevices(int $HomeID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/devices';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the users and their mobile devices for the selected home.
     *
     * @param int $HomeID
     * @return string
     */
    public function GetUsers(int $HomeID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/users';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the mobile devices controlling the selected home.
     *
     * @param int $HomeID
     * @return string
     */
    public function GetMobileDevices(int $HomeID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/mobileDevices';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information for all zones of your selected home.
     *
     * @param int $HomeID
     * @return string
     */
    public function GetZones(int $HomeID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the state for the selected zone of your home.
     * Here you will be able to see the status of the different components.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @return string
     */
    public function GetZoneState(int $HomeID, int $ZoneID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/state';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the capabilities for the selected zone of your home.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @return string
     */
    public function GetZoneCapabilities(int $HomeID, int $ZoneID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/capabilities';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the early start for the selected zone of your home.
     * Only supported for heating zones.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @return string
     */
    public function GetZoneEarlyStart(int $HomeID, int $ZoneID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/earlyStart';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information of the defined timetables for the selected zone of your home.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @return string
     */
    public function GetTimeTables(int $HomeID, int $ZoneID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/schedule/timetables/';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the scheduled timetable type of the selected zone of your home.
     * With a PUT to this endpoint it is also possible to modify this.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @return string
     */
    public function GetScheduleTimeTable(int $HomeID, int $ZoneID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/schedule/activeTimetable/';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the scheduled timetable type of the selected zone of your home.
     * With a PUT to this endpoint it is also possible to modify this.
     * This can be used for heating and hot water zones.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @param string $Date
     * @return string
     */
    public function GetAwayTimeTable(int $HomeID, int $ZoneID, string $Date): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/dayReport?date=' . $Date;
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the selected schedule of the selected zone of your home.
     * Get the schedule id from the "GetTimeTables" methode.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @param int $ScheduleID
     * @return string
     */
    public function GetTimeTableDetails(int $HomeID, int $ZoneID, int $ScheduleID): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/schedule/timetables/' . $ScheduleID . '/blocks';
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    /**
     * This GET endpoint provides information about the the history of the selected zone of your home.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @param string $Date
     * @return string
     */
    public function GetZoneHistory(int $HomeID, int $ZoneID, string $Date): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/dayReport?date=' . $Date;
        return $this->SendDataToTado($endpoint, 'GET', '');
    }

    #################### PUT

    /**
     * This PUT endpoint sets the early start for the selected zone of your home.
     * Only supported for heating zones.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @param bool $State
     * false    = disabled
     * true     = enabled
     * @return string
     */
    public function SetZoneEarlyStart(int $HomeID, int $ZoneID, bool $State): string
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/earlyStart';
        $postfields = json_encode(['enabled' => $State]);
        return $this->SendDataToTado($endpoint, 'PUT', $postfields);
    }

    /**
     * This PUT endpoint will make it possible to set a manual temperature for the given zone of your home.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @param string $PowerState
     * OFF  = power off
     * ON   = power on
     * @param float $Temperature
     * @return false|string
     */
    public function SetZoneTemperatureNoTimer(int $HomeID, int $ZoneID, string $PowerState, float $Temperature)
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/overlay';
        $postfields = json_encode(['setting' => ['type' => 'HEATING', 'power' => $PowerState, 'temperature' =>['celsius' => $Temperature]], 'termination' => ['type' => 'MANUAL']]);
        return $this->SendDataToTado($endpoint, 'PUT', $postfields);
    }

    /**
     * This PUT endpoint will make it possible to set a manual temperature for the given zone of your home for a selected time.
     *
     * @param int $HomeID
     * @param int $ZoneID
     * @param string $PowerState
     * OFF  = power off
     * ON   = power on
     * @param int $Temperature
     * @param int $DurationInSeconds
     * @return false|string
     */
    public function SetZoneTemperatureTimer(int $HomeID, int $ZoneID, string $PowerState, int $Temperature, int $DurationInSeconds)
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/overlay';
        $postfields = json_encode(['setting' => ['type' => 'HEATING', 'power' => $PowerState, 'temperature' =>['celsius' => $Temperature]], 'termination' => ['type' => 'TIMER', 'durationInSeconds' => $DurationInSeconds]]);
        return $this->SendDataToTado($endpoint, 'PUT', $postfields);
    }

    // SetScheduleTimeTable to be created
    // SetAwayTimeTable to be created

    #################### DEL

    /**
     * This DELETE endpoint will stop the manual heating settings.
     * It will return to the scheduled settings for the selected zone of your home.
     *
     * @param int $HomeID
     * @param int $ZoneID
     */
    public function TurnZoneManualHeatingOff(int $HomeID, int $ZoneID)
    {
        $endpoint = 'https://my.tado.com/api/v2/homes/' . $HomeID . '/zones/' . $ZoneID . '/overlay';
        $this->SendDataToTado($endpoint, 'DELETE', '');
    }

    #################### POST

    /**
     * This POST endpoint is displaying HI! on the selected device.
     * Get the short serial number of your device from the "GetDevices" methode.
     *
     * @param string $DeviceShortSerialNumber
     */
    public function IdentifiyDevice(string $DeviceShortSerialNumber)
    {
        $this->SendDebug(__FUNCTION__, 'Short serial number: ' . $DeviceShortSerialNumber, 0);
        $endpoint = 'https://my.tado.com/api/v2/devices/' . $DeviceShortSerialNumber . '/identify';
        $this->SendDataToTado($endpoint, 'POST', '');
    }

    #################### Private

    private function SendDataToTado(string $Endpoint, string $CustomRequest, string $Postfields)
    {
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $Endpoint, 0);
        $this->SendDebug(__FUNCTION__, 'CustomRequest: ' . $CustomRequest, 0);
        $accessToken = $this->GetBearerToken();
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        $body = '';
        // Send data to endpoint
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST   => $CustomRequest,
            CURLOPT_URL             => $Endpoint,
            CURLOPT_HEADER          => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_POSTFIELDS      => $Postfields,
            CURLOPT_HTTPHEADER      => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json']]);
        $response = curl_exec($ch);
        if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:  # OK
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($response, 0, $header_size);
                    $body = substr($response, $header_size);
                    $this->SendDebug(__FUNCTION__, 'Header: ' . $header, 0);
                    $this->SendDebug(__FUNCTION__, 'Body: ' . $body, 0);
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);
            }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($ch);
        return $body;
    }
}