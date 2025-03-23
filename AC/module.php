<?php

/**
 * @project       tado° AC
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/AC_autoload.php';

class TadoAC extends IPSModule
{
    //Helper
    use AC_Control;
    use AC_Properties;

    //Constants
    private const LIBRARY_GUID = '{2C88856B-7D25-7502-1594-11F588E2C685}';
    private const MODULE_NAME = 'tado° AC';
    private const MODULE_PREFIX = 'TADOAC';
    private const TADO_SPLITTER_GUID = '{31C59151-0182-07DB-4D0D-7EDA0668186F}';
    private const TADO_SPLITTER_DATA_GUID = '{9B0CC551-1523-14B7-8C56-39869942CF02}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Device
        $this->RegisterPropertyString('HomeID', '');
        $this->RegisterPropertyString('HomeName', '');
        $this->RegisterPropertyString('ZoneID', '');
        $this->RegisterPropertyString('ZoneName', '');
        $this->RegisterPropertyString('ZoneType', '');
        $this->RegisterPropertyInteger('UpdateInterval', 0);

        //Properties
        $this->RegisterPropertyBoolean('UseFanSpeed', true);
        $this->RegisterPropertyBoolean('UseFanLevel', false);
        $this->RegisterPropertyBoolean('UseSwing', false);
        $this->RegisterPropertyBoolean('UseVerticalSwing', false);
        $this->RegisterPropertyBoolean('UseHorizontalSwing', false);
        $this->RegisterPropertyBoolean('UseDisplayLight', false);

        //Device mode
        $this->RegisterPropertyBoolean('UseCoolTemperature', true);
        $this->RegisterPropertyBoolean('UseCoolFan', true);
        $this->RegisterPropertyBoolean('UseCoolSwing', false);
        $this->RegisterPropertyBoolean('UseCoolLight', false);
        $this->RegisterPropertyBoolean('UseHeatTemperature', true);
        $this->RegisterPropertyBoolean('UseHeatFan', true);
        $this->RegisterPropertyBoolean('UseHeatSwing', false);
        $this->RegisterPropertyBoolean('UseHeatLight', false);
        $this->RegisterPropertyBoolean('UseDryTemperature', false);
        $this->RegisterPropertyBoolean('UseDryFan', false);
        $this->RegisterPropertyBoolean('UseDrySwing', false);
        $this->RegisterPropertyBoolean('UseDryLight', false);
        $this->RegisterPropertyBoolean('UseFanTemperature', false);
        $this->RegisterPropertyBoolean('UseFan', false);
        $this->RegisterPropertyBoolean('UseFanSwing', false);
        $this->RegisterPropertyBoolean('UseFanLight', false);
        $this->RegisterPropertyBoolean('UseAutoTemperature', true);
        $this->RegisterPropertyBoolean('UseAutoFan', true);
        $this->RegisterPropertyBoolean('UseAutoSwing', false);
        $this->RegisterPropertyBoolean('UseAutoLight', false);

        ########## Variables

        //Power
        $this->RegisterVariableBoolean('Power', 'Power', '~Switch', 10);
        $this->EnableAction('Power');

        //Operation mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.OperationMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Manual'), 'Execute', -1);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Smart Schedule'), 'Calendar', 0x00FF00);
        $this->RegisterVariableBoolean('OperationMode', $this->Translate('Operation Mode'), $profile, 20);
        $this->EnableAction('OperationMode');

        //Cooling timer
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.CoolingTimer';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Clock');
        IPS_SetVariableProfileValues($profile, 0, 12, 0);
        IPS_SetVariableProfileDigits($profile, 2);
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Off'), '', 0xA9B8C4);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Till next time block'), '', 0xA9B8C4);
        for ($i = 5; $i <= 45; $i += 5) {
            $seconds = $i * 60;
            $j = sprintf('%02d', $i);
            IPS_SetVariableProfileAssociation($profile, $seconds, '00:' . $j . ':00', '', -1);
        }
        IPS_SetVariableProfileAssociation($profile, 3600, '01:00:00', '', -1);
        for ($i = 15; $i <= 45; $i += 15) {
            $seconds = 3600 + ($i * 60);
            IPS_SetVariableProfileAssociation($profile, $seconds, '01:' . $i . ':00', '', -1);
        }
        IPS_SetVariableProfileAssociation($profile, 7200, '02:00:00', '', -1);
        for ($i = 15; $i <= 45; $i += 15) {
            $seconds = 7200 + ($i * 60);
            IPS_SetVariableProfileAssociation($profile, $seconds, '02:' . $i . ':00', '', -1);
        }
        IPS_SetVariableProfileAssociation($profile, 10800, '03:00:00', '', -1);
        IPS_SetVariableProfileAssociation($profile, 10800 + 1800, '03:30:00', '', -1);
        IPS_SetVariableProfileAssociation($profile, 14400, '04:00:00', '', -1);
        IPS_SetVariableProfileAssociation($profile, 14400 + 1800, '04:30:00', '', -1);
        for ($i = 5; $i <= 12; $i++) {
            $seconds = $i * 3600;
            $j = sprintf('%02d', $i);
            IPS_SetVariableProfileAssociation($profile, $seconds, $j . ':00:00', '', -1);
        }
        $this->RegisterVariableInteger('CoolingTimer', 'Timer', $profile, 30);
        $this->EnableAction('CoolingTimer');

        //Device mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DeviceMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 3);
            IPS_SetVariableProfileAssociation($profile, 'COOL', $this->Translate('Cool'), 'Snowflake', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'HEAT', $this->Translate('Heat'), 'Sun', 0xFF0000);
            IPS_SetVariableProfileAssociation($profile, 'DRY', $this->Translate('Dry'), 'Drops', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'FAN', $this->Translate('Fan'), 'Ventilation', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'AUTO', $this->Translate('Auto'), 'Climate', -1);
        }
        $id = @$this->GetIDForIdent('DeviceMode');
        $this->RegisterVariableString('DeviceMode', $this->Translate('Device mode'), $profile, 40);
        $this->EnableAction('DeviceMode');
        if (!$id) {
            $this->SetValue('DeviceMode', 'COOL');
        }

        //Set point temperature
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SetpointTemperature';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 2);
        }
        IPS_SetVariableProfileIcon($profile, 'Temperature');
        IPS_SetVariableProfileValues($profile, 0, 25, 0);
        IPS_SetVariableProfileDigits($profile, 1);
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', 0xA9B8C4);
        for ($i = 15; $i <= 30; $i += 0.5) {
            $color = 0x8AC1E8;
            IPS_SetVariableProfileAssociation($profile, $i, $i . ' °', '', $color);
        }
        $this->RegisterVariableFloat('SetpointTemperature', $this->Translate('Setpoint temperature'), $profile, 50);
        $this->EnableAction('SetpointTemperature');

        //Fan speed
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.FanSpeed';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 3);
            IPS_SetVariableProfileIcon($profile, 'Climate');
            IPS_SetVariableProfileAssociation($profile, 'LOW', $this->Translate('Low'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'MIDDLE', $this->Translate('Middle'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'HIGH', $this->Translate('High'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'AUTO', $this->Translate('Auto'), '', -1);
        }
        $id = @$this->GetIDForIdent('FanSpeed');
        $this->RegisterVariableString('FanSpeed', $this->Translate('Fan speed'), $profile, 60);
        $this->EnableAction('FanSpeed');
        if (!$id) {
            $this->SetValue('FanSpeed', 'AUTO');
        }

        //Fan level
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.FanLevel';
        if (IPS_VariableProfileExists($profile) === false) {
            IPS_CreateVariableProfile($profile, 3);
            IPS_SetVariableProfileIcon($profile, 'Climate');
            IPS_SetVariableProfileAssociation($profile, 'SILENT', $this->Translate('Silent'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'LEVEL1', $this->Translate('Level 1'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'LEVEL2', $this->Translate('Level 2'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'LEVEL3', $this->Translate('Level 3'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'LEVEL4', $this->Translate('Level 4'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'LEVEL5', $this->Translate('Level 5'), '', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 'AUTO', $this->Translate('Auto'), '', -1);
        }
        $id = @$this->GetIDForIdent('FanLevel');
        $this->RegisterVariableString('FanLevel', $this->Translate('Fan level'), $profile, 70);
        $this->EnableAction('FanLevel');
        if (!$id) {
            $this->SetValue('FanLevel', 'AUTO');
        }

        //Swing
        $id = @$this->GetIDForIdent('Swing');
        $this->RegisterVariableBoolean('Swing', $this->Translate('Swing'), '~Switch', 80);
        $this->EnableAction('Power');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('Swing'), 'Shutter');
        }

        //Vertical swing
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.VerticalSwing';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 3);
            IPS_SetVariableProfileIcon($profile, 'Shutter');
            IPS_SetVariableProfileAssociation($profile, 'OFF', $this->Translate('Off'), '', -1);
            IPS_SetVariableProfileAssociation($profile, 'ON', $this->Translate('On'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'UP', $this->Translate('Up'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'MID_UP', $this->Translate('Mid up'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'MID', $this->Translate('Mid'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'MID_DOWN', $this->Translate('Mid down'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'DOWN', $this->Translate('Down'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'AUTO', $this->Translate('Auto'), '', -1);
        }
        $id = @$this->GetIDForIdent('VerticalSwing');
        $this->RegisterVariableString('VerticalSwing', $this->Translate('Vertical swing'), $profile, 90);
        $this->EnableAction('VerticalSwing');
        if (!$id) {
            $this->SetValue('VerticalSwing', 'OFF');
        }

        //Horizontal swing
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.HorizontalSwing';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 3);
            IPS_SetVariableProfileIcon($profile, 'Shutter');
            IPS_SetVariableProfileAssociation($profile, 'OFF', $this->Translate('Off'), '', -1);
            IPS_SetVariableProfileAssociation($profile, 'ON', $this->Translate('On'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'LEFT', $this->Translate('Left'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'MID_LEFT', $this->Translate('Mid left'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'MID', $this->Translate('Mid'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'MID_RIGHT', $this->Translate('Mid right'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'RIGHT', $this->Translate('Right'), '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 'AUTO', $this->Translate('Auto'), '', -1);
        }
        $id = @$this->GetIDForIdent('HorizontalSwing');
        $this->RegisterVariableString('HorizontalSwing', $this->Translate('Horizontal swing'), $profile, 100);
        $this->EnableAction('HorizontalSwing');
        if (!$id) {
            $this->SetValue('HorizontalSwing', 'OFF');
        }

        //Display light
        $id = @$this->GetIDForIdent('DisplayLight');
        $this->RegisterVariableBoolean('DisplayLight', $this->Translate('Display light'), '~Switch', 110);
        $this->EnableAction('DisplayLight');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('DisplayLight'), 'Bulb');
        }

        //Room temperature
        $this->RegisterVariableFloat('RoomTemperature', $this->Translate('Room temperature'), '~Temperature', 120);

        //Humidity
        $this->RegisterVariableFloat('AirHumidity', $this->Translate('Air humidity'), '~Humidity.F', 130);

        //Network status
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Link';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Offline', 'Network', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'Online', 'Network', 0x00FF00);
        $this->RegisterVariableBoolean('Link', $this->Translate('Network status'), $profile, 140);

        ########## Timer

        $this->RegisterTimer('UpdateCoolingState', 0, self::MODULE_PREFIX . '_UpdateCoolingZoneState(' . $this->InstanceID . ');');

        ##########  Connect to splitter

        $this->ConnectParent(self::TADO_SPLITTER_GUID);
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

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('FanSpeed'), !$this->ReadPropertyBoolean('UseFanSpeed'));
        IPS_SetHidden($this->GetIDForIdent('FanLevel'), !$this->ReadPropertyBoolean('UseFanLevel'));
        IPS_SetHidden($this->GetIDForIdent('Swing'), !$this->ReadPropertyBoolean('UseSwing'));
        IPS_SetHidden($this->GetIDForIdent('VerticalSwing'), !$this->ReadPropertyBoolean('UseVerticalSwing'));
        IPS_SetHidden($this->GetIDForIdent('HorizontalSwing'), !$this->ReadPropertyBoolean('UseHorizontalSwing'));
        IPS_SetHidden($this->GetIDForIdent('DisplayLight'), !$this->ReadPropertyBoolean('UseDisplayLight'));

        //Set timer
        $this->SetTimerInterval('UpdateCoolingState', $this->ReadPropertyInteger('UpdateInterval') * 1000);

        //Update state
        $this->UpdateCoolingZoneState();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['OperationMode', 'CoolingTimer', 'DeviceMode', 'SetpointTemperature', 'FanSpeed', 'FanLevel', 'VerticalSwing', 'HorizontalSwing'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
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
        $form['elements'][] = [
            'type'  => 'Image',
            'image' => 'data:image/png;base64, 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'
        ];

        $form['elements'][] =
            [
                'type'    => 'Label',
                'caption' => self::MODULE_NAME
            ];

        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $form['elements'][] =
            [
                'type'    => 'Label',
                'caption' => 'ID ' . $this->InstanceID . ', Version ' . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
            ];

        $form['elements'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Device information',
            'italic'  => true,
            'bold'    => true
        ];

        $form['elements'][] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'HomeID',
            'caption' => 'Home ID',
            'width'   => '600px'
        ];

        $form['elements'][] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'HomeName',
            'caption' => 'Home Name',
            'width'   => '600px'
        ];

        $form['elements'][] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'ZoneID',
            'caption' => 'Zone ID',
            'width'   => '600px'
        ];

        $form['elements'][] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'ZoneName',
            'caption' => 'Zone Name',
            'width'   => '600px'
        ];

        $form['elements'][] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'ZoneType',
            'caption' => 'Zone Type',
            'width'   => '600px'
        ];

        $form['elements'][] = [
            'type'     => 'NumberSpinner',
            'name'     => 'UpdateInterval',
            'caption'  => 'Update Interval',
            'suffix'   => 'seconds'
        ];

        $form['elements'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Device properties',
            'italic'  => true,
            'bold'    => true
        ];

        $form['elements'][] = [
            'type'    => 'CheckBox',
            'name'    => 'UseFanSpeed',
            'caption' => 'Fan Speed (fanSpeed)'
        ];

        $form['elements'][] = [
            'type'    => 'CheckBox',
            'name'    => 'UseFanLevel',
            'caption' => 'Fan Level (fanLevel)'
        ];

        $form['elements'][] = [
            'type'    => 'CheckBox',
            'name'    => 'UseSwing',
            'caption' => 'Swing (swing)'
        ];

        $form['elements'][] = [
            'type'    => 'CheckBox',
            'name'    => 'UseVerticalSwing',
            'caption' => 'Vertical Swing (verticalSwing)'
        ];

        $form['elements'][] = [
            'type'    => 'CheckBox',
            'name'    => 'UseHorizontalSwing',
            'caption' => 'Horizontal Swing (horizontalSwing)'
        ];

        $form['elements'][] = [
            'type'    => 'CheckBox',
            'name'    => 'UseDisplayLight',
            'caption' => 'Display (light)'
        ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => ' '
        ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Device mode',
            'italic'  => true,
            'bold'    => true
        ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Cool (COOL)',
            'italic'  => true
        ];

        $form['elements'][] = [
            'type'    => 'RowLayout',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseCoolTemperature',
                    'caption' => 'Temperature'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseCoolFan',
                    'caption' => 'Fan'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseCoolSwing',
                    'caption' => 'Swing'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseCoolLight',
                    'caption' => 'Display light'
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Heat (HEAT)',
            'italic'  => true
        ];

        $form['elements'][] = [
            'type'    => 'RowLayout',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseHeatTemperature',
                    'caption' => 'Temperature'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseHeatFan',
                    'caption' => 'Fan'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseHeatSwing',
                    'caption' => 'Swing'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseHeatLight',
                    'caption' => 'Display light'
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Dry (DRY)',
            'italic'  => true
        ];

        $form['elements'][] = [
            'type'    => 'RowLayout',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseDryTemperature',
                    'caption' => 'Temperature'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseDryFan',
                    'caption' => 'Fan'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseDrySwing',
                    'caption' => 'Swing'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseDryLight',
                    'caption' => 'Display light'
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Fan (FAN)',
            'italic'  => true
        ];

        $form['elements'][] = [
            'type'    => 'RowLayout',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseFanTemperature',
                    'caption' => 'Temperature'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseFan',
                    'caption' => 'Fan'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseFanSwing',
                    'caption' => 'Swing'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseFanLight',
                    'caption' => 'Display light'
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'Label',
            'caption' => 'Auto (AUTO)',
            'italic'  => true
        ];

        $form['elements'][] = [
            'type'    => 'RowLayout',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseAutoTemperature',
                    'caption' => 'Temperature'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseAutoFan',
                    'caption' => 'Fan'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseAutoSwing',
                    'caption' => 'Swing'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseAutoLight',
                    'caption' => 'Display light'
                ]
            ]
        ];

        ########## Actions

        $form['actions'][] = [
            'type'    => 'RowLayout',
            'items'   => [
                [
                    'type'    => 'Select',
                    'name'    => 'DeviceType',
                    'caption' => 'Device type',
                    'options' => [
                        [
                            'caption' => 'Default',
                            'value'   => 0
                        ],
                        [
                            'caption'=> 'Fujitsu AUYG07LVLA',
                            'value'  => 1
                        ],
                        [
                            'caption'=> 'Fujitsu ASYG09LMCA',
                            'value'  => 2
                        ],
                        [
                            'caption'=> 'LG Standard Plus PC12SQ',
                            'value'  => 3
                        ],
                        [
                            'caption'=> 'Comfee MS11M6-12 ',
                            'value'  => 4
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'      => 'Button',
                    'caption'   => 'Set properties',
                    'onClick'   => self::MODULE_PREFIX . '_SetDeviceModeProperties($id, $DeviceType);',
                ]
            ]
        ];

        $form['actions'][] = [
            'type'    => 'Label',
            'caption' => ' '
        ];

        $form['actions'][] = [
            'type'      => 'Button',
            'caption'   => 'Update State',
            'onClick'   => 'echo ' . self::MODULE_PREFIX . '_UpdateCoolingZoneState($id);',
        ];

        $form['actions'][] = [
            'type'    => 'Label',
            'caption' => ' '
        ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' will be created',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' is active',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' will be deleted',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => self::MODULE_NAME . ' is inactive',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'An error has occurred, for further information see messages, log file or debug!',
        ];

        return json_encode($form);
    }

    public function ReceiveData($JSONString)
    {
        //Receive data from splitter, not used at the moment
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Power':
                $this->TogglePower($Value);
                break;

            case 'OperationMode':
                $this->ToggleOperationMode($Value);
                break;

            case 'CoolingTimer':
                $this->SetCoolingTimer($Value);
                break;

            case 'DeviceMode':
                $this->ToggleDeviceMode($Value);
                break;

            case 'SetpointTemperature':
                $this->SetTemperature($Value);
                break;

            case 'FanSpeed':
                $this->SetFanSpeed($Value);
                break;

            case 'FanLevel':
                $this->SetFanLevel($Value);
                break;

            case 'Swing':
                $this->SetSwingState($Value);
                break;

            case 'VerticalSwing':
                $this->SetVerticalSwingState($Value);
                break;

            case 'HorizontalSwing':
                $this->SetHorizontalSwingState($Value);
                break;

            case 'DisplayLight':
                $this->ToggleDisplayLight($Value);
                break;

        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }
}