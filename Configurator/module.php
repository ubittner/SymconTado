<?php

/** @noinspection PhpUnused */

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
        //Register properties
        $this->RegisterPropertyInteger('CategoryID', 0);
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
        $values = $this->GetTadoSetup();
        $formData['actions'][0]['values'] = $values;
        return json_encode($formData);
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function GetTadoSetup()
    {
        $values = [];
        if (!$this->HasActiveParent()) {
            return $values;
        }
        $values = [];
        $location = $this->GetCategoryPath($this->ReadPropertyInteger(('CategoryID')));
        //Homes
        $data = [];
        $buffer = [];
        $data['DataID'] = TADO_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetAccount';
        $buffer['Params'] = '';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $homes = $this->SendDataToParent($data);
        $this->SendDebug(__FUNCTION__, $homes, 0);
        $homes = json_decode($homes, true);
        if (!empty($homes)) {
            if (array_key_exists('homes', $homes)) {
                $homes = $homes['homes'];
                //Zones
                foreach ($homes as $home) {
                    $homeID = $home['id'];
                    $homeName = $home['name'];
                    $values[] = [
                        'id'                    => $homeID,
                        'name'                  => $homeName . ' (' . $this->Translate('Home') . ')',
                        'Identifier'            => $homeID,
                        'Type'                  => ''];
                    $data = [];
                    $buffer = [];
                    $data['DataID'] = TADO_SPLITTER_DATA_GUID;
                    $buffer['Command'] = 'GetZones';
                    $buffer['Params'] = $homeID;
                    $data['Buffer'] = $buffer;
                    $data = json_encode($data);
                    $zones = $this->SendDataToParent($data);
                    $this->SendDebug(__FUNCTION__, $zones, 0);
                    $zones = json_decode($zones, true);
                    if (!empty($zones)) {
                        foreach ($zones as $key => $zone) {
                            $zoneID = (int) $zone['id'];
                            $id = $homeID . $zoneID;
                            if ($zone['type'] == 'HEATING') {
                                $instanceID = $this->GetZoneInstanceID($zoneID, 0);
                                $values[] = [
                                    'id'                    => $id,
                                    'parent'                => $homeID,
                                    'name'                  => $zone['name'] . ' ' . $this->Translate('(Room)'),
                                    'Identifier'            => $homeID . '-' . $zone['id'],
                                    'Type'                  => $zone['type'],
                                    'instanceID'            => $instanceID,
                                    'create'                => [
                                        'moduleID'      => TADO_HEATING_GUID,
                                        'name'          => 'Tado ' . $zone['name'] . ' (' . $this->Translate('Heating') . ')',
                                        'configuration' => [
                                            'HomeID'                => (string) $homeID,
                                            'HomeName'              => (string) $homeName,
                                            'ZoneID'                => (string) $zone['id'],
                                            'ZoneName'              => (string) $zone['name'],
                                            'ZoneType'              => (string) $zone['type'],
                                        ],
                                        'location' => $location
                                    ]
                                ];
                            }
                            if ($zone['type'] == 'AIR_CONDITIONING') {
                                $instanceID = $this->GetZoneInstanceID($zoneID, 1);
                                $values[] = [
                                    'id'                    => $id,
                                    'parent'                => $homeID,
                                    'name'                  => $zone['name'] . ' ' . $this->Translate('(Room)'),
                                    'Identifier'            => $homeID . '-' . $zone['id'],
                                    'Type'                  => $zone['type'],
                                    'instanceID'            => $instanceID,
                                    'create'                => [
                                        'moduleID'      => TADO_HEATING_GUID, //ToDo: change to: TADO_COOLING_GUID,
                                        'name'          => 'Tado ' . $zone['name'] . ' (' . $this->Translate('Cooling') . ')',
                                        'configuration' => [
                                            'HomeID'                => (string) $homeID,
                                            'HomeName'              => (string) $homeName,
                                            'ZoneID'                => (string) $zone['id'],
                                            'ZoneName'              => (string) $zone['name'],
                                            'ZoneType'              => (string) $zone['type'],
                                        ],
                                        'location' => $location
                                    ]
                                ];
                            }
                            if (array_key_exists('devices', $zone)) {
                                $devices = $zone['devices'];
                                foreach ($devices as $index => $device) {
                                    $this->SendDebug(__FUNCTION__, 'DeviceType : ' . $device['deviceType'], 0);
                                    switch ($device['deviceType']) {
                                        case 'RU01':
                                            $deviceName = $this->Translate('Smart Thermostat');
                                            $deviceType = 'RU01';
                                            break;

                                        case 'VA02':
                                            $deviceName = $this->Translate('Smart Radiator-Thermostat');
                                            $deviceType = 'VA02';
                                            break;

                                        case 'WR02':
                                            $deviceName = $this->Translate('Smart Air Conditioning Control');
                                            $deviceType = 'WR02';
                                            break;

                                        default:
                                            $deviceName = $device['deviceType'];
                                            $deviceType = $device['deviceType'];

                                    }
                                    $deviceSerialNumber = $device['serialNo'];
                                    $this->SendDebug(__FUNCTION__, 'DeviceSerialNumber: ' . $deviceSerialNumber, 0);
                                    $deviceInstanceID = $this->GetDeviceInstanceID($deviceSerialNumber);
                                    $this->SendDebug(__FUNCTION__, 'DeviceInstanceID: ' . $deviceInstanceID, 0);
                                    $values[] = [
                                        'parent'                => $id,
                                        'name'                  => $deviceName . ' ' . $this->Translate('(Device)'),
                                        'Identifier'            => $device['serialNo'],
                                        'Type'                  => $deviceType,
                                        'instanceID'            => $deviceInstanceID,
                                        'create'                => [
                                            'moduleID'      => TADO_DEVICE_GUID,
                                            'name'          => 'Tado ' . $this->Translate($deviceName) . ' (' . $device['serialNo'] . ')',
                                            'configuration' => [
                                                'DeviceType'                  => (string) $device['deviceType'],
                                                'DeviceName'                  => (string) $device['deviceType'],
                                                'SerialNumber'                => (string) $device['serialNo'],
                                                'ShortSerialNumber'           => (string) $device['shortSerialNo'],
                                                'HomeID'                      => (string) $homeID
                                            ],
                                            'location' => $location
                                        ]
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $values;
    }

    private function GetZoneInstanceID(int $ZoneID, int $Type)
    {
        $id = 0;
        $moduleID = TADO_HEATING_GUID;
        if ($Type == 1) {
            $moduleID = TADO_COOLING_GUID;
        }
        $instances = IPS_GetInstanceListByModuleID($moduleID);
        foreach ($instances as $instance) {
            if (IPS_GetProperty($instance, 'ZoneID') == $ZoneID) {
                $id = $instance;
            }
        }
        return $id;
    }

    private function GetDeviceInstanceID(string $SerialNumber)
    {
        $id = 0;
        $instances = IPS_GetInstanceListByModuleID(TADO_DEVICE_GUID);
        foreach ($instances as $instance) {
            if (IPS_GetProperty($instance, 'SerialNumber') == $SerialNumber) {
                $id = $instance;
            }
        }
        return $id;
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
}