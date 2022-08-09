<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class SymconTadoValidationTest extends TestCaseSymconValidation
{
    public function testValidateSymconTado(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateModule_AC(): void
    {
        $this->validateModule(__DIR__ . '/../AC');
    }
    public function testValidateConfiguratorModule(): void
    {
        $this->validateModule(__DIR__ . '/../Configurator');
    }

    public function testValidateCoolingModule(): void
    {
        $this->validateModule(__DIR__ . '/../Cooling');
    }

    public function testValidateDeviceModule(): void
    {
        $this->validateModule(__DIR__ . '/../Device');
    }

    public function testValidateHeatingModule(): void
    {
        $this->validateModule(__DIR__ . '/../Heating');
    }

    public function testValidateHomeModule(): void
    {
        $this->validateModule(__DIR__ . '/../Home');
    }

    public function testValidateSplitterModule(): void
    {
        $this->validateModule(__DIR__ . '/../Splitter');
    }
}