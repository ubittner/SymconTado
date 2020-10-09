<?php

/*
 * @module      Tado Configurator
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
 *              Tado Configurator
 *             	{55862B9A-A9E9-2A1D-2EA9-C195C18A42EA}
 */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';

class TadoConfigurator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Register properties
        $this->RegisterPropertyInteger('CategoryID', 0);
        //Connect to parent (Tado Splitter)
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
        //Check runlevel
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

    public function GetConfigurationForm(): string
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $values = [];
        $devices = json_decode($this->GetDevices(), true);
        if (!empty($devices)) {
            $this->SendDebug(__FUNCTION__, print_r($devices, true), 0);
            $location = $this->GetCategoryPath($this->ReadPropertyInteger(('CategoryID')));
            foreach ($devices as $key => $device) {
                $serialNumber = (string) $device['shortSerialNo'];
                $instanceID = $this->GetDeviceInstanceIDBySerialNumber($serialNumber);
                if ($device['type'] === 'HEATING') {
                    $values[] = [
                        'DeviceType'   => $device['deviceType'],
                        'SerialNumber' => $device['shortSerialNo'],
                        'HomeID'       => $device['homeId'],
                        'ZoneID'       => $device['zoneId'],
                        'name'         => $device['zoneName'],
                        'Type'         => $device['type'],
                        'instanceID'   => $instanceID,
                        'create'       => [
                            'moduleID'      => TADO_DEVICE_GUID,
                            'configuration' => [
                                'DeviceType'        => (string) $device['deviceType'],
                                'ShortSerialNumber' => (string) $device['shortSerialNo'],
                                'HomeID'            => (string) $device['homeId'],
                                'ZoneID'            => (string) $device['zoneId'],
                                'ZoneName'          => (string) $device['zoneName'],
                                'Type'              => (string) $device['type'],
                            ],
                            'location' => $location
                        ]
                    ];
                }
                if ($device['type'] != 'HEATING') {
                    $values[] = [
                        'DeviceType'   => $device['deviceType'],
                        'SerialNumber' => $device['shortSerialNo'],
                        'HomeID'       => $device['homeId'],
                        'ZoneID'       => $device['zoneId'],
                        'ZoneName'     => $device['zoneName'],
                        'Type'         => $device['type'],
                        'instanceID'   => $instanceID
                    ];
                }
            }
        }
        $formData['actions'][0]['values'] = $values;
        return json_encode($formData);
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function GetCategoryPath(int $CategoryID)
    {
        if ($CategoryID === 0) {
            return [];
        }
        $path[] = IPS_GetName($CategoryID);
        $parentID = IPS_GetObject($CategoryID)['ParentID'];
        while ($parentID > 0) {
            $path[] = IPS_GetName($parentID);
            $parentID = IPS_GetObject($parentID)['ParentID'];
        }
        return array_reverse($path);
    }

    private function GetDevices()
    {
        if (!$this->HasActiveParent()) {
            return '';
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetDevices';
        $buffer['Params'] = '';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        return $this->SendDataToParent($data);
    }

    private function GetDeviceInstanceIDBySerialNumber(string $SerialNumber)
    {
        $id = 0;
        $instances = IPS_GetInstanceListByModuleID(TADO_DEVICE_GUID);
        foreach ($instances as $instance) {
            if (IPS_GetProperty($instance, 'ShortSerialNumber') == $SerialNumber) {
                $id = $instance;
            }
        }
        return $id;
    }
}