<?php
namespace agumil\SatuSehatSDK\Terminology\HL7;

use agumil\SatuSehatSDK\Interface\TerminologyInterface;

class BiologicallyDerivedProductCategory implements TerminologyInterface
{
    const VERSION = '4.0.1';
    const SYSTEM = 'http://hl7.org/fhir/product-category';

    const CODE_ORGAN = 'organ';
    const CODE_TISSUE = 'tissue';
    const CODE_FLUID = 'fluid';
    const CODE_CELLS = 'cells';
    const CODE_BIOLOGICAL_AGENT = 'biologicalAgent';

    public static function getCodes(): array
    {
        return [
            self::CODE_ORGAN,
            self::CODE_TISSUE,
            self::CODE_FLUID,
            self::CODE_CELLS,
            self::CODE_BIOLOGICAL_AGENT,
        ];
    }

    public static function getDisplayCode(string $code): null | string
    {
        $displays = [
            self::CODE_ORGAN => 'Organ',
            self::CODE_TISSUE => 'Tissue',
            self::CODE_FLUID => 'Fluid',
            self::CODE_CELLS => 'Cells',
            self::CODE_BIOLOGICAL_AGENT => 'Biological Agent',
        ];

        return @$displays[$code];
    }
}
