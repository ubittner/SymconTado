<?php

/*
 * @module      Tado Device
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
 *              Tado Device
 *             	{F6D924F8-0CAB-2EB7-725D-2640B8F5556B}
 */

declare(strict_types=1);

// Include
include_once __DIR__ . '/../libs/constants.php';

class TadoDevice extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->CreateProfiles();
        $this->RegisterVariables();
        //Connect to Tado Splitter
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

    public function Send()
    {
        $this->SendDataToParent(json_encode(['DataID' => TADO_SPLITTER_DATA_GUID]));
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug(__FUNCTION__ . ' Start', 'Incomming data', 0);
        $this->SendDebug(__FUNCTION__ . ' String', $JSONString, 0);
        $data = json_decode(utf8_decode($JSONString));
        $dataID = $data->DataID;
        $this->SendDebug(__FUNCTION__ . ' DataID', json_encode($dataID), 0);
        $buffer = $data->Buffer;
        $this->SendDebug(__FUNCTION__ . ' Buffer', json_encode($buffer), 0);
        $this->SendDebug(__FUNCTION__ . ' End', 'Data received', 0);
        $method = $buffer->Method;
        if ($method == 'DataUpdate') {
            $data = $data->Buffer->Data;
            $this->SendDebug(__FUNCTION__, 'Buffer Data: ' . json_encode($data), 0);
            $this->UpdateData(json_encode($data));
        }
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Mode':
                echo 'Mode';
                //$this->ToggleMode($Value);
                break;

            case 'SetPointTemperature':
                echo 'SetPointTemperature';
                //$this->ToggleSetPointTemperature($Value);
                break;

        }
    }

    private function UpdateData(string $Data): void
    {
        $zoneID = $this->ReadPropertyString('ZoneID');
        $zoneStates = json_decode($Data, true);
        if (!empty($zoneStates)) {
            foreach ($zoneStates as $ID => $state) {
                if ($ID == $zoneID) {
                    if (!empty($state)) {
                        // Mode
                        $mode = 1; #automatic
                        if (array_key_exists('overlayType', $state)) {
                            if ($state['overlayType'] == 'MANUAL') {
                                $mode = 0;
                            }
                        }
                        $this->SetValue('Mode', $mode);
                        //Setpoint temperature
                        if (array_key_exists('setting', $state)) {
                            if (array_key_exists('temperature', $state['setting'])) {
                                $setpointTemperature = $state['setting']['temperature']['celsius'];
                                $this->SetValue('SetPointTemperature', (float) $setpointTemperature);
                            }
                        }
                        if (array_key_exists('sensorDataPoints', $state)) {
                            //Inside temperature
                            if (array_key_exists('insideTemperature', $state['sensorDataPoints'])) {
                                $insideTemperature = $state['sensorDataPoints']['insideTemperature']['celsius'];
                                $this->SetValue('RoomTemperature', (float) $insideTemperature);
                            }
                            //Humidity
                            if (array_key_exists('humidity', $state['sensorDataPoints'])) {
                                $humidity = $state['sensorDataPoints']['humidity']['percentage'];
                                $this->SetValue('AirHumidity', (float) $humidity);
                            }
                        }
                    }
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
        $this->registerPropertyString('Note', '');
        $this->RegisterPropertyString('DeviceType', '');
        $this->RegisterPropertyString('ShortSerialNumber', '');
        $this->RegisterPropertyString('HomeID', '');
        $this->RegisterPropertyString('ZoneID', '');
        $this->RegisterPropertyString('ZoneName', '');
        $this->RegisterPropertyString('Type', '');
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
        IPS_SetVariableProfileValues($profile, 0, 25, 0.5);
        IPS_SetVariableProfileDigits($profile, 1);
        IPS_SetVariableProfileText($profile, '', ' °C');
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['Mode', 'SetPointTemperature'];
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
        //Room temperature
        $this->RegisterVariableFloat('RoomTemperature', $this->Translate('Room temperature'), '~Temperature', 30);
        //Humidity
        $this->RegisterVariableFloat('AirHumidity', $this->Translate('Air humidity'), '~Humidity.F', 40);
    }
}