<?php

/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';

class TadoDevice extends IPSModuleStrict
{
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->RegisterVariables();
        $this->RegisterTimers();
    }

    public function ApplyChanges(): void
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Register FM Connect message
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);

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
        if ($this->HasActiveParent()) {
            $this->UpdateDeviceState();
        }
    }

    public function GetCompatibleParents(): string
    {
        //Connect to a new or existing tado° Splitter instance
        return json_encode([
            'type'      => 'connect',
            'moduleIDs' => [
                TADO_SPLITTER_GUID
            ]
        ]);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case FM_CONNECT:
                $this->UpdateDeviceState();
                break;

        }
    }

    public function GetConfigurationForm(): string
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        return json_encode($formData);
    }

    public function ReceiveData($JSONString): string
    {
        //Receive data from splitter, not used at the moment
        $this->SendDebug(__FUNCTION__, 'Incoming data: ' . $JSONString, 0);
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, 'Buffer data:  ' . json_encode($data->Buffer), 0);
        return '';
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