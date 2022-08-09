<?php

/**
 * @project       tado° AC
 * @file          AC_Properties.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpConditionAlreadyCheckedInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait AC_Properties
{
    /**
     * Sets the device properties.
     *
     * @param int $DeviceType
     * 0 = Default
     * 1 = Fujitsu AUYG07LVLA
     * 2 = Fujitsu ASYG09LMCA
     * 3 = LG Standard Plus PC12SQ
     * 4 = Comfee MS11M6-12
     *
     * @return void
     */
    public function SetDeviceModeProperties(int $DeviceType): void
    {
        $this->SendDebug(__FUNCTION__, 'was executed', 0);
        $this->SendDebug(__FUNCTION__, 'DeviceType: ' . $DeviceType, 0);

        switch ($DeviceType) {
            case 1: //Fujitsu AUYG07LVLA
            case 2: //Fujitsu ASYG09LMCA
                $fanSpeed = true;
                $fanLevel = false;
                $swing = true;
                $verticalSwing = false;
                $horizontalSwing = false;
                $light = false;
                //Device mode
                //Cool
                $useCoolTemperature = true;
                $useCoolFan = true;
                $useCoolSwing = true;
                $useCoolLight = false;
                //Heat
                $useHeatTemperature = true;
                $useHeatFan = true;
                $useHeatSwing = true;
                $useHeatLight = false;
                //Dry
                $useDryTemperature = false;
                $useDryFan = false;
                $useDrySwing = true;
                $useDryLight = false;
                //Fan
                $useFanTemperature = false;
                $useFan = true;
                $useFanSwing = true;
                $useFanLight = false;
                //Auto
                $useAutoTemperature = true;
                $useAutoFan = true;
                $useAutoSwing = true;
                $useAutoLight = false;
                break;

            case 3: //LG Standard Plus PC12SQ
                $fanSpeed = true;
                $fanLevel = false;
                $swing = false;
                $verticalSwing = false;
                $horizontalSwing = false;
                $light = false;
                //Device mode
                //Cool
                $useCoolTemperature = true;
                $useCoolFan = true;
                $useCoolSwing = false;
                $useCoolLight = false;
                //Heat
                $useHeatTemperature = true;
                $useHeatFan = true;
                $useHeatSwing = false;
                $useHeatLight = false;
                //Dry
                $useDryTemperature = false;
                $useDryFan = false;
                $useDrySwing = false;
                $useDryLight = false;
                //Fan
                $useFanTemperature = false;
                $useFan = false;
                $useFanSwing = false;
                $useFanLight = false;
                //Auto
                $useAutoTemperature = true;
                $useAutoFan = true;
                $useAutoSwing = false;
                $useAutoLight = false;
                break;

            case 4: //Comfee MS11M6-12
                $fanSpeed = false;
                $fanLevel = true;
                $swing = false;
                $verticalSwing = true;
                $horizontalSwing = true;
                $light = true;
                //Device mode
                //Cool
                $useCoolTemperature = true;
                $useCoolFan = true;
                $useCoolSwing = true;
                $useCoolLight = true;
                //Heat
                $useHeatTemperature = true;
                $useHeatFan = true;
                $useHeatSwing = true;
                $useHeatLight = true;
                //Dry
                $useDryTemperature = false;
                $useDryFan = false;
                $useDrySwing = false;
                $useDryLight = false;
                //Fan
                $useFanTemperature = false;
                $useFan = false;
                $useFanSwing = false;
                $useFanLight = false;
                //Auto
                $useAutoTemperature = true;
                $useAutoFan = false;
                $useAutoSwing = false;
                $useAutoLight = false;
                break;

            default: //Default
                $fanSpeed = true;
                $fanLevel = false;
                $swing = false;
                $verticalSwing = false;
                $horizontalSwing = false;
                $light = false;
                //Device mode
                //Cool
                $useCoolTemperature = true;
                $useCoolFan = true;
                $useCoolSwing = false;
                $useCoolLight = false;
                //Heat
                $useHeatTemperature = true;
                $useHeatFan = true;
                $useHeatSwing = false;
                $useHeatLight = false;
                //Dry
                $useDryTemperature = false;
                $useDryFan = false;
                $useDrySwing = false;
                $useDryLight = false;
                //Fan
                $useFanTemperature = false;
                $useFan = false;
                $useFanSwing = false;
                $useFanLight = false;
                //Auto
                $useAutoTemperature = true;
                $useAutoFan = true;
                $useAutoSwing = false;
                $useAutoLight = false;
        }

        //Device properties
        IPS_SetProperty($this->InstanceID, 'UseFanSpeed', $fanSpeed);
        IPS_SetProperty($this->InstanceID, 'UseFanLevel', $fanLevel);
        IPS_SetProperty($this->InstanceID, 'UseSwing', $swing);
        IPS_SetProperty($this->InstanceID, 'UseVerticalSwing', $verticalSwing);
        IPS_SetProperty($this->InstanceID, 'UseHorizontalSwing', $horizontalSwing);
        IPS_SetProperty($this->InstanceID, 'UseDisplayLight', $light);

        //Device mode
        IPS_SetProperty($this->InstanceID, 'UseCoolTemperature', $useCoolTemperature);
        IPS_SetProperty($this->InstanceID, 'UseCoolFan', $useCoolFan);
        IPS_SetProperty($this->InstanceID, 'UseCoolSwing', $useCoolSwing);
        IPS_SetProperty($this->InstanceID, 'UseCoolLight', $useCoolLight);
        IPS_SetProperty($this->InstanceID, 'UseHeatTemperature', $useHeatTemperature);
        IPS_SetProperty($this->InstanceID, 'UseHeatFan', $useHeatFan);
        IPS_SetProperty($this->InstanceID, 'UseHeatSwing', $useHeatSwing);
        IPS_SetProperty($this->InstanceID, 'UseHeatLight', $useHeatLight);
        IPS_SetProperty($this->InstanceID, 'UseDryTemperature', $useDryTemperature);
        IPS_SetProperty($this->InstanceID, 'UseDryFan', $useDryFan);
        IPS_SetProperty($this->InstanceID, 'UseDrySwing', $useDrySwing);
        IPS_SetProperty($this->InstanceID, 'UseDryLight', $useDryLight);
        IPS_SetProperty($this->InstanceID, 'UseFanTemperature', $useFanTemperature);
        IPS_SetProperty($this->InstanceID, 'UseFan', $useFan);
        IPS_SetProperty($this->InstanceID, 'UseFanSwing', $useFanSwing);
        IPS_SetProperty($this->InstanceID, 'UseFanLight', $useFanLight);
        IPS_SetProperty($this->InstanceID, 'UseAutoTemperature', $useAutoTemperature);
        IPS_SetProperty($this->InstanceID, 'UseAutoFan', $useAutoFan);
        IPS_SetProperty($this->InstanceID, 'UseAutoSwing', $useAutoSwing);
        IPS_SetProperty($this->InstanceID, 'UseAutoLight', $useAutoLight);

        //Apply changes
        if (IPS_HasChanges($this->InstanceID)) {
            IPS_ApplyChanges($this->InstanceID);
        }
    }
}