<?php

/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

/*
 * @module      Tado Heating
 *
 * @prefix      TADO
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020
 * @license     CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @see         https://github.com/ubittner/SymconTado/
 *
 * @guids       Library
 *              {2C88856B-7D25-7502-1594-11F588E2C685}
 *
 *              Tado Heating
 *             	{F6D924F8-0CAB-2EB7-725D-2640B8F5556B}
 */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';

class TadoHeating extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->CreateProfiles();
        $this->RegisterVariables();
        $this->RegisterTimers();
        //Connect to splitter
        $this->ConnectParent(TADO_SPLITTER_GUID);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
        $this->DeleteProfiles();
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        //Never delete this line!
        parent::ApplyChanges();
        //Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        //Set timer
        $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        $this->SetTimerInterval('UpdateHeatingState', $milliseconds);
        //Update state
        $this->UpdateHeatingZoneState();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        return json_encode($formData);
    }

    public function ReceiveData($JSONString)
    {
        //Received data from splitter, not used at the moment
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Mode':
                $this->ToggleHeatingMode($Value);
                break;

            case 'SetpointTemperature':
                $this->SetHeatingTemperature($Value);
                break;

            case 'HeatingTimer':
                $this->SetHeatingTimer($Value);
                break;

        }
    }

    public function ToggleHeatingMode(bool $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Mode: ' . json_encode($Mode) . ' (' . microtime(true) . ')', 0);
        //Check parent
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Mode', $Mode);
        //Manual mode
        if (!$Mode) {
            $this->SendDebug(__FUNCTION__, 'Mode: Manual', 0);
            $this->SetHeating();
        }
        //Automatic mode
        if ($Mode) {
            $this->SendDebug(__FUNCTION__, 'Mode: Automatic', 0);
            $homeID = intval($this->ReadPropertyString('HomeID'));
            $zoneID = intval($this->ReadPropertyString('ZoneID'));
            $data = [];
            $buffer = [];
            $data['DataID'] = TADO_SPLITTER_DATA_GUID;
            $buffer['Command'] = 'StopManualMode';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            json_decode($this->SendDataToParent($data), true);
        }
        $this->UpdateHeatingZoneState();
    }

    public function SetHeatingTemperature(float $Temperature): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Temperatur: ' . json_encode($Temperature) . ' (' . microtime(true) . ')', 0);
        //Check parent
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Mode', false);
        $this->SetValue('SetpointTemperature', $Temperature);
        $this->SetHeating();
        $this->UpdateHeatingZoneState();
    }

    public function SetHeatingTimer(int $Duration): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Duration: ' . json_encode($Duration) . ' (' . microtime(true) . ')', 0);
        //Check parent
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Mode', false);
        $this->SetValue('HeatingTimer', $Duration);
        $this->SetHeating();
        $this->UpdateHeatingZoneState();
    }

    public function UpdateHeatingZoneState(): void
    {
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $homeID = $this->ReadPropertyString('HomeID');
        $zoneID = $this->ReadPropertyString('ZoneID');
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetZoneState';
        $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        if (!empty($result)) {
            // Mode
            $mode = 1; //automatic
            if (array_key_exists('overlayType', $result)) {
                if ($result['overlayType'] == 'MANUAL') {
                    $mode = 0;
                }
            }
            $this->SetValue('Mode', $mode);
            //Setpoint temperature
            if (array_key_exists('setting', $result)) {
                $this->SendDebug(__FUNCTION__, 'array key setting exits', 0);
                if (array_key_exists('temperature', $result['setting'])) {
                    $temperatureSettings = $result['setting']['temperature'];
                    if (is_array($temperatureSettings)) {
                        if (array_key_exists('celsius', $temperatureSettings)) {
                            $temperature = floatval($temperatureSettings['celsius']);
                            $this->SetValue('SetpointTemperature', $temperature);
                        }
                    }
                }
            }
            //Timer
            if (array_key_exists('overlay', $result)) {
                $heatingTimer = 0;
                $this->SendDebug(__FUNCTION__, 'array key overlay exits', 0);
                $overlay = $result['overlay'];
                if (is_array($overlay)) {
                    if (array_key_exists('termination', $overlay)) {
                        $this->SendDebug(__FUNCTION__, 'array key termination exits', 0);
                        $termination = $overlay['termination'];
                        if (is_array($termination)) {
                            if (array_key_exists('typeSkillBasedApp', $termination)) {
                                $this->SendDebug(__FUNCTION__, 'array key typeSkillBasedApp exits', 0);
                                $type = $termination['typeSkillBasedApp'];
                                $this->SendDebug(__FUNCTION__, 'Timer type: ' . $type, 0);
                                if ($type == 'TIMER' || $type == 'NEXT_TIME_BLOCK') {
                                    if (array_key_exists('remainingTimeInSeconds', $termination)) {
                                        $heatingTimer = $termination['remainingTimeInSeconds'];
                                    }
                                }
                            }
                        }
                    }
                }
                $this->SetValue('HeatingTimer', $heatingTimer);
            }
            //Sensor
            if (array_key_exists('sensorDataPoints', $result)) {
                //Inside temperature
                if (array_key_exists('insideTemperature', $result['sensorDataPoints'])) {
                    $insideTemperature = $result['sensorDataPoints']['insideTemperature']['celsius'];
                    $this->SetValue('RoomTemperature', (float) $insideTemperature);
                }
                //Humidity
                if (array_key_exists('humidity', $result['sensorDataPoints'])) {
                    $humidity = $result['sensorDataPoints']['humidity']['percentage'];
                    $this->SetValue('AirHumidity', (float) $humidity);
                }
            }
            //Heating power
            $heatingPower = 0;
            if (array_key_exists('activityDataPoints', $result)) {
                $activityDataPoints = $result['activityDataPoints'];
                if (is_array($activityDataPoints)) {
                    if (array_key_exists('heatingPower', $activityDataPoints)) {
                        $heatingPowerData = $activityDataPoints['heatingPower'];
                        if (is_array($heatingPowerData)) {
                            if (array_key_exists('percentage', $heatingPowerData)) {
                                $heatingPower = $heatingPowerData['percentage'];
                            }
                        }
                    }
                }
            }
            $this->SetValue('HeatingPower', $heatingPower);
            //Geofencing status
            if (array_key_exists('tadoMode', $result)) {
                $tadoMode = $result['tadoMode'];
                $state = false;
                if ($tadoMode == 'AWAY') {
                    $state = true;
                }
                $this->SetValue('GeofencingStatus', $state);
            }
        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function RegisterProperties(): void
    {
        $this->RegisterPropertyString('HomeID', '');
        $this->RegisterPropertyString('HomeName', '');
        $this->RegisterPropertyString('ZoneID', '');
        $this->RegisterPropertyString('ZoneName', '');
        $this->RegisterPropertyString('ZoneType', '');
        $this->RegisterPropertyInteger('UpdateInterval', 0);
    }

    private function CreateProfiles(): void
    {
        //Mode
        $profile = 'TADO.' . $this->InstanceID . '.Mode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Manual'), 'Execute', -1);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Automatic'), 'Calendar', 0x00FF00);
        //Setpoint temperature
        $profile = 'TADO.' . $this->InstanceID . '.SetpointTemperature';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 2);
        }
        IPS_SetVariableProfileIcon($profile, 'Temperature');
        IPS_SetVariableProfileValues($profile, 0, 25, 0);
        IPS_SetVariableProfileDigits($profile, 1);
        $color = -1;
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Off'), '', 0xA9B8C4);
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', 0xA9B8C4);
        for ($i = 5; $i <= 25; $i += 0.5) {
            if ($i >= 5 && $i < 7) {
                $color = 0x76A595;
            }
            if ($i >= 7 && $i < 9) {
                $color = 0x78AD98;
            }
            if ($i >= 9 && $i < 11) {
                $color = 0x7AB299;
            }
            if ($i >= 11 && $i < 13) {
                $color = 0x7BB798;
            }
            if ($i >= 13 && $i < 15) {
                $color = 0x7EBC98;
            }
            if ($i >= 15 && $i < 17) {
                $color = 0x80C397;
            }
            if ($i >= 17 && $i < 19) {
                $color = 0x82C695;
            }
            if ($i >= 19 && $i < 21) {
                $color = 0xF8D56A;
            }
            if ($i >= 21 && $i < 23) {
                $color = 0xF6C365;
            }
            if ($i >= 23 && $i < 25) {
                $color = 0xF4B160;
            }
            if ($i >= 25) {
                $color = 0xF09035;
            }
            IPS_SetVariableProfileAssociation($profile, $i, $i . ' °', '', $color);
        }
        //Heating timer
        $profile = 'TADO.' . $this->InstanceID . '.HeatingTimer';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Clock');
        IPS_SetVariableProfileValues($profile, 0, 12, 0);
        IPS_SetVariableProfileDigits($profile, 2);
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Off'), '', 0xA9B8C4);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Till next time block'), '', 0xA9B8C4);
        for ($i = 5; $i <= 45; $i += 5) {
            $seconds = $i * 60;
            $j = sprintf('%02d', $i);
            IPS_SetVariableProfileAssociation($profile, $seconds, '00:' . $j . ':00', '', -1);
        }
        IPS_SetVariableProfileAssociation($profile, 3600, '01:00:00', '', -1);
        for ($i = 15; $i <= 45; $i += 15) {
            $seconds = 3600 + ($i * 60);
            IPS_SetVariableProfileAssociation($profile, $seconds, '01:' . $i . ':00', '', -1);
        }
        IPS_SetVariableProfileAssociation($profile, 7200, '02:00:00', '', -1);
        for ($i = 15; $i <= 45; $i += 15) {
            $seconds = 7200 + ($i * 60);
            IPS_SetVariableProfileAssociation($profile, $seconds, '02:' . $i . ':00', '', -1);
        }
        IPS_SetVariableProfileAssociation($profile, 10800, '03:00:00', '', -1);
        IPS_SetVariableProfileAssociation($profile, 10800 + 1800, '03:30:00', '', -1);
        IPS_SetVariableProfileAssociation($profile, 14400, '04:00:00', '', -1);
        IPS_SetVariableProfileAssociation($profile, 14400 + 1800, '04:30:00', '', -1);
        for ($i = 5; $i <= 12; $i++) {
            $seconds = $i * 3600;
            $j = sprintf('%02d', $i);
            IPS_SetVariableProfileAssociation($profile, $seconds, $j . ':00:00', '', -1);
        }
        //Heating power
        $profile = 'TADO.' . $this->InstanceID . '.HeatingPower';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Radiator');
        IPS_SetVariableProfileText($profile, '', ' %');
        IPS_SetVariableProfileValues($profile, 0, 100, 1);
        //Geofencing status
        $profile = 'TADO.' . $this->InstanceID . '.GeofencingStatus';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Home'), 'Presence', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Away'), 'Motion', 0x0000FF);
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['Mode', 'SetpointTemperature', 'HeatingTimer', 'HeatingPower', 'GeofencingStatus'];
        foreach ($profiles as $profile) {
            $profileName = 'TADO.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    private function RegisterVariables(): void
    {
        //Mode
        $profile = 'TADO.' . $this->InstanceID . '.Mode';
        $this->RegisterVariableBoolean('Mode', $this->Translate('Automatic'), $profile, 10);
        $this->EnableAction('Mode');
        //Set point temperature
        $profile = 'TADO.' . $this->InstanceID . '.SetpointTemperature';
        $this->RegisterVariableFloat('SetpointTemperature', $this->Translate('Setpoint temperature'), $profile, 20);
        $this->EnableAction('SetpointTemperature');
        //Heating timer
        $profile = 'TADO.' . $this->InstanceID . '.HeatingTimer';
        $this->RegisterVariableInteger('HeatingTimer', 'Timer', $profile, 30);
        $this->EnableAction('HeatingTimer');
        //Room temperature
        $this->RegisterVariableFloat('RoomTemperature', $this->Translate('Room temperature'), '~Temperature', 40);
        //Humidity
        $this->RegisterVariableFloat('AirHumidity', $this->Translate('Air humidity'), '~Humidity.F', 50);
        //Heating power
        $profile = 'TADO.' . $this->InstanceID . '.HeatingPower';
        $this->RegisterVariableInteger('HeatingPower', $this->Translate('Heating Power'), $profile, 60);
        //Geofencing status
        $profile = 'TADO.' . $this->InstanceID . '.GeofencingStatus';
        $this->RegisterVariableBoolean('GeofencingStatus', 'Geofencing Status', $profile, 70);
    }

    private function RegisterTimers(): void
    {
        $this->RegisterTimer('UpdateHeatingState', 0, 'TADO_UpdateHeatingZoneState(' . $this->InstanceID . ');');
    }

    private function CheckParent(): bool
    {
        $result = true;
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            $result = false;
        }
        return $result;
    }

    private function CheckHomeID(): bool
    {
        $result = true;
        if (empty($this->ReadPropertyString('HomeID'))) {
            $this->SendDebug(__FUNCTION__, 'No HomeID assigned in the properties!', 0);
            $result = false;
        }
        return $result;
    }

    private function CheckZoneID(): bool
    {
        $result = true;
        if (empty($this->ReadPropertyString('ZoneID'))) {
            $this->SendDebug(__FUNCTION__, 'No ZoneID assigned in the properties!', 0);
            $result = false;
        }
        return $result;
    }

    private function SetHeating(): void
    {
        $homeID = intval($this->ReadPropertyString('HomeID'));
        $zoneID = intval($this->ReadPropertyString('ZoneID'));
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $power = 'ON';
        $temperature = $this->GetValue('SetpointTemperature');
        if ($temperature == 0) {
            $power = 'OFF';
        }
        $heatingTimer = $this->GetValue('HeatingTimer');
        //No Timer
        if ($heatingTimer == 0) {
            $this->SendDebug(__FUNCTION__, 'Do not use timer, set temperature for unlimited time.', 0);
            $buffer['Command'] = 'SetHeatingZoneTemperature';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $temperature];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            $result = json_decode($this->SendDataToParent($data), true);
            $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        }
        //Timer till next time block
        if ($heatingTimer == 1) {
            $this->SendDebug(__FUNCTION__, 'Use heating timer, set temperature till next time block.', 0);
            $buffer['Command'] = 'SetHeatingZoneTemperatureTimerNextTimeBlock';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $temperature];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            $result = json_decode($this->SendDataToParent($data), true);
            $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        }
        //Timer
        if ($heatingTimer >= 300) {
            $this->SendDebug(__FUNCTION__, 'Use heating timer, set temperature for ' . $heatingTimer . 'seconds.', 0);
            $buffer['Command'] = 'SetHeatingZoneTemperatureTimer';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $temperature, 'durationInSeconds' => $heatingTimer];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            $result = json_decode($this->SendDataToParent($data), true);
            $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        }
    }
}