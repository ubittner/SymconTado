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

class TadoConfigurator extends IPSModule
{
    //Constants
    private const TADO_SPLITTER_GUID = '{31C59151-0182-07DB-4D0D-7EDA0668186F}';
    private const TADO_SPLITTER_DATA_GUID = '{9B0CC551-1523-14B7-8C56-39869942CF02}';
    private const TADO_HOME_GUID = '{69F3B4F8-3A8E-BB23-FEFD-66BB7846CAEF}';
    private const TADO_HEATING_GUID = '{F6D924F8-0CAB-2EB7-725D-2640B8F5556B}';
    private const TADO_COOLING_GUID = '{374753E5-0048-7EF7-43C5-D8AAB5CB317B}';
    private const TADO_AC_GUID = '{2EA2896C-8FB0-C565-EB69-F6FADECDC736}';
    private const TADO_DEVICE_GUID = '{07B0E642-8AF4-0E49-5A5E-DA70531CCF7F}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        //Register properties
        $this->RegisterPropertyInteger('CategoryID', 0);
        //Connect to splitter
        $this->ConnectParent(self::TADO_SPLITTER_GUID);
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
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
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

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function GetTadoSetup(): array
    {
        $values = [];
        if (!$this->HasActiveParent()) {
            return $values;
        }
        $location = $this->GetCategoryPath($this->ReadPropertyInteger(('CategoryID')));
        //Homes
        $data = [];
        $buffer = [];
        $data['DataID'] = self::TADO_SPLITTER_DATA_GUID;
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
                foreach ($homes as $home) {
                    $homeID = $home['id'];
                    $homeName = $home['name'];
                    $data = [];
                    $buffer = [];
                    $data['DataID'] = self::TADO_SPLITTER_DATA_GUID;
                    $buffer['Command'] = 'GetHome';
                    $buffer['Params'] = $homeID;
                    $data['Buffer'] = $buffer;
                    $data = json_encode($data);
                    $homes = $this->SendDataToParent($data);
                    $this->SendDebug(__FUNCTION__, $homes, 0);
                    $homeData = json_decode($homes, true);
                    if (!empty($homeData)) {
                        $homeDataID = $homeData['id'];
                        $homeDataName = $homeData['name'];
                        $contactName = $homeData['contactDetails']['name'];
                        $contactEMail = $homeData['contactDetails']['email'];
                        $contactPhone = $homeData['contactDetails']['phone'];
                        $addressLine1 = $homeData['address']['addressLine1'];
                        $addressLine2 = $homeData['address']['addressLine2'];
                        $zipCode = $homeData['address']['zipCode'];
                        $city = $homeData['address']['city'];
                        $state = $homeData['address']['state'];
                        $country = $homeData['address']['country'];
                        $homeInstanceID = $this->GetHomeInstanceID($homeDataID);
                        $values[] = [
                            'id'          => $homeDataID,
                            'expanded'    => true,
                            'name'        => $homeDataName,
                            'Description' => $this->Translate('Home'),
                            'Identifier'  => $homeDataID,
                            'Type'        => '',
                            'instanceID'  => $homeInstanceID,
                            'create'      => [
                                'moduleID'      => self::TADO_HOME_GUID,
                                'name'          => 'Tado ' . $home['name'] . ' (' . $this->Translate('Home') . ')',
                                'configuration' => [
                                    'HomeID'       => (string) $homeDataID,
                                    'HomeName'     => (string) $homeDataName,
                                    'ContactName'  => (string) $contactName,
                                    'ContactEMail' => (string) $contactEMail,
                                    'ContactPhone' => (string) $contactPhone,
                                    'AddressLine1' => (string) $addressLine1,
                                    'AddressLine2' => (string) $addressLine2,
                                    'ZipCode'      => (string) $zipCode,
                                    'City'         => (string) $city,
                                    'State'        => (string) $state,
                                    'Country'      => (string) $country,

                                ],
                                'location' => $location
                            ]
                        ];
                    }
                    //Zones
                    $data = [];
                    $buffer = [];
                    $data['DataID'] = self::TADO_SPLITTER_DATA_GUID;
                    $buffer['Command'] = 'GetZones';
                    $buffer['Params'] = $homeID;
                    $data['Buffer'] = $buffer;
                    $data = json_encode($data);
                    $zones = $this->SendDataToParent($data);
                    $this->SendDebug(__FUNCTION__, $zones, 0);
                    $zones = json_decode($zones, true);
                    if (!empty($zones)) {
                        foreach ($zones as $zone) {
                            $zoneID = (int) $zone['id'];
                            $id = $homeID . $zoneID;
                            if ($zone['type'] == 'HEATING') {
                                $instanceID = $this->GetZoneInstanceID($zoneID, 0);
                                $values[] = [
                                    'id'                    => $id,
                                    'parent'                => $homeID,
                                    'expanded'              => true,
                                    'name'                  => $zone['name'],
                                    'Description'           => $this->Translate('Room'),
                                    'Identifier'            => $zone['id'],
                                    'Type'                  => $zone['type'],
                                    'instanceID'            => $instanceID,
                                    'create'                => [
                                        'moduleID'      => self::TADO_HEATING_GUID,
                                        'name'          => 'Tado ' . $zone['name'] . ' (' . $this->Translate('Room') . ')',
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
                                    'expanded'              => true,
                                    'name'                  => $zone['name'],
                                    'Description'           => $this->Translate('Room'),
                                    'Identifier'            => $zone['id'],
                                    'Type'                  => $zone['type'],
                                    'instanceID'            => $instanceID,
                                    'create'                => [
                                        'moduleID'      => self::TADO_AC_GUID,
                                        'name'          => 'tado° AC ' . $zone['name'] . ' (' . $this->Translate('Room' . ')'),
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
                            //Devices
                            if (array_key_exists('devices', $zone)) {
                                $devices = $zone['devices'];
                                foreach ($devices as $device) {
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
                                        'name'                  => $deviceName,
                                        'Description'           => $this->Translate('Device'),
                                        'Identifier'            => $device['serialNo'],
                                        'Type'                  => $deviceType,
                                        'instanceID'            => $deviceInstanceID,
                                        'create'                => [
                                            'moduleID'      => self::TADO_DEVICE_GUID,
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

    private function GetCategoryPath(int $CategoryID): array
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

    private function GetHomeInstanceID(int $HomeID): int
    {
        $id = 0;
        $moduleID = self::TADO_HOME_GUID;
        $instances = IPS_GetInstanceListByModuleID($moduleID);
        foreach ($instances as $instance) {
            if (IPS_GetProperty($instance, 'HomeID') == $HomeID) {
                $id = $instance;
            }
        }
        return $id;
    }

    private function GetZoneInstanceID(int $ZoneID, int $Type): int
    {
        $id = 0;
        if ($Type == 0) {
            $moduleID = self::TADO_HEATING_GUID;
            $instances = IPS_GetInstanceListByModuleID($moduleID);
            foreach ($instances as $instance) {
                if (IPS_GetProperty($instance, 'ZoneID') == $ZoneID) {
                    $id = $instance;
                }
            }
        }
        if ($Type == 1) {
            $moduleID = self::TADO_AC_GUID;
            $legacy = self::TADO_COOLING_GUID; #deprecated, legacy
            $this->SendDebug(__FUNCTION__, $legacy . 'not supported anymore', 0);
            $instances = IPS_GetInstanceListByModuleID($moduleID);
            foreach ($instances as $instance) {
                if (IPS_GetProperty($instance, 'ZoneID') == $ZoneID) {
                    $id = $instance;
                }
            }
        }
        return $id;
    }

    private function GetDeviceInstanceID(string $SerialNumber): int
    {
        $id = 0;
        $instances = IPS_GetInstanceListByModuleID(self::TADO_DEVICE_GUID);
        foreach ($instances as $instance) {
            if (IPS_GetProperty($instance, 'SerialNumber') == $SerialNumber) {
                $id = $instance;
            }
        }
        return $id;
    }
}