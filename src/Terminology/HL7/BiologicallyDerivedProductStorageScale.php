<?php
namespace agumil\SatuSehatSDK\Terminology\HL7;

use agumil\SatuSehatSDK\Interface\TerminologyInterface;

/**
 * Value set `product-storage-scale` — binding wajib
 * `BiologicallyDerivedProduct.storage.scale`.
 *
 * Kode `farenheit` memang dieja begitu (bukan `fahrenheit`) di FHIR R4;
 * yang baku adalah kodenya, display-nya yang memakai ejaan benar.
 */
class BiologicallyDerivedProductStorageScale implements TerminologyInterface
{
    const VERSION = '4.0.1';
    const SYSTEM = 'http://hl7.org/fhir/product-storage-scale';

    const CODE_FARENHEIT = 'farenheit';
    const CODE_CELSIUS = 'celsius';
    const CODE_KELVIN = 'kelvin';

    public static function getCodes(): array
    {
        return [
            self::CODE_FARENHEIT,
            self::CODE_CELSIUS,
            self::CODE_KELVIN,
        ];
    }

    public static function getDisplayCode(string $code): null | string
    {
        $displays = [
            self::CODE_FARENHEIT => 'Fahrenheit',
            self::CODE_CELSIUS => 'Celsius',
            self::CODE_KELVIN => 'Kelvin',
        ];

        return @$displays[$code];
    }
}
