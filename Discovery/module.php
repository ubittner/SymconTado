<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */
/** @noinspection PhpUnusedPrivateMethodInspection */

/*
 * @module      Tado Discovery
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
 *              Tado Discovery
 *             	{B8E6FB6F-6838-68AE-F708-82CB423ADBDE}
 */

declare(strict_types=1);

include_once __DIR__ . '/../libs/constants.php';

class TadoDiscovery extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
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
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $this->UpdateFormField('ScanProgress', 'indeterminate', false);
        $this->UpdateFormField('ScanProgress', 'caption', '');
        $this->UpdateFormField('ScanProgress', 'current', 0);
        $ScriptText = 'IPS_RequestAction(' . $this->InstanceID . ', \'StartDiscover\',true);';
        IPS_RunScriptText($ScriptText);
        return json_encode($formData);
    }

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == 'StartDiscover') {
            $this->DiscoverDevices();
        }
    }

    private function DiscoverDevices()
    {
        $this->UpdateFormField('ScanProgress', 'indeterminate', true);
        $this->UpdateFormField('ScanProgress', 'caption', $this->Translate('Please wait, searching for devices...'));
        $ids = IPS_GetInstanceListByModuleID(CORE_DNS_SD_GUID);
        $devices = ZC_QueryServiceType($ids[0], '_hap._tcp.', '');
        $existingDevices = [];
        if (!empty($devices)) {
            $i = 0;
            foreach ($devices as $device) {
                $data = [];
                $deviceInfos = ZC_QueryService($ids[0], $device['Name'], '_hap._tcp.', 'local.');
                if (!empty($deviceInfos)) {
                    foreach ($deviceInfos as $info) {
                        if (array_key_exists('TXTRecords', $info)) {
                            $txtRecords = $info['TXTRecords'];
                            foreach ($txtRecords as $record) {
                                $match = false;
                                //Internet bridge
                                if (strpos($record, 'md=tado Internet Bridge') !== false) {
                                    $this->SendDebug(__FUNCTION__, print_r($info, true), 0);
                                    $match = true;
                                    if (empty($info['IPv4'])) {
                                        $data['ip'] = $info['IPv6'][0];
                                    } else {
                                        $data['ip'] = $info['IPv4'][0];
                                    }
                                    if (array_key_exists('Name', $info)) {
                                        $search = ['tado Internet Bridge', '._hap._tcp.local'];
                                        $data['name'] = str_replace($search, '', $info['Name']);
                                    }

                                }
                                //Cooling thermostat
                                if (strpos($record, 'md=AC02') !== false) {
                                    $this->SendDebug(__FUNCTION__, print_r($info, true), 0);
                                    $match = true;
                                    if (empty($info['IPv4'])) {
                                        $data['ip'] = $info['IPv6'][0];
                                    } else {
                                        $data['ip'] = $info['IPv4'][0];
                                    }
                                    if (array_key_exists('Name', $info)) {
                                        $search = ['Smart AC Control', '._hap._tcp.local'];
                                        $data['name'] = str_replace($search, '', $info['Name']);
                                    }
                                }
                                if ($match) {
                                    foreach ($txtRecords as $value) {
                                        if (strpos($value, 'id=') !== false) {
                                            $data['id'] = str_replace('id=', '', $value);
                                        }
                                    }
                                    array_push($existingDevices, $data);
                                    $this->UpdateFormField('ScanProgress', 'current', $i++);
                                }
                            }
                        }
                    }
                }
            }
        }
        $values = [];
        if (!empty($existingDevices)) {
            foreach ($existingDevices as $existingDevice) {
                $existingDeviceID = (string) $existingDevice['id'];
                $instanceID = $this->GetSplitterInstanceID();
                $values[] = [
                    'IPAddress'     => $existingDevice['ip'],
                    'DeviceName'    => $existingDevice['name'],
                    'MACAddress'    => $existingDeviceID,
                    'instanceID'    => $instanceID,
                    'create'        => [
                        'moduleID'      => TADO_SPLITTER_GUID
                    ]
                ];
            }
        }
        $this->UpdateFormField('ScanProgress', 'indeterminate', false);
        $this->UpdateFormField('ScanProgress', 'caption', $this->Translate('Discovery finished'));
        $this->UpdateFormField('ScanProgress', 'current', 100);
        $this->UpdateFormField('Devices', 'values', json_encode($values));
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function GetSplitterInstanceID()
    {
        $id = 0;
        $instances = IPS_GetInstanceListByModuleID(TADO_SPLITTER_GUID);
        if (!empty($instances)) {
            $id = $instances[0];
        }
        return $id;
    }
}