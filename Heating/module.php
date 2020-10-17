<?php

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
        //Set update timer
        $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        $this->SetTimerInterval('Update', $milliseconds);
        //Update
        $this->UpdateZoneState();
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
                $this->ToggleMode($Value);
                break;

            case 'SetPointTemperature':
                $this->SetTemperature($Value);
                break;

            case 'HeatingTimer':
                $this->SetHeatingTimer($Value);
                break;

        }
    }

    public function ToggleMode(bool $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Mode: ' . json_encode($Mode) . '. (' . microtime(true) . ')', 0);
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
        $homeID = intval($this->ReadPropertyString('HomeID'));
        $zoneID = intval($this->ReadPropertyString('ZoneID'));
        $temperature = $this->GetValue('SetPointTemperature');
        $power = 'ON';
        if ($temperature == 0) {
            $power = 'OFF';
        }
        $heatingTimer = $this->GetValue('HeatingTimer');
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        //Manual mode
        if (!$Mode) {
            $this->SendDebug(__FUNCTION__, 'Mode: Manual', 0);
            //No Timer
            if ($heatingTimer == 0) {
                $this->SendDebug(__FUNCTION__, 'Do not use timer, set temperature for unlimited time.', 0);
                $buffer['Command'] = 'SetZoneTemperatureNoTimer';
                $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $temperature];
                $data['Buffer'] = $buffer;
                $data = json_encode($data);
                $result = json_decode($this->SendDataToParent($data), true);
                $this->SendDebug(__FUNCTION__, json_encode($result), 0);
            }
            //Timer
            else {
                $duration = $heatingTimer * 3600;
                $this->SendDebug(__FUNCTION__, 'Use heating timer, set temperature for ' . $duration . 'seconds.', 0);
                $buffer['Command'] = 'SetZoneTemperatureTimer';
                $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $temperature, 'durationInSeconds' => $duration];
                $data['Buffer'] = $buffer;
                $data = json_encode($data);
                $result = json_decode($this->SendDataToParent($data), true);
                $this->SendDebug(__FUNCTION__, json_encode($result), 0);
            }
        }
        //Automatic mode
        if ($Mode) {
            $this->SendDebug(__FUNCTION__, 'Mode: Automatic', 0);
            $buffer['Command'] = 'TurnZoneManualHeatingOff';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            json_decode($this->SendDataToParent($data), true);
        }
        $this->UpdateZoneState();
    }

    public function SetTemperature(float $Temperature): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Temperatur: ' . json_encode($Temperature) . '. (' . microtime(true) . ')', 0);
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
        $this->SetValue('SetPointTemperature', $Temperature);
        $power = 'ON';
        if ($Temperature == 0) {
            $power = 'OFF';
        }
        $this->SetValue('Mode', false);
        $homeID = intval($this->ReadPropertyString('HomeID'));
        $zoneID = intval($this->ReadPropertyString('ZoneID'));
        $heatingTimer = $this->GetValue('HeatingTimer');
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        //No Timer
        if ($heatingTimer == 0) {
            $this->SendDebug(__FUNCTION__, 'Do not use timer, set temperature for unlimited time.', 0);
            $buffer['Command'] = 'SetZoneTemperatureNoTimer';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $Temperature];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            $result = json_decode($this->SendDataToParent($data), true);
            $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        }
        //Timer
        else {
            $duration = $heatingTimer * 3600;
            $this->SendDebug(__FUNCTION__, 'Use heating timer, set temperature for ' . $duration . 'seconds.', 0);
            $buffer['Command'] = 'SetZoneTemperatureTimer';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $Temperature, 'durationInSeconds' => $duration];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            $result = json_decode($this->SendDataToParent($data), true);
            $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        }
        $this->UpdateZoneState();
    }

    public function SetHeatingTimer(float $Duration)
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Duration: ' . json_encode($Duration) . '. (' . microtime(true) . ')', 0);
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
        $homeID = intval($this->ReadPropertyString('HomeID'));
        $zoneID = intval($this->ReadPropertyString('ZoneID'));
        $temperature = $this->GetValue('SetPointTemperature');
        $power = 'ON';
        if ($temperature == 0) {
            $power = 'OFF';
        }
        $heatingTimer = $this->GetValue('HeatingTimer');
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        //No Timer
        if ($heatingTimer == 0) {
            $this->SendDebug(__FUNCTION__, 'Do not use timer, set temperature for unlimited time.', 0);
            $buffer['Command'] = 'SetZoneTemperatureNoTimer';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $temperature];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            $result = json_decode($this->SendDataToParent($data), true);
            $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        }
        //Timer
        else {
            $duration = $heatingTimer * 3600;
            $this->SendDebug(__FUNCTION__, 'Use heating timer, set temperature for ' . $duration . 'seconds.', 0);
            $buffer['Command'] = 'SetZoneTemperatureTimer';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID, 'power' => $power, 'temperature' => $temperature, 'durationInSeconds' => $duration];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            $result = json_decode($this->SendDataToParent($data), true);
            $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        }
        $this->UpdateZoneState();
    }

    public function UpdateZoneState(): void
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
            $mode = 1; #automatic
            if (array_key_exists('overlayType', $result)) {
                if ($result['overlayType'] == 'MANUAL') {
                    $mode = 0;
                }
            }
            $this->SetValue('Mode', $mode);
            //Setpoint temperature
            if (array_key_exists('setting', $result)) {
                if (array_key_exists('temperature', $result['setting'])) {
                    $temperatureSettings = $result['setting']['temperature'];
                    if (is_array($temperatureSettings)) {
                        if (array_key_exists('celsius', $temperatureSettings)) {
                            $temperature = floatval($temperatureSettings['celsius']);
                            $this->SetValue('SetPointTemperature', $temperature);
                        }
                    }
                }
            }
            //ToDo: termination for heating timer?
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
        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function RegisterProperties()
    {
        $this->RegisterPropertyString('HomeID', '');
        $this->RegisterPropertyString('HomeName', '');
        $this->RegisterPropertyString('ZoneID', '');
        $this->RegisterPropertyString('ZoneName', '');
        $this->RegisterPropertyString('ZoneType', '');
        $this->RegisterPropertyInteger('UpdateInterval', 0);
    }

    private function CreateProfiles()
    {
        //Mode
        $profile = 'TADO.' . $this->InstanceID . '.Mode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Manual'), 'Execute', -1);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Automatic'), 'Temperature', 0x00FF00);
        //Set point temperature
        $profile = 'TADO.' . $this->InstanceID . '.SetPointTemperature';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 2);
        }
        IPS_SetVariableProfileIcon($profile, 'Temperature');
        IPS_SetVariableProfileValues($profile, 0, 25, 0);
        IPS_SetVariableProfileDigits($profile, 1);
        $color = -1;
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', 0xA9B8C4);
        for ($i = 5; $i <= 25; $i += 0.5) {
            //light green
            if ($i >= 5 && $i < 8) {
                $color = 0x78AC99;
            }
            if ($i >= 5 && $i < 11) {
                $color = 0x7AB599;
            }
            if ($i >= 11 && $i < 15) {
                $color = 0x7EC197;
            }
            if ($i >= 15 && $i < 19) {
                $color = 0x82C995;
            }
            //yellow
            if ($i >= 19 && $i < 21) {
                $color = 0xF9D76A;
            }
            if ($i >= 21 && $i < 24) {
                $color = 0xF5B863;
            }
            //orange
            if ($i >= 24) {
                $color = 0xF29D5B;
            }
            IPS_SetVariableProfileAssociation($profile, $i, $i . ' °', '', $color);
        }
        //Heating timer
        $profile = 'TADO.' . $this->InstanceID . '.HeatingTimer';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 2);
        }
        IPS_SetVariableProfileIcon($profile, 'Clock');
        IPS_SetVariableProfileValues($profile, 0, 12, 0);
        IPS_SetVariableProfileDigits($profile, 2);
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', 0xA9B8C4);
        $color = 0x0000FF;
        IPS_SetVariableProfileAssociation($profile, 0.25, '15 min', '', $color);
        IPS_SetVariableProfileAssociation($profile, 0.5, '30  min', '', $color);
        IPS_SetVariableProfileAssociation($profile, 0.75, '45 min', '', $color);
        $color = 0x00FF00;
        for ($i = 1; $i <= 12; $i += 0.5) {
            IPS_SetVariableProfileAssociation($profile, $i, $i . ' h', '', $color);
        }
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['Mode', 'SetPointTemperature', 'HeatingTimer'];
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
        $this->RegisterVariableBoolean('Mode', $this->Translate('Mode'), $profile, 10);
        $this->EnableAction('Mode');
        //Set point temperature
        $profile = 'TADO.' . $this->InstanceID . '.SetPointTemperature';
        $this->RegisterVariableFloat('SetPointTemperature', $this->Translate('Setpoint temperature'), $profile, 20);
        $this->EnableAction('SetPointTemperature');
        //Heating timer
        $profile = 'TADO.' . $this->InstanceID . '.HeatingTimer';
        $this->RegisterVariableFloat('HeatingTimer', 'Timer', $profile, 30);
        $this->EnableAction('HeatingTimer');
        //Room temperature
        $this->RegisterVariableFloat('RoomTemperature', $this->Translate('Room temperature'), '~Temperature', 40);
        //Humidity
        $this->RegisterVariableFloat('AirHumidity', $this->Translate('Air humidity'), '~Humidity.F', 50);
    }

    private function RegisterTimers()
    {
        $this->RegisterTimer('Update', 0, 'TADO_UpdateZoneState(' . $this->InstanceID . ');');
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