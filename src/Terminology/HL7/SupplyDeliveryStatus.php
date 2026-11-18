<?php
namespace agumil\SatuSehatSDK\Terminology\HL7;

use agumil\SatuSehatSDK\Interface\TerminologyInterface;

class SupplyDeliveryStatus implements TerminologyInterface
{
    const VERSION = '4.0.1';
    const SYSTEM = 'http://hl7.org/fhir/supplydelivery-status';

    const CODE_IN_PROGRESS = 'in-progress';
    const CODE_COMPLETED = 'completed';
    const CODE_ABANDONED = 'abandoned';
    const CODE_ENTERED_IN_ERROR = 'entered-in-error';

    public static function getCodes(): array
    {
        return [
            self::CODE_IN_PROGRESS,
            self::CODE_COMPLETED,
            self::CODE_ABANDONED,
            self::CODE_ENTERED_IN_ERROR,
        ];
    }

    public static function getDisplayCode(string $code): null | string
    {
        $displays = [
            self::CODE_IN_PROGRESS => 'In Progress',
            self::CODE_COMPLETED => 'Delivered',
            self::CODE_ABANDONED => 'Abandoned',
            self::CODE_ENTERED_IN_ERROR => 'Entered In Error',
        ];

        return @$displays[$code];
    }
}
