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
        $this->InitializeBuffers();
        $this->RegisterAttributes();
        $this->RegisterTimers();
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
        $this->SetTimers();
        $this->CheckInstanceActive();
        $this->ValidateConfiguration();
        $this->GetBearerToken();

        /*




        $this->GetAccountInformation();
        $this->GetHomeInformation();
        $this->GetZoneInformation();
        $this->GetDevices();
        //$this->GetAccountData();
         */
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, 'SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;
        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        //Buffer
        $clientSecret = json_decode($this->GetBuffer('ClientSecret'))->ClientSecret;
        $formData['actions'][1]['items'][0]['caption'] = "ClientSecret:\t\t\t\t" . $clientSecret;
        $state = '';
        $accessToken = json_decode($this->GetBuffer('AccessToken'))->AccessToken;
        if (!empty($accessToken)) {
            $state = 'OK';
        }
        $formData['actions'][1]['items'][1]['caption'] = "AccessToken:\t\t\t" . $state;
        $accessTokenExpires = json_decode($this->GetBuffer('AccessToken'))->Expires;
        if (!empty($accessTokenExpires)) {
            $accessTokenExpires = date('d.m.y H:i:s', $accessTokenExpires);
        }
        $formData['actions'][1]['items'][2]['caption'] = "AccessToken Expires:\t\t" . $accessTokenExpires;
        $refreshToken = json_decode($this->GetBuffer('RefreshToken'))->RefreshToken;
        if (!empty($refreshToken)) {
            $state = 'OK';
        }
        $formData['actions'][1]['items'][3]['caption'] = "RefreshToken:\t\t\t" . $state;
        $scope = json_decode($this->GetBuffer('Scope'))->Scope;
        $formData['actions'][1]['items'][4]['caption'] = "Scope:\t\t\t\t\t" . $scope;
        return json_encode($formData);
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'GetDevices':
                $response = json_encode($this->GetDevices());
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                $response = '';
        }
        $this->SendDebug(__FUNCTION__, $response, 0);
        return $response;
    }

    public function ShowBuffer(string $BufferName): string
    {
        $result = '';
        switch ($BufferName) {
            case 'ClientSecret':
                $name = 'ClientSecret';
                $result = $this->GetBuffer($name);
                break;

            case 'AccessToken':
                $name = 'AccessToken';
                $result = $this->GetBuffer($name);
                break;

            case 'RefreshToken':
                $name = 'RefreshToken';
                $result = $this->GetBuffer($name);
                break;

            case 'Scope':
                $name = 'Scope';
                $result = $this->GetBuffer($name);
                break;
        }
        if (!empty($result)) {
            $this->SendDebug(__FUNCTION__, $result, 0);
        }
        return $result;
    }

    /**
     * Shows the attribute according to the attribute name.
     *
     * @param string $AttributeName
     *
     * @return string
     * Returns a string of information.
     */
    public function ShowAttribute(string $AttributeName): string
    {
        switch ($AttributeName) {
            case 'AccountInformation':
                $result = $this->ReadAttributeString('AccountInformation');
                break;

            case 'HomeInformation':
                $result = $this->ReadAttributeString('HomeInformation');
                break;

            case 'ZoneInformation':
                $result = $this->ReadAttributeString('ZoneInformation');
                break;

            case 'Devices':
                $result = $this->ReadAttributeString('Devices');
                break;

            case 'ZoneState':
                $result = $this->ReadAttributeString('ZoneState');
                break;

            default:
                $result = '';
        }
        return $result;
    }

    public function InitializeBuffers()
    {
        $this->SetBuffer('ClientSecret', json_encode(['ClientSecret' => '']));
        $this->SetBuffer('AccessToken', json_encode(['AccessToken' => '', 'Expires' => '']));
        $this->SetBuffer('RefreshToken', json_encode(['RefreshToken' => '']));
        $this->SetBuffer('Scope', json_encode(['Scope' => '']));
        $this->UpdateParameters();
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
        $this->RegisterPropertyBoolean('Active', true);
        $this->RegisterPropertyString('UserName', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyInteger('Timeout', 5000);
        $this->RegisterPropertyInteger('UpdateInterval', 0);
    }

    private function RegisterAttributes(): void
    {
        $this->RegisterAttributeString('AccountInformation', '');
        $this->RegisterAttributeString('HomeInformation', '');
        $this->RegisterAttributeString('ZoneInformation', '');
        $this->RegisterAttributeString('Devices', '');
        $this->RegisterAttributeString('ZoneState', '');
    }

    private function RegisterTimers()
    {
        $this->RegisterTimer('Update', 0, 'TADO_GetAccountData(' . $this->InstanceID . ');');
    }

    private function SetTimers()
    {
        $status = IPS_GetInstance($this->InstanceID)['InstanceStatus'];
        $updateInterval = $this->ReadPropertyInteger('UpdateInterval');
        if ($status == 102 || $updateInterval == 0) {
            $this->SetTimerInterval('Update', $updateInterval * 1000);
        }
    }

    private function UpdateParameters(): void
    {
        //Buffer client secret
        $clientSecret = json_decode($this->GetBuffer('ClientSecret'))->ClientSecret;
        $caption = 'ClientSecret: ' . $clientSecret;
        $this->UpdateFormField('BufferClientSecret', 'caption', $caption);
        //Buffer access token
        $state = '';
        $accessToken = json_decode($this->GetBuffer('AccessToken'))->AccessToken;
        if (!empty($accessToken)) {
            $state = 'OK';
        }
        $caption = 'AccessToken: ' . $state;
        $this->UpdateFormField('BufferAccessToken', 'caption', $caption);
        //Buffer access token expires
        $accessTokenExpires = json_decode($this->GetBuffer('AccessToken'))->Expires;
        if (!empty($accessTokenExpires)) {
            $accessTokenExpires = date('d.m.y H:i:s', $accessTokenExpires);
        }
        $caption = 'AccessToken Expires: ' . $accessTokenExpires;
        $this->UpdateFormField('BufferAccessTokenExpires', 'caption', $caption);
        //Buffer refresh token
        $refreshToken = json_decode($this->GetBuffer('RefreshToken'))->RefreshToken;
        if (!empty($refreshToken)) {
            $state = 'OK';
        }
        $caption = 'RefreshToken: ' . $state;
        $this->UpdateFormField('BufferRefreshToken', 'caption', $caption);
        //Buffer scope
        $scope = json_decode($this->GetBuffer('Scope'))->Scope;
        $caption = 'Scope: ' . $scope;
        $this->UpdateFormField('BufferScope', 'caption', $caption);
    }

    private function ValidateConfiguration(): void
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

    private function CheckInstanceActive(): bool
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
}