<?php

/** @noinspection PhpUnused */

/*
 * @module      Tado Home
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
 *              Tado Home
 *             	{69F3B4F8-3A8E-BB23-FEFD-66BB7846CAEF}
 */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';

class TadoHome extends IPSModule
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
        $this->SetTimerInterval('UpdateHomeState', $milliseconds);
        //Update state
        $this->UpdateHomeState();
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
            case 'GeofencingMode':
                $this->SetGeofencingMode($Value);
                break;

        }
    }

    public function SetGeofencingMode(int $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'The method was executed with parameter $Mode: ' . json_encode($Mode) . ' (' . microtime(true) . ')', 0);
        //Check parent
        if (!$this->CheckParent()) {
            return;
        }
        //Check Home ID
        if (!$this->CheckHomeID()) {
            return;
        }
        $homeID = intval($this->ReadPropertyString('HomeID'));
        $this->SetValue('GeofencingMode', $Mode);
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'SetPresenceLock';
        $buffer['Params'] = ['homeID' => $homeID, 'awayMode' => $Mode];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        $this->UpdateHomeState();
    }

    public function UpdateHomeState(): void
    {
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        $homeID = $this->ReadPropertyString('HomeID');
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetHomeState';
        $buffer['Params'] = (int) $homeID;
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        if (!empty($result)) {
            if (array_key_exists('presence', $result)) {
                $presence = $result['presence'];
            }
            if (array_key_exists('presenceLocked', $result)) {
                $presenceLocked = $result['presenceLocked'];
            }
            if (isset($presence) && isset($presenceLocked)) {
                //Auto
                $mode = 0;
                if ($presenceLocked == 1) {
                    if ($presence == 'HOME') {
                        $mode = 1;
                    }
                    if ($presence == 'AWAY') {
                        $mode = 2;
                    }
                }
                $this->SetValue('GeofencingMode', $mode);
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
        $this->RegisterPropertyString('ContactName', '');
        $this->RegisterPropertyString('ContactEMail', '');
        $this->RegisterPropertyString('ContactPhone', '');
        $this->RegisterPropertyString('AddressLine1', '');
        $this->RegisterPropertyString('AddressLine2', '');
        $this->RegisterPropertyString('ZipCode', '');
        $this->RegisterPropertyString('City', '');
        $this->RegisterPropertyString('State', '');
        $this->RegisterPropertyString('Country', '');
        $this->RegisterPropertyInteger('UpdateInterval', 0);
    }

    private function CreateProfiles(): void
    {
        //Mode
        $profile = 'TADO.' . $this->InstanceID . '.GeofencingMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Auto', 'Information', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'Home', 'Presence', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 2, 'Away', 'Motion', 0x0000FF);
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['GeofencingMode'];
        foreach ($profiles as $profile) {
            $profileName = 'TADO.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    private function RegisterVariables(): void
    {
        //Away mode
        $profile = 'TADO.' . $this->InstanceID . '.GeofencingMode';
        $this->RegisterVariableInteger('GeofencingMode', $this->Translate('Geofencing Mode'), $profile, 10);
        $this->EnableAction('GeofencingMode');
    }

    private function RegisterTimers(): void
    {
        $this->RegisterTimer('UpdateHomeState', 0, 'TADO_UpdateHomeState(' . $this->InstanceID . ');');
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
}