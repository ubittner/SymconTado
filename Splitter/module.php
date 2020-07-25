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
 * @see         https://github.com/ubittner/SymconBoseSwitchboard/Splitter
 *
 * @guids       Library
 *              {2C88856B-7D25-7502-1594-11F588E2C685}
 *
 *              Tado Splitter
 *              {31C59151-0182-07DB-4D0D-7EDA0668186F}
 */

declare(strict_types=1);

// Include
include_once __DIR__ . '/../libs/helper/autoload.php';
include_once __DIR__ . '/helper/autoload.php';

class TadoSplitter extends IPSModule
{
    // Helper
    use libs_helper_getModuleInfo;
    use tadoAPI;
    use webOAuth;

    public function Create()
    {
        // Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->RegisterAttributes();
        $this->RegisterTimers();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        // Never delete this line!
        parent::ApplyChanges();
        // Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->ResetBuffers();
        $this->GetClientSecret();
        $this->FetchAccessToken();
        $this->SetTimers();
        $this->ResetAttributes();
        $this->ValidateConfiguration();

        $this->GetAccountInformation();
        $this->GetHomeInformation();
        $this->GetZoneInformation();
        $this->GetDevices();
        //$this->GetAccountData();
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
        $moduleInfo = $this->GetModuleInfo(TADO_SPLITTER_GUID);
        $formData['elements'][1]['items'][1]['caption'] = $this->Translate("Instance ID:\t\t") . $this->InstanceID;
        $formData['elements'][1]['items'][2]['caption'] = $this->Translate("Module:\t\t\t") . $moduleInfo['name'];
        $formData['elements'][1]['items'][3]['caption'] = "Version:\t\t\t" . $moduleInfo['version'];
        $formData['elements'][1]['items'][4]['caption'] = $this->Translate("Date:\t\t\t") . $moduleInfo['date'];
        $formData['elements'][1]['items'][5]['caption'] = $this->Translate("Time:\t\t\t") . $moduleInfo['time'];
        $formData['elements'][1]['items'][6]['caption'] = $this->Translate("Developer:\t\t") . $moduleInfo['developer'];
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

    public function ResetBuffers()
    {
        $this->SetBuffer('ClientSecret', '');
        $this->SetBuffer('AccessToken', '');
        $this->SetBuffer('RefreshToken', '');
        $this->SetBuffer('Scope', '');

    }

    /**
     * Resets the attributes.
     */
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
        $this->RegisterPropertyString('Note', '');
        $this->RegisterPropertyString('UserName', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyInteger('Timeout', 5000);
        $this->RegisterPropertyInteger('UpdateInterval', 60);
        $this->RegisterPropertyString('Token', '');
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
        if ($status == 102) {
            // Update data
            $updateInterval = $this->ReadPropertyInteger('UpdateInterval');
            $this->SetTimerInterval('Update', $updateInterval * 1000);
        }
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
}