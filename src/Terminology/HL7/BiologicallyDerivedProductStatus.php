<?php
namespace agumil\SatuSehatSDK\Terminology\HL7;

use agumil\SatuSehatSDK\Interface\TerminologyInterface;

class BiologicallyDerivedProductStatus implements TerminologyInterface
{
    const VERSION = '4.0.1';
    const SYSTEM = 'http://hl7.org/fhir/product-status';

    const CODE_AVAILABLE = 'available';
    const CODE_UNAVAILABLE = 'unavailable';

    public static function getCodes(): array
    {
        return [
            self::CODE_AVAILABLE,
            self::CODE_UNAVAILABLE,
        ];
    }

    public static function getDisplayCode(string $code): null | string
    {
        $displays = [
            self::CODE_AVAILABLE => 'Available',
            self::CODE_UNAVAILABLE => 'Unavailable',
        ];

        return @$displays[$code];
    }
}
