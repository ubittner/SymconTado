<?php

/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

/*
 * @module      Tado Cooling
 *
 * @prefix      TADO
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020, 2021
 * @license     CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @see         https://github.com/ubittner/SymconTado/
 *
 * @guids       Library
 *              {2C88856B-7D25-7502-1594-11F588E2C685}
 *
 *              Tado Cooling
 *             	{374753E5-0048-7EF7-43C5-D8AAB5CB317B}
 */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';

class TadoCooling extends IPSModule
{
    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Properties
        $this->RegisterPropertyBoolean('UseSwing', false);
        $this->RegisterPropertyInteger('DeviceType', 99999); # deprecated
        $this->RegisterPropertyString('HomeID', '');
        $this->RegisterPropertyString('HomeName', '');
        $this->RegisterPropertyString('ZoneID', '');
        $this->RegisterPropertyString('ZoneName', '');
        $this->RegisterPropertyString('ZoneType', '');
        $this->RegisterPropertyInteger('UpdateInterval', 0);

        // Variables
        // Power
        $this->RegisterVariableBoolean('Power', 'Power', '~Switch', 10);
        $this->EnableAction('Power');
        // Mode
        $profile = 'TADO.' . $this->InstanceID . '.Mode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Manual'), 'Execute', -1);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Smart Schedule'), 'Calendar', 0x00FF00);
        $this->RegisterVariableBoolean('Mode', $this->Translate('Mode'), $profile, 20);
        $this->EnableAction('Mode');
        // Device mode
        $profile = 'TADO.' . $this->InstanceID . '.DeviceMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Cool'), 'Snowflake', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Dry'), 'Drops', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Fan'), 'Ventilation', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, $this->Translate('Heat'), 'Sun', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 4, $this->Translate('Auto'), 'Climate', -1);
        $this->RegisterVariableInteger('DeviceMode', $this->Translate('Device mode'), $profile, 30);
        $this->EnableAction('DeviceMode');
        // Set point temperature
        $profile = 'TADO.' . $this->InstanceID . '.SetpointTemperature';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 2);
        }
        IPS_SetVariableProfileIcon($profile, 'Temperature');
        IPS_SetVariableProfileValues($profile, 0, 25, 0);
        IPS_SetVariableProfileDigits($profile, 1);
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', 0xA9B8C4);
        for ($i = 15; $i <= 30; $i += 0.5) {
            $color = 0x8AC1E8;
            IPS_SetVariableProfileAssociation($profile, $i, $i . ' °', '', $color);
        }
        $this->RegisterVariableFloat('SetpointTemperature', $this->Translate('Setpoint temperature'), $profile, 40);
        $this->EnableAction('SetpointTemperature');
        // Fan speed
        $profile = 'TADO.' . $this->InstanceID . '.FanSpeed';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Climate');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Low'), '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Middle'), '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('High'), '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 3, $this->Translate('Auto'), '', 0x00FF00);
        $this->RegisterVariableInteger('FanSpeed', $this->Translate('Fan speed'), $profile, 50);
        $this->EnableAction('FanSpeed');
        // Swing
        $profile = 'TADO.' . $this->InstanceID . '.Swing';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Shutter');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Off'), '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('On'), '', 0x00FF00);
        $this->RegisterVariableInteger('Swing', $this->Translate('Swing'), $profile, 60);
        $this->EnableAction('Swing');
        // Cooling timer
        $profile = 'TADO.' . $this->InstanceID . '.CoolingTimer';
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
        $this->RegisterVariableInteger('CoolingTimer', 'Timer', $profile, 70);
        $this->EnableAction('CoolingTimer');
        // Room temperature
        $this->RegisterVariableFloat('RoomTemperature', $this->Translate('Room temperature'), '~Temperature', 80);
        // Humidity
        $this->RegisterVariableFloat('AirHumidity', $this->Translate('Air humidity'), '~Humidity.F', 90);

        // Timer
        $this->RegisterTimer('UpdateCoolingState', 0, 'TADO_UpdateCoolingZoneState(' . $this->InstanceID . ');');

        // Connect to splitter
        $this->ConnectParent(TADO_SPLITTER_GUID);
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Never delete this line!
        parent::ApplyChanges();

        // Move device type property to swing property
        $deviceType = @$this->ReadPropertyInteger('DeviceType');
        if (is_int($deviceType)) {
            if ($deviceType != 99999) {
                switch ($deviceType) {
                    case 1: # Fujitsu AUYG07LVLA
                    case 2: # Fujitsu ASYG09LMCA
                        $swing = true;
                        break;

                    default: # all other known devices
                        $swing = false;
                }
                IPS_SetProperty($this->InstanceID, 'DeviceType', 99999);
                IPS_SetProperty($this->InstanceID, 'UseSwing', $swing);
                IPS_ApplyChanges($this->InstanceID);
                return;
            }
        }

        // Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // Set swing option
        IPS_SetHidden($this->GetIDForIdent('Swing'), !$this->ReadPropertyBoolean('UseSwing'));

        // Set timer
        $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        $this->SetTimerInterval('UpdateCoolingState', $milliseconds);

        // Update state
        $this->UpdateCoolingZoneState();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();

        // Delete profiles
        $profiles = ['DeviceMode', 'Mode', 'SetpointTemperature', 'FanSpeed', 'Swing', 'CoolingTimer'];
        foreach ($profiles as $profile) {
            $profileName = 'TADO.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        return json_encode($formData);
    }

    public function ReceiveData($JSONString)
    {
        // Received data from splitter, not used at the moment
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Power':
                $this->TogglePower($Value);
                break;

            case 'Mode':
                $this->ToggleCoolingMode($Value);
                break;

            case 'DeviceMode':
                $this->ToggleDeviceMode($Value);
                break;

            case 'SetpointTemperature':
                $this->SetCoolingTemperature($Value);
                break;

            case 'FanSpeed':
                $this->SetFanSpeed($Value);
                break;

            case 'Swing':
                $this->SetSwingState($Value);
                break;

            case 'CoolingTimer':
                $this->SetCoolingTimer($Value);
                break;

        }
    }

    public function TogglePower(bool $State): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Mode: ' . json_encode($State) . ' (' . microtime(true) . ')', 0);
        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', $State);
        $this->SetCooling();
    }

    public function ToggleCoolingMode(bool $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Mode: ' . json_encode($Mode) . ' (' . microtime(true) . ')', 0);
        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Mode', $Mode);
        // Manual mode
        if (!$Mode) {
            $this->SendDebug(__FUNCTION__, 'Mode: Manual', 0);
            $this->SetValue('Power', true);
            $this->SetCooling();
        }
        // Smart schedule (automatic)
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
            $this->UpdateCoolingZoneState();
        }
    }

    public function ToggleDeviceMode(int $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Mode: ' . json_encode($Mode) . ' (' . microtime(true) . ')', 0);
        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('Mode', false);
        $this->SetValue('DeviceMode', $Mode);
        $this->SetCooling();
    }

    public function SetCoolingTemperature(float $Temperature): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Temperatur: ' . json_encode($Temperature) . ' (' . microtime(true) . ')', 0);
        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('Mode', false);
        $this->SetValue('SetpointTemperature', $Temperature);
        $this->SetCooling();
    }

    public function SetFanSpeed(int $Speed): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Speed: ' . json_encode($Speed) . ' (' . microtime(true) . ')', 0);
        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('Mode', false);
        $this->SetValue('FanSpeed', $Speed);
        $this->SetCooling();
    }

    public function SetSwingState(int $State): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Temperatur: ' . json_encode($State) . ' (' . microtime(true) . ')', 0);
        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('Mode', false);
        $this->SetValue('Swing', $State);
        $this->SetCooling();
    }

    public function SetCoolingTimer(int $Duration): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Duration: ' . json_encode($Duration) . ' (' . microtime(true) . ')', 0);
        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('Mode', false);
        $this->SetValue('CoolingTimer', $Duration);
        $this->SetCooling();
    }

    public function UpdateCoolingZoneState(): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed. (' . microtime(true) . ')', 0);
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
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
            $mode = 1; # smart schedule
            if (array_key_exists('overlayType', $result)) {
                if ($result['overlayType'] == 'MANUAL') {
                    $mode = 0;
                }
            }
            $this->SetValue('Mode', $mode);
            // Setting
            if (array_key_exists('setting', $result)) {
                // Power
                if (array_key_exists('power', $result['setting'])) {
                    $power = $result['setting']['power'];
                    $powerState = false;
                    if ($power == 'ON') {
                        $powerState = true;
                    }
                    $this->SetValue('Power', $powerState);
                }
                // Device mode
                if (array_key_exists('mode', $result['setting'])) {
                    $mode = $result['setting']['mode'];
                    switch ($mode) {
                        case 'DRY':
                            $modeValue = 1;
                            break;

                        case 'FAN':
                            $modeValue = 2;
                            break;

                        case 'HEAT':
                            $modeValue = 3;
                            break;

                        case 'AUTO':
                            $modeValue = 4;
                            break;

                        default:
                            $modeValue = 0;
                    }
                    $this->SetValue('DeviceMode', $modeValue);
                }
                // Setpoint temperature
                if (array_key_exists('temperature', $result['setting'])) {
                    $temperatureSettings = $result['setting']['temperature'];
                    if (is_array($temperatureSettings)) {
                        if (array_key_exists('celsius', $temperatureSettings)) {
                            $temperature = floatval($temperatureSettings['celsius']);
                            $this->SetValue('SetpointTemperature', $temperature);
                        }
                    }
                }
                // Fan speed
                if (array_key_exists('fanSpeed', $result['setting'])) {
                    $fanSpeed = (string) $result['setting']['fanSpeed'];
                    switch ($fanSpeed) {
                        case 'LOW':
                            $fanSpeedState = 0;
                            break;

                        case 'MIDDLE':
                            $fanSpeedState = 1;
                            break;

                        case 'HIGH':
                            $fanSpeedState = 2;
                            break;

                        default:
                            $fanSpeedState = 3;
                    }
                    $this->SetValue('FanSpeed', $fanSpeedState);
                }
                // Swing
                if (array_key_exists('swing', $result['setting'])) {
                    $swing = (string) $result['setting']['swing'];
                    $swingState = 0;
                    if ($swing == 'ON') {
                        $swingState = 1;
                    }
                    $this->SetValue('Swing', $swingState);
                }
            }
            // Timer
            if (array_key_exists('overlay', $result)) {
                $coolingTimer = 0;
                $overlay = $result['overlay'];
                if (is_array($overlay)) {
                    if (array_key_exists('termination', $overlay)) {
                        $termination = $overlay['termination'];
                        if (is_array($termination)) {
                            if (array_key_exists('typeSkillBasedApp', $termination)) {
                                $type = $termination['typeSkillBasedApp'];
                                $this->SendDebug(__FUNCTION__, 'Timer type: ' . $type, 0);
                                if ($type == 'NEXT_TIME_BLOCK') {
                                    $coolingTimer = 1;
                                }
                                if ($type == 'TIMER') {
                                    if (array_key_exists('remainingTimeInSeconds', $termination)) {
                                        $coolingTimer = $termination['remainingTimeInSeconds'];
                                    }
                                }
                            }
                        }
                    }
                }
                $this->SetValue('CoolingTimer', $coolingTimer);
            }
            // Sensor
            if (array_key_exists('sensorDataPoints', $result)) {
                // Inside temperature
                if (array_key_exists('insideTemperature', $result['sensorDataPoints'])) {
                    $insideTemperature = $result['sensorDataPoints']['insideTemperature']['celsius'];
                    $this->SetValue('RoomTemperature', (float) $insideTemperature);
                }
                // Humidity
                if (array_key_exists('humidity', $result['sensorDataPoints'])) {
                    $humidity = $result['sensorDataPoints']['humidity']['percentage'];
                    $this->SetValue('AirHumidity', (float) $humidity);
                }
            }
        }
        // Set next timer interval
        $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        $this->SetTimerInterval('UpdateCoolingState', $milliseconds);
    }

    public function SetCooling(): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed. (' . microtime(true) . ')', 0);
        // Disable update during cooling setting
        $this->SetTimerInterval('UpdateCoolingState', 0);
        // Prepare data
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $homeID = $this->ReadPropertyString('HomeID');
        $zoneID = $this->ReadPropertyString('ZoneID');
        // Power off
        if (!$this->GetValue('Power')) {
            $buffer['Command'] = 'SetCoolingZone';
            $postfields['termination'] = ['typeSkillBasedApp' => 'MANUAL'];
            $postfields['setting'] = ['power' => 'OFF', 'type' => 'AIR_CONDITIONING'];
            $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID, 'overlay' => json_encode($postfields)];
        } else {
            // Power on
            // Smart schedule
            if ($this->GetValue('Mode')) {
                $buffer['Command'] = 'StopManualMode';
                $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID]; //bug fix
            } else {
                // Manual mode
                $buffer['Command'] = 'SetCoolingZone';
                // Timer
                $coolingTimer = $this->GetValue('CoolingTimer');
                if ($coolingTimer == 0) { # No timer
                    $postfields['termination'] = ['typeSkillBasedApp' => 'MANUAL'];
                }
                if ($coolingTimer == 1) { # Timer till next time block
                    $postfields['termination'] = ['typeSkillBasedApp' => 'NEXT_TIME_BLOCK'];
                }
                if ($coolingTimer >= 300) { # Timer
                    $postfields['termination'] = ['type' => 'TIMER', 'durationInSeconds' => $coolingTimer];
                }
                // Temperature
                $postfields['setting']['temperature'] = ['celsius' => $this->GetValue('SetpointTemperature'), 'fahrenheit' => (float) (($this->GetValue('SetpointTemperature') * 9 / 5) + 32)];
                // Device mode
                switch ($this->GetValue('DeviceMode')) {
                    case 1:
                        $deviceMode = 'DRY';
                        break;

                    case 2:
                        $deviceMode = 'FAN';
                        break;

                    case 3:
                        $deviceMode = 'HEAT';
                        break;

                    case 4:
                        $deviceMode = 'AUTO';
                        break;

                    default:
                        $deviceMode = 'COOL';
                }
                $postfields['setting']['mode'] = $deviceMode;
                // Type
                $postfields['setting']['type'] = 'AIR_CONDITIONING';
                // Power
                $postfields['setting']['power'] = 'ON';
                // Fan speed
                switch ($this->GetValue('FanSpeed')) {
                    case 0:
                        $fanSpeed = 'LOW';
                        break;

                    case 1:
                        $fanSpeed = 'MIDDLE';
                        break;

                    case 2:
                        $fanSpeed = 'HIGH';
                        break;

                    default:
                        $fanSpeed = 'AUTO';
                }
                $postfields['setting']['fanSpeed'] = $fanSpeed;
                // Swing
                if ($this->ReadPropertyBoolean('UseSwing')) {
                    $swing = 'OFF';
                    if ($this->GetValue('Swing')) {
                        $swing = 'ON';
                    }
                    $postfields['setting']['swing'] = $swing;
                }

                ########## Check device mode

                // COOL: we need teperature and fanspeed, if device has swing mode we also need swing mode
                // DRY: without temperature and fan speed, if device has swing mode we also need swing mode
                if ($deviceMode == 'DRY') {
                    unset($postfields['setting']['temperature']);
                    unset($postfields['setting']['fanSpeed']);
                }
                // FAN: without temperature and fanspeed, if device has swing mode we also need swing mode
                if ($deviceMode == 'FAN') {
                    unset($postfields['setting']['temperature']);
                    unset($postfields['setting']['fanSpeed']);
                }
                // HEAT: we need temperature and fanspeed, if device has swing mode we also need swing mode
                // AUTO: without temperature and fanspeed
                if ($deviceMode == 'AUTO') {
                    unset($postfields['setting']['temperature']);
                    unset($postfields['setting']['fanSpeed']);
                }
                // Add data
                $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID, 'overlay' => json_encode($postfields)];
            }
        }
        // Send data
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $result = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        // Update
        $this->UpdateCoolingZoneState();
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
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
}