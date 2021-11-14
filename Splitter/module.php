<?php

/** @noinspection PhpRedundantMethodOverrideInspection */
/** @noinspection PhpUnused */

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
        $this->ValidateConfiguration();
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

            case 'GetHome':
                $response = $this->GetHome($data->Buffer->Params);
                break;

            case 'GetHomeState':
                $response = $this->GetHomeState($data->Buffer->Params);
                break;

            case 'GetDevices':
                $response = $this->GetDevices($data->Buffer->Params);
                break;

            case 'SetPresenceLock':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetPresenceLock($params['homeID'], $params['awayMode']);
                break;

            case 'GetZones':
                $response = $this->GetZones($data->Buffer->Params);
                break;

            case 'GetZoneState':
                $params = (array) $data->Buffer->Params;
                $response = $this->GetZoneState($params['homeID'], $params['zoneID']);
                break;

            case 'StopManualMode':
                $params = (array) $data->Buffer->Params;
                $this->StopManualMode($params['homeID'], $params['zoneID']);
                $response = true;
                break;

            case 'SetHeatingZoneTemperature':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetHeatingZoneTemperature($params['homeID'], $params['zoneID'], $params['power'], $params['temperature']);
                break;

            case 'SetHeatingZoneTemperatureTimer':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetHeatingZoneTemperatureTimer($params['homeID'], $params['zoneID'], $params['power'], $params['temperature'], $params['durationInSeconds']);
                break;

            case 'SetHeatingZoneTemperatureTimerNextTimeBlock':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetHeatingZoneTemperatureTimerNextTimeBlock($params['homeID'], $params['zoneID'], $params['power'], $params['temperature']);
                break;

            case 'SetCoolingZone':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetCoolingZone($params['homeID'], $params['zoneID'], $params['overlay']);
                $this->SendDebug(__FUNCTION__, 'Overlay: ' . $params['overlay'], 0);
                break;

            case 'SetCoolingZoneTemperature': # Deprecated !
                $params = (array) $data->Buffer->Params;
                $response = $this->SetCoolingZoneTemperature($params['homeID'], $params['zoneID'], $params['power'], $params['temperature']);
                break;

            case 'SetCoolingZoneTemperatureEx': # Deprecated !
                $params = (array) $data->Buffer->Params;
                $response = $this->SetCoolingZoneTemperatureEx($params['homeID'], $params['zoneID'], $params['power'], $params['mode'], $params['temperature'], $params['fanSpeed'], $params['swing']);
                break;

            case 'SetCoolingZoneTemperatureTimer': # Deprecated !
                $params = (array) $data->Buffer->Params;
                $response = $this->SetCoolingZoneTemperatureTimer($params['homeID'], $params['zoneID'], $params['power'], $params['temperature'], $params['durationInSeconds']);
                break;

            case 'SetCoolingZoneTemperatureTimerEx': # Deprecated !
                $params = (array) $data->Buffer->Params;
                $response = $this->SetCoolingZoneTemperatureTimerEx($params['homeID'], $params['zoneID'], $params['power'], $params['mode'], $params['temperature'], $params['durationInSeconds'], $params['fanSpeed'], $params['swing']);
                break;

            case 'SetCoolingZoneTemperatureTimerNextTimeBlock': # Deprecated !
                $params = (array) $data->Buffer->Params;
                $response = $this->SetCoolingZoneTemperatureTimerNextTimeBlock($params['homeID'], $params['zoneID'], $params['power'], $params['temperature']);
                break;

            case 'SetCoolingZoneTemperatureTimerNextTimeBlockEx': # Deprecated !
                $params = (array) $data->Buffer->Params;
                $response = $this->SetCoolingZoneTemperatureTimerNextTimeBlockEx($params['homeID'], $params['zoneID'], $params['power'], $params['mode'], $params['temperature'], $params['fanSpeed'], $params['swing']);
                break;

            case 'SendDataToTado':
                $params = (array) $data->Buffer->Params;
                $response = $this->SendDataToTado($params['endpoint'], $params['customRequest'], $params['postfields']);
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                $response = '';
        }
        $this->SendDebug(__FUNCTION__, $response, 0);
        return $response;
    }

    #################### Private

    private function KernelReady(): void
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

    private function ValidateConfiguration(): void
    {
        $this->SendDebug(__FUNCTION__, 'Validate configuration', 0);
        $status = 102;
        $userName = $this->ReadPropertyString('UserName');
        $password = $this->ReadPropertyString('Password');
        //Check password
        if (empty($password)) {
            $status = 203;
        }
        //Check user name
        if (empty($userName)) {
            $status = 202;
        }
        //Check user name and password
        if (empty($userName) && empty($password)) {
            $status = 201;
        }
        if (!empty($userName) && !empty($password)) {
            $token = $this->GetBearerToken();
            if (is_array(json_decode($token, true))) {
                if (array_key_exists('error', json_decode($token, true))) {
                    $status = 204;
                }
            }
        }
        $active = $this->CheckInstance();
        if (!$active) {
            $status = 104;
        }
        $this->SetStatus($status);
    }

    private function CheckInstance(): bool
    {
        $result = $this->ReadPropertyBoolean('Active');
        if (!$result) {
            $this->SendDebug(__FUNCTION__, 'Instance is inactive!', 0);
        }
        return $result;
    }
}