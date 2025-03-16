<?php

/** @noinspection PhpRedundantMethodOverrideInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpMissingReturnTypeInspection */
/** @noinspection PhpUnused */

/*
 * @module      Tado Splitter
 *
 * @prefix      TADO
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020 - 2025
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

    //Constants
    private const LIBRARY_GUID = '{2C88856B-7D25-7502-1594-11F588E2C685}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyBoolean('Active', true);
        $this->RegisterPropertyInteger('Timeout', 5000);
        //Legacy properties, not used anymore.
        $this->RegisterPropertyString('UserName', '');
        $this->RegisterPropertyString('Password', '');

        //Legacy buffer, not used anymore. Transferred to attributes for new device code grand flow!
        $this->SetBuffer('ClientSecret', json_encode(['ClientSecret' => '']));
        $this->SetBuffer('AccessToken', json_encode(['AccessToken' => '', 'Expires' => '']));
        $this->SetBuffer('RefreshToken', json_encode(['RefreshToken' => '']));
        $this->SetBuffer('Scope', json_encode(['Scope' => '']));

        //Attributes
        //Device code grant flow
        $this->RegisterAttributeString('DeviceCode', '');
        $this->RegisterAttributeInteger('DeviceCodeExpires', 0);
        $this->RegisterAttributeString('DeviceCodeValidUntil', '');
        $this->RegisterAttributeString('VerificationUri', '');
        $this->RegisterAttributeString('AccessToken', '');
        $this->RegisterAttributeInteger('AccessTokenExpires', 0);
        $this->RegisterAttributeString('AccessTokenValidUntil', '');
        $this->RegisterAttributeString('RefreshToken', '');
        //Legacy attributes, not used anywhere.
        $this->RegisterAttributeString('Setup', '');
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
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $formData['elements'][1]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date']);
        $formData['actions'][0]['items'][0]['caption'] = $this->ReadAttributeString('AccessToken') ? 'Access Token: ' . substr($this->ReadAttributeString('AccessToken'), 0, 16) . ' ...' : 'Access Token: ' . $this->Translate('not available') . '!';
        $formData['actions'][0]['items'][1]['caption'] = $this->Translate('Valid until') . ': ' . $this->ReadAttributeString('AccessTokenValidUntil');
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

    public function ShowAttributes(): void
    {
        echo 'DeviceCode: ' . substr($this->ReadAttributeString('DeviceCode'), 0, 16) . ' ...' . "\n";
        echo 'DeviceCodeExpires: ' . $this->ReadAttributeInteger('DeviceCodeExpires') . "\n";
        echo 'DeviceCodeValidUntil: ' . $this->ReadAttributeString('DeviceCodeValidUntil') . "\n";
        echo 'VerificationUri: ' . $this->ReadAttributeString('VerificationUri') . "\n";
        echo 'AccessToken: ' . substr($this->ReadAttributeString('AccessToken'), 0, 16) . ' ...' . "\n";
        echo 'AccessTokenExpires: ' . $this->ReadAttributeInteger('AccessTokenExpires') . "\n";
        echo 'AccessTokenValidUntil: ' . $this->ReadAttributeString('AccessTokenValidUntil') . "\n";
        echo 'RefreshToken: ' . substr($this->ReadAttributeString('RefreshToken'), 0, 16) . ' ...' . "\n";
    }

    public function DeleteTokens(): void
    {
        $this->WriteAttributeString('AccessToken', '');
        $this->WriteAttributeInteger('AccessTokenExpires', 0);
        $this->WriteAttributeString('AccessTokenValidUntil', '');
        $this->WriteAttributeString('RefreshToken', '');
        $this->UpdateFormField('AccessToken', 'caption', 'Access Token: ' . $this->Translate('not available') . '!');
        $this->UpdateFormField('TokenValidUntil', 'caption', $this->Translate('Valid until') . ': ');
        $this->SetStatus(201);
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): void
    {
        $status = 102;

        //Check existing access token
        $accessToken = $this->ReadAttributeString('AccessToken');
        if ($accessToken == '') {
            $status = 201;
        }

        if (!$this->ReadPropertyBoolean('Active')) {
            $status = 104;
            $this->SendDebug($this->Translate('Instance Configuration'), $this->Translate('Instance is inactive!'), 0);
        } else {
            if ($accessToken == '') {
                $this->SendDebug($this->Translate('Instance Configuration'), $this->Translate('Please start the registration!'), 0);
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . $this->Translate('Please start the registration!'), KL_WARNING);
            }
        }

        $this->SetStatus($status);
    }
}