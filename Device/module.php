<?php

/** @noinspection PhpUnused */

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
 *             	{07B0E642-8AF4-0E49-5A5E-DA70531CCF7F}
 */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';

class TadoDevice extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->RegisterVariables();
        $this->RegisterTimers();
        //Connect to splitter
        $this->ConnectParent(TADO_SPLITTER_GUID);
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
        //Set update timer
        $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        $this->SetTimerInterval('UpdateDeviceState', $milliseconds);
        //Update state
        $this->UpdateDeviceState();
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
        //Received data from splitter, not used at the moment
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    public function UpdateDeviceState(): void
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            return;
        }
        $data = [];
        $buffer = [];
        $homeID = $this->ReadPropertyString('HomeID');
        if (empty($homeID)) {
            $this->SendDebug(__FUNCTION__, 'Error, no home id assigned!', 0);
            return;
        }
        $serialNumber = $this->ReadPropertyString('SerialNumber');
        if (empty($serialNumber)) {
            $this->SendDebug(__FUNCTION__, 'Error, no serial number assigned!', 0);
            return;
        }
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetDevices';
        $buffer['Params'] = (int) $homeID;
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $devices = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, json_encode($devices), 0);
        if (!empty($devices)) {
            foreach ($devices as $device) {
                if (array_key_exists('serialNo', $device)) {
                    $deviceSerialNumber = $device['serialNo'];
                    if ($deviceSerialNumber == $serialNumber) {
                        if (array_key_exists('batteryState', $device)) {
                            $batteryState = $device['batteryState'];
                            $state = false;
                            if ($batteryState != 'NORMAL') {
                                $state = true;
                            }
                            $this->SetValue('BatteryState', $state);
                        }
                    }
                }
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
        $this->RegisterPropertyString('DeviceType', '');
        $this->RegisterPropertyString('DeviceName', '');
        $this->RegisterPropertyString('SerialNumber', '');
        $this->RegisterPropertyString('ShortSerialNumber', '');
        $this->RegisterPropertyString('HomeID', '');
        $this->RegisterPropertyInteger('UpdateInterval', 0);
    }

    private function RegisterVariables(): void
    {
        //Battery state
        $this->RegisterVariableBoolean('BatteryState', $this->Translate('Battery State'), '~Battery', 10);
    }

    private function RegisterTimers(): void
    {
        $this->RegisterTimer('UpdateDeviceState', 0, 'TADO_UpdateDeviceState(' . $this->InstanceID . ');');
    }
}