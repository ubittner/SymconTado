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
        $this->UpdateFormField('UseFanSpeed', 'value', $fanSpeed);
        $this->UpdateFormField('UseFanLevel', 'value', $fanLevel);
        $this->UpdateFormField('UseSwing', 'value', $swing);
        $this->UpdateFormField('UseVerticalSwing', 'value', $verticalSwing);
        $this->UpdateFormField('UseHorizontalSwing', 'value', $horizontalSwing);
        $this->UpdateFormField('UseDisplayLight', 'value', $light);

        //Device mode
        $this->UpdateFormField('UseCoolTemperature', 'value', $useCoolTemperature);
        $this->UpdateFormField('UseCoolFan', 'value', $useCoolFan);
        $this->UpdateFormField('UseCoolSwing', 'value', $useCoolSwing);
        $this->UpdateFormField('UseCoolLight', 'value', $useCoolLight);
        $this->UpdateFormField('UseHeatTemperature', 'value', $useHeatTemperature);
        $this->UpdateFormField('UseHeatFan', 'value', $useHeatFan);
        $this->UpdateFormField('UseHeatSwing', 'value', $useHeatSwing);
        $this->UpdateFormField('UseHeatLight', 'value', $useHeatLight);
        $this->UpdateFormField('UseDryTemperature', 'value', $useDryTemperature);
        $this->UpdateFormField('UseDryFan', 'value', $useDryFan);
        $this->UpdateFormField('UseDrySwing', 'value', $useDrySwing);
        $this->UpdateFormField('UseDryLight', 'value', $useDryLight);
        $this->UpdateFormField('UseFanTemperature', 'value', $useFanTemperature);
        $this->UpdateFormField('UseFan', 'value', $useFan);
        $this->UpdateFormField('UseFanSwing', 'value', $useFanSwing);
        $this->UpdateFormField('UseFanLight', 'value', $useFanLight);
        $this->UpdateFormField('UseAutoTemperature', 'value', $useAutoTemperature);
        $this->UpdateFormField('UseAutoFan', 'value', $useAutoFan);
        $this->UpdateFormField('UseAutoSwing', 'value', $useAutoSwing);
        $this->UpdateFormField('UseAutoLight', 'value', $useAutoLight);
    }
}