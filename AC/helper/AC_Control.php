<?php

/**
 * @project       tado° AC
 * @file          AC_Control.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait AC_Control
{
    /**
     * Toggles the power.
     *
     * @param bool $State
     * false =  Off
     * true =   On
     * @return void
     * @throws Exception
     */
    public function TogglePower(bool $State): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'State: ' . json_encode($State), 0);

        //Check parent
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', $State);
        $this->SetCooling();
    }

    /**
     * Toggles the operation mode.
     *
     * @param bool $Mode
     * false =  Manual mode
     * true =   Smart schedule (automatic)
     *
     * @return void
     * @throws Exception
     */
    public function ToggleOperationMode(bool $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'Mode: ' . json_encode($Mode), 0);

        //Check parent
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('OperationMode', $Mode);
        //Manual mode
        if (!$Mode) {
            $this->SendDebug(__FUNCTION__, 'OperationMode: Manual', 0);
            $this->SetValue('Power', true);
            $this->SetCooling();
        }
        //Smart schedule (automatic)
        if ($Mode) {
            $this->SendDebug(__FUNCTION__, 'OperationMode: Automatic', 0);
            $homeID = intval($this->ReadPropertyString('HomeID'));
            $zoneID = intval($this->ReadPropertyString('ZoneID'));
            $data = [];
            $buffer = [];
            $data['DataID'] = self::TADO_SPLITTER_DATA_GUID;
            $buffer['Command'] = 'StopManualMode';
            $buffer['Params'] = ['homeID' => $homeID, 'zoneID' => $zoneID];
            $data['Buffer'] = $buffer;
            $data = json_encode($data);
            json_decode($this->SendDataToParent($data), true);
            $this->UpdateCoolingZoneState();
        }
    }

    /**
     * Sets the cooling timer.
     *
     * @param int $Duration
     * @return void
     * @throws Exception
     */
    public function SetCoolingTimer(int $Duration): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'Duration: ' . $Duration, 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('CoolingTimer', $Duration);
        $this->SetCooling();
    }

    /**
     * Toggles the device mode.
     *
     * @param string $Mode
     * COOL, HEAT, DRY, FAN, AUTO
     *
     * @return void
     * @throws Exception
     */
    public function ToggleDeviceMode(string $Mode): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'Mode: ' . $Mode, 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('DeviceMode', $Mode);
        $this->SetCooling();
    }

    /**
     * Sets the temperature.
     *
     * @param float $Temperature
     * @return void
     * @throws Exception
     */
    public function SetTemperature(float $Temperature): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'Temperature: ' . $Temperature, 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('SetpointTemperature', $Temperature);
        $this->SetCooling();
    }

    /**
     * Sets the fan speed.
     *
     * @param string $Speed
     * LOW, MIDDLE, HIGH, AUTO
     *
     * @return void
     * @throws Exception
     */
    public function SetFanSpeed(string $Speed): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'Speed: ' . $Speed, 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('FanSpeed', $Speed);
        $this->SetCooling();
    }

    /**
     * Sets the fan level.
     *
     * @param string $Level
     * SILENT, LEVEL1, LEVEL2, LEVEL3, LEVEL4, LEVEL5, AUTO
     *
     * @return void
     * @throws Exception
     * @throws Exception
     */
    public function SetFanLevel(string $Level): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'Level: ' . $Level, 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('FanLevel', $Level);
        $this->SetCooling();
    }

    /**
     * Sets the swing state.
     *
     * @param bool $State
     * false =  Off
     * true =   On
     *
     * @return void
     * @throws Exception
     * @throws Exception
     */
    public function SetSwingState(bool $State): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'State: ' . json_encode($State), 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('Swing', $State);
        $this->SetCooling();
    }

    /**
     * Sets the vertical swing state.
     *
     * @param string $State
     * OFF, ON, UP, MID_UP, MID, MID_DOWN, DOWN, AUTO
     *
     * @return void
     * @throws Exception
     * @throws Exception
     */
    public function SetVerticalSwingState(string $State): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'State: ' . $State, 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('VerticalSwing', $State);
        $this->SetCooling();
    }

    /**
     * Sets the horizontal swing state.
     *
     * @param string $State
     * OFF, ON, LEFT, MID_LEFT, MID, MID_RIGHT, RIGHT, AUTO
     *
     * @return void
     * @throws Exception
     * @throws Exception
     */
    public function SetHorizontalSwingState(string $State): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'State: ' . $State, 0);

        // Check parent
        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('Power', true);
        $this->SetValue('OperationMode', false);
        $this->SetValue('HorizontalSwing', $State);
        $this->SetCooling();
    }

    /**
     * Toggles the display light.
     *
     * @param bool $State
     * false =  Off
     * true =   On
     *
     * @return void
     * @throws Exception
     * @throws Exception
     */
    public function ToggleDisplayLight(bool $State): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'State: ' . json_encode($State), 0);

        //Check parent
        if (!$this->CheckParent()) {
            return;
        }
        //Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $this->SetValue('DisplayLight', $State);
        $this->SetCooling();
    }

    /**
     * Sets the parameters for a cooling zone.
     *
     * @return void
     * @throws Exception
     */
    public function SetCooling(): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);

        //Disable update during cooling setting
        $this->SetTimerInterval('UpdateCoolingState', 0);
        //Prepare data
        $data = [];
        $buffer = [];
        $data['DataID'] = self::TADO_SPLITTER_DATA_GUID;
        $homeID = $this->ReadPropertyString('HomeID');
        $zoneID = $this->ReadPropertyString('ZoneID');
        //Power off
        if (!$this->GetValue('Power')) {
            $buffer['Command'] = 'SetCoolingZone';
            $postfields['termination'] = ['typeSkillBasedApp' => 'MANUAL'];
            $postfields['setting'] = ['power' => 'OFF', 'type' => 'AIR_CONDITIONING'];
            $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID, 'overlay' => json_encode($postfields)];
        } else {
            //Power on
            //Smart schedule
            if ($this->GetValue('OperationMode')) {
                $buffer['Command'] = 'StopManualMode';
                $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID];
            } else {
                //Manual mode
                $buffer['Command'] = 'SetCoolingZone';
                //Timer
                $coolingTimer = $this->GetValue('CoolingTimer');
                if ($coolingTimer == 0) { # No timer
                    $postfields['termination'] = ['typeSkillBasedApp' => 'MANUAL'];
                }
                if ($coolingTimer == 1) { # Timer till next time block
                    $postfields['termination'] = ['typeSkillBasedApp' => 'NEXT_TIME_BLOCK'];
                }
                if ($coolingTimer >= 300) { # Timer
                    $postfields['termination'] = ['type' => 'TIMER', 'durationInSeconds' => $coolingTimer];
                }

                //Device mode
                $deviceMode = $this->GetValue('DeviceMode');
                $postfields['setting']['mode'] = $deviceMode;
                //Type
                $postfields['setting']['type'] = 'AIR_CONDITIONING';
                //Power
                $postfields['setting']['power'] = 'ON';

                switch ($deviceMode) {
                    case 'COOL':
                        //Temperature
                        if ($this->ReadPropertyBoolean('UseCoolTemperature')) {
                            $postfields['setting']['temperature'] = ['celsius' => $this->GetValue('SetpointTemperature'), 'fahrenheit' => (float) (($this->GetValue('SetpointTemperature') * 9 / 5) + 32)];
                        }
                        //Fan
                        if ($this->ReadPropertyBoolean('UseCoolFan')) {
                            //Fan speed
                            if ($this->ReadPropertyBoolean('UseFanSpeed')) {
                                $postfields['setting']['fanSpeed'] = (string) $this->GetValue('FanSpeed');
                            }
                            //Fan level
                            if ($this->ReadPropertyBoolean('UseFanLevel')) {
                                $postfields['setting']['fanLevel'] = (string) $this->GetValue('FanLevel');
                            }
                        }
                        //Swing
                        if ($this->ReadPropertyBoolean('UseCoolSwing')) {
                            //Swing
                            if ($this->ReadPropertyBoolean('UseSwing')) {
                                $swing = 'OFF';
                                if ($this->GetValue('Swing')) {
                                    $swing = 'ON';
                                }
                                $postfields['setting']['swing'] = $swing;
                            }
                            //Vertical swing
                            if ($this->ReadPropertyBoolean('UseVerticalSwing')) {
                                $postfields['setting']['verticalSwing'] = (string) $this->GetValue('VerticalSwing');
                            }
                            //Horizontal swing
                            if ($this->ReadPropertyBoolean('UseHorizontalSwing')) {
                                $postfields['setting']['horizontalSwing'] = (string) $this->GetValue('HorizontalSwing');
                            }
                        }
                        //Light
                        if ($this->ReadPropertyBoolean('UseCoolLight')) {
                            //Light
                            if ($this->ReadPropertyBoolean('UseDisplayLight')) {
                                $light = 'OFF';
                                if ($this->GetValue('DisplayLight')) {
                                    $light = 'ON';
                                }
                                $postfields['setting']['light'] = $light;
                            }
                        }
                        break;

                    case 'HEAT':
                        //Temperature
                        if ($this->ReadPropertyBoolean('UseHeatTemperature')) {
                            $postfields['setting']['temperature'] = ['celsius' => $this->GetValue('SetpointTemperature'), 'fahrenheit' => (float) (($this->GetValue('SetpointTemperature') * 9 / 5) + 32)];
                        }
                        //Fan
                        if ($this->ReadPropertyBoolean('UseHeatFan')) {
                            //Fan speed
                            if ($this->ReadPropertyBoolean('UseFanSpeed')) {
                                $postfields['setting']['fanSpeed'] = (string) $this->GetValue('FanSpeed');
                            }
                            //Fan level
                            if ($this->ReadPropertyBoolean('UseFanLevel')) {
                                $postfields['setting']['fanLevel'] = (string) $this->GetValue('FanLevel');
                            }
                        }
                        //Swing
                        if ($this->ReadPropertyBoolean('UseHeatSwing')) {
                            //Swing
                            if ($this->ReadPropertyBoolean('UseSwing')) {
                                $swing = 'OFF';
                                if ($this->GetValue('Swing')) {
                                    $swing = 'ON';
                                }
                                $postfields['setting']['swing'] = $swing;
                            }
                            //Vertical swing
                            if ($this->ReadPropertyBoolean('UseVerticalSwing')) {
                                $postfields['setting']['verticalSwing'] = (string) $this->GetValue('VerticalSwing');
                            }
                            //Horizontal swing
                            if ($this->ReadPropertyBoolean('UseHorizontalSwing')) {
                                $postfields['setting']['horizontalSwing'] = (string) $this->GetValue('HorizontalSwing');
                            }
                        }
                        //Light
                        if ($this->ReadPropertyBoolean('UseHeatLight')) {
                            //Light
                            if ($this->ReadPropertyBoolean('UseDisplayLight')) {
                                $light = 'OFF';
                                if ($this->GetValue('DisplayLight')) {
                                    $light = 'ON';
                                }
                                $postfields['setting']['light'] = $light;
                            }
                        }
                        break;

                    case 'DRY':
                        //Temperature
                        if ($this->ReadPropertyBoolean('UseDryTemperature')) {
                            $postfields['setting']['temperature'] = ['celsius' => $this->GetValue('SetpointTemperature'), 'fahrenheit' => (float) (($this->GetValue('SetpointTemperature') * 9 / 5) + 32)];
                        }
                        //Fan
                        if ($this->ReadPropertyBoolean('UseDryFan')) {
                            //Fan speed
                            if ($this->ReadPropertyBoolean('UseFanSpeed')) {
                                $postfields['setting']['fanSpeed'] = (string) $this->GetValue('FanSpeed');
                            }
                            //Fan level
                            if ($this->ReadPropertyBoolean('UseFanLevel')) {
                                $postfields['setting']['fanLevel'] = (string) $this->GetValue('FanLevel');
                            }
                        }
                        //Swing
                        if ($this->ReadPropertyBoolean('UseDrySwing')) {
                            //Swing
                            if ($this->ReadPropertyBoolean('UseSwing')) {
                                $swing = 'OFF';
                                if ($this->GetValue('Swing')) {
                                    $swing = 'ON';
                                }
                                $postfields['setting']['swing'] = $swing;
                            }
                            //Vertical swing
                            if ($this->ReadPropertyBoolean('UseVerticalSwing')) {
                                $postfields['setting']['verticalSwing'] = (string) $this->GetValue('VerticalSwing');
                            }
                            //Horizontal swing
                            if ($this->ReadPropertyBoolean('UseHorizontalSwing')) {
                                $postfields['setting']['horizontalSwing'] = (string) $this->GetValue('HorizontalSwing');
                            }
                        }
                        //Light
                        if ($this->ReadPropertyBoolean('UseDryLight')) {
                            //Light
                            if ($this->ReadPropertyBoolean('UseDisplayLight')) {
                                $light = 'OFF';
                                if ($this->GetValue('DisplayLight')) {
                                    $light = 'ON';
                                }
                                $postfields['setting']['light'] = $light;
                            }
                        }
                        break;

                    case 'FAN':
                        //Temperature
                        if ($this->ReadPropertyBoolean('UseFanTemperature')) {
                            $postfields['setting']['temperature'] = ['celsius' => $this->GetValue('SetpointTemperature'), 'fahrenheit' => (float) (($this->GetValue('SetpointTemperature') * 9 / 5) + 32)];
                        }
                        //Fan
                        if ($this->ReadPropertyBoolean('UseFan')) {
                            //Fan speed
                            if ($this->ReadPropertyBoolean('UseFanSpeed')) {
                                $postfields['setting']['fanSpeed'] = (string) $this->GetValue('FanSpeed');
                            }
                            //Fan level
                            if ($this->ReadPropertyBoolean('UseFanLevel')) {
                                $postfields['setting']['fanLevel'] = (string) $this->GetValue('FanLevel');
                            }
                        }
                        //Swing
                        if ($this->ReadPropertyBoolean('UseFanSwing')) {
                            //Swing
                            if ($this->ReadPropertyBoolean('UseSwing')) {
                                $swing = 'OFF';
                                if ($this->GetValue('Swing')) {
                                    $swing = 'ON';
                                }
                                $postfields['setting']['swing'] = $swing;
                            }
                            //Vertical swing
                            if ($this->ReadPropertyBoolean('UseVerticalSwing')) {
                                $postfields['setting']['verticalSwing'] = (string) $this->GetValue('VerticalSwing');
                            }
                            //Horizontal swing
                            if ($this->ReadPropertyBoolean('UseHorizontalSwing')) {
                                $postfields['setting']['horizontalSwing'] = (string) $this->GetValue('HorizontalSwing');
                            }
                        }
                        //Light
                        if ($this->ReadPropertyBoolean('UseFanLight')) {
                            //Light
                            if ($this->ReadPropertyBoolean('UseDisplayLight')) {
                                $light = 'OFF';
                                if ($this->GetValue('DisplayLight')) {
                                    $light = 'ON';
                                }
                                $postfields['setting']['light'] = $light;
                            }
                        }
                        break;

                    case 'AUTO':
                        //Temperature
                        if ($this->ReadPropertyBoolean('UseAutoTemperature')) {
                            $postfields['setting']['temperature'] = ['celsius' => $this->GetValue('SetpointTemperature'), 'fahrenheit' => (float) (($this->GetValue('SetpointTemperature') * 9 / 5) + 32)];
                        }
                        //Fan
                        if ($this->ReadPropertyBoolean('UseAutoFan')) {
                            //Fan speed
                            if ($this->ReadPropertyBoolean('UseFanSpeed')) {
                                $postfields['setting']['fanSpeed'] = (string) $this->GetValue('FanSpeed');
                            }
                            //Fan level
                            if ($this->ReadPropertyBoolean('UseFanLevel')) {
                                $postfields['setting']['fanLevel'] = (string) $this->GetValue('FanLevel');
                            }
                        }
                        //Swing
                        if ($this->ReadPropertyBoolean('UseAutoSwing')) {
                            //Swing
                            if ($this->ReadPropertyBoolean('UseSwing')) {
                                $swing = 'OFF';
                                if ($this->GetValue('Swing')) {
                                    $swing = 'ON';
                                }
                                $postfields['setting']['swing'] = $swing;
                            }
                            //Vertical swing
                            if ($this->ReadPropertyBoolean('UseVerticalSwing')) {
                                $postfields['setting']['verticalSwing'] = (string) $this->GetValue('VerticalSwing');
                            }
                            //Horizontal swing
                            if ($this->ReadPropertyBoolean('UseHorizontalSwing')) {
                                $postfields['setting']['horizontalSwing'] = (string) $this->GetValue('HorizontalSwing');
                            }
                        }
                        //Light
                        if ($this->ReadPropertyBoolean('UseAutoLight')) {
                            //Light
                            if ($this->ReadPropertyBoolean('UseDisplayLight')) {
                                $light = 'OFF';
                                if ($this->GetValue('DisplayLight')) {
                                    $light = 'ON';
                                }
                                $postfields['setting']['light'] = $light;
                            }
                        }
                        break;
                }

                // Add data
                $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID, 'overlay' => json_encode($postfields)];
                $this->SendDebug(__FUNCTION__, 'Postfields: ' . json_encode($postfields), 0);
            }
        }
        // Send data
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $result = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        // Update
        $this->UpdateCoolingZoneState();
    }

    /**
     * Updates the states of a cooling zone.
     *
     * @return void
     * @throws Exception
     */
    public function UpdateCoolingZoneState(): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);

        if (!$this->CheckParent()) {
            return;
        }
        // Check IDs
        if (!$this->CheckHomeID()) {
            return;
        }
        if (!$this->CheckZoneID()) {
            return;
        }
        $homeID = $this->ReadPropertyString('HomeID');
        $zoneID = $this->ReadPropertyString('ZoneID');
        $data = [];
        $buffer = [];
        $data['DataID'] = self::TADO_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetZoneState';
        $buffer['Params'] = ['homeID' => (int) $homeID, 'zoneID' => (int) $zoneID];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        if (!empty($result)) {
            //OperationMode
            $mode = 1; //Smart schedule
            if (array_key_exists('overlayType', $result)) {
                if ($result['overlayType'] == 'MANUAL') {
                    $mode = 0;
                }
            }
            $this->SetValue('OperationMode', $mode);
            //Setting
            if (array_key_exists('setting', $result)) {
                //Power
                if (array_key_exists('power', $result['setting'])) {
                    $powerState = false;
                    if ($result['setting']['power'] == 'ON') {
                        $powerState = true;
                    }
                    $this->SetValue('Power', $powerState);
                }
                //Device mode
                if (array_key_exists('mode', $result['setting'])) {
                    $this->SetValue('DeviceMode', (string) $result['setting']['mode']);
                }
                //Setpoint temperature
                if (array_key_exists('temperature', $result['setting'])) {
                    $temperatureSettings = $result['setting']['temperature'];
                    if (is_array($temperatureSettings)) {
                        if (array_key_exists('celsius', $temperatureSettings)) {
                            $this->SetValue('SetpointTemperature', floatval($temperatureSettings['celsius']));
                        }
                    }
                }
                //Fan speed
                if (array_key_exists('fanSpeed', $result['setting'])) {
                    $this->SetValue('FanSpeed', (string) $result['setting']['fanSpeed']);
                }
                //Fan level
                if (array_key_exists('fanLevel', $result['setting'])) {
                    $this->SetValue('FanLevel', (string) $result['setting']['fanLevel']);
                }
                //Swing
                if (array_key_exists('swing', $result['setting'])) {
                    $swingState = false;
                    if ($result['setting']['swing'] == 'ON') {
                        $swingState = true;
                    }
                    $this->SetValue('Swing', $swingState);
                }
                //Vertical swing
                if (array_key_exists('verticalSwing', $result['setting'])) {
                    $this->SetValue('VerticalSwing', (string) $result['setting']['verticalSwing']);
                }
                //Horizontal swing
                if (array_key_exists('horizontalSwing', $result['setting'])) {
                    $this->SetValue('HorizontalSwing', (string) $result['setting']['horizontalSwing']);
                }
                //Light
                if (array_key_exists('light', $result['setting'])) {
                    $lightState = false;
                    if ($result['setting']['light'] == 'ON') {
                        $lightState = true;
                    }
                    $this->SetValue('DisplayLight', $lightState);
                }
            }
            // Timer
            if (array_key_exists('overlay', $result)) {
                $coolingTimer = 0;
                $overlay = $result['overlay'];
                if (is_array($overlay)) {
                    if (array_key_exists('termination', $overlay)) {
                        $termination = $overlay['termination'];
                        if (is_array($termination)) {
                            if (array_key_exists('typeSkillBasedApp', $termination)) {
                                $type = $termination['typeSkillBasedApp'];
                                $this->SendDebug(__FUNCTION__, 'Timer type: ' . $type, 0);
                                if ($type == 'NEXT_TIME_BLOCK') {
                                    $coolingTimer = 1;
                                }
                                if ($type == 'TIMER') {
                                    if (array_key_exists('remainingTimeInSeconds', $termination)) {
                                        $coolingTimer = $termination['remainingTimeInSeconds'];
                                    }
                                }
                            }
                        }
                    }
                }
                $this->SetValue('CoolingTimer', $coolingTimer);
            }
            //Sensor
            if (array_key_exists('sensorDataPoints', $result)) {
                // Inside temperature
                if (array_key_exists('insideTemperature', $result['sensorDataPoints'])) {
                    $this->SetValue('RoomTemperature', (float) $result['sensorDataPoints']['insideTemperature']['celsius']);
                }
                //Humidity
                if (array_key_exists('humidity', $result['sensorDataPoints'])) {
                    $this->SetValue('AirHumidity', (float) $result['sensorDataPoints']['humidity']['percentage']);
                }
            }
        }
        //Set next timer interval
        $this->SetTimerInterval('UpdateCoolingState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
    }

    #################### Private

    /**
     * Checks the parent instance (splitter).
     *
     * @return bool
     */
    private function CheckParent(): bool
    {
        $result = true;
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            $result = false;
        }
        return $result;
    }

    /**
     * Checks for an existing home ID.
     *
     * @return bool
     * @throws Exception
     */
    private function CheckHomeID(): bool
    {
        $result = true;
        if (empty($this->ReadPropertyString('HomeID'))) {
            $this->SendDebug(__FUNCTION__, 'No HomeID assigned in the properties!', 0);
            $result = false;
        }
        return $result;
    }

    /**
     * Checks for an existing zone ID.
     * @return bool
     * @throws Exception
     */
    private function CheckZoneID(): bool
    {
        $result = true;
        if (empty($this->ReadPropertyString('ZoneID'))) {
            $this->SendDebug(__FUNCTION__, 'No ZoneID assigned in the properties!', 0);
            $result = false;
        }
        return $result;
    }
}