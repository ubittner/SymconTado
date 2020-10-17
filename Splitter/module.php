<?php

/*
 * @module      Tado Splitter
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
 *              Tado Splitter
 *              {31C59151-0182-07DB-4D0D-7EDA0668186F}
 */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';
include_once __DIR__ . '/helper/autoload.php';

class TadoSplitter extends IPSModule
{
    //Helper
    use tadoAPI;
    use webOAuth;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->RegisterAttributes();
        $this->CreateBuffers();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
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
        $this->CheckInstance();
        $this->ValidateConfiguration();
        $this->GetBearerToken();
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

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'GetAccount':
                $response = $this->GetAccount();
                break;

            case 'GetZones':
                $response = $this->GetZones($data->Buffer->Params);
                break;

            case 'GetZoneState':
                $params = (array) $data->Buffer->Params;
                $response = $this->GetZoneState($params['homeID'], $params['zoneID']);
                break;

            case 'GetDevices':
                $response = $this->GetDevices($data->Buffer->Params);
                break;

            case 'TurnZoneManualHeatingOff':
                $params = (array) $data->Buffer->Params;
                $this->TurnZoneManualHeatingOff($params['homeID'], $params['zoneID']);
                $response = true;
                break;

            case 'SetZoneTemperatureNoTimer':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetZoneTemperatureNoTimer($params['homeID'], $params['zoneID'], $params['power'], $params['temperature']);
                break;

            case 'SetZoneTemperatureTimer':
                $params = (array) $data->Buffer->Params;
                $this->SendDebug(__FUNCTION__, 'HomeID: ' . $params['homeID'], 0);
                $this->SendDebug(__FUNCTION__, 'ZoneID: ' . $params['zoneID'], 0);
                $this->SendDebug(__FUNCTION__, 'Temp: ' . $params['temperature'], 0);
                $this->SendDebug(__FUNCTION__, 'Duration: ' . $params['durationInSeconds'], 0);
                $response = $this->SetZoneTemperatureTimer($params['homeID'], $params['zoneID'], $params['power'], $params['temperature'], $params['durationInSeconds']);
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                $response = '';
        }
        $this->SendDebug(__FUNCTION__, $response, 0);
        return $response;
    }

    public function ResetAttributes()
    {
        $this->WriteAttributeString('AccountInformation', '');
        $this->WriteAttributeString('HomeInformation', '');
        $this->WriteAttributeString('ZoneInformation', '');
        $this->WriteAttributeString('Devices', '');
        $this->WriteAttributeString('ZoneState', '');
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function RegisterProperties(): void
    {
        $this->RegisterPropertyBoolean('Active', false);
        $this->RegisterPropertyString('UserName', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyInteger('Timeout', 5000);
    }

    private function RegisterAttributes(): void
    {
        $this->RegisterAttributeString('Setup', '');
    }

    private function CreateBuffers(): void
    {
        $this->SetBuffer('ClientSecret', json_encode(['ClientSecret' => '']));
        $this->SetBuffer('AccessToken', json_encode(['AccessToken' => '', 'Expires' => '']));
        $this->SetBuffer('RefreshToken', json_encode(['RefreshToken' => '']));
        $this->SetBuffer('Scope', json_encode(['Scope' => '']));
    }

    private function CheckInstance(): bool
    {
        $result = $this->ReadPropertyBoolean('Active');
        $status = 102;
        if (!$result) {
            $status = 104;
            $this->SendDebug(__FUNCTION__, 'Instance is inactive!', 0);
        }
        $this->SetStatus($status);
        IPS_SetDisabled($this->InstanceID, !$result);
        return $result;
    }

    private function ValidateConfiguration()
    {
        $status = 102;
        // Check password
        if (empty($this->ReadPropertyString('Password'))) {
            $status = 203;
        }
        // Check user name
        if (empty($this->ReadPropertyString('UserName'))) {
            $status = 202;
        }
        // Check user name and password
        if (empty($this->ReadPropertyString('Password')) && empty($this->ReadPropertyString('UserName'))) {
            $status = 201;
        }
        $this->SetStatus($status);
    }
}