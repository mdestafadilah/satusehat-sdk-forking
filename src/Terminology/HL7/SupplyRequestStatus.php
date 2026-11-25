<?php
namespace agumil\SatuSehatSDK\Terminology\HL7;

use agumil\SatuSehatSDK\Interface\TerminologyInterface;

/**
 * Value set `supplyrequest-status` — binding wajib `SupplyRequest.status`.
 *
 * Berbeda dari `request-status` (dipakai ServiceRequest dkk.): di sini
 * `suspended`/`cancelled` sah, sedangkan `on-hold`/`revoked` tidak ada.
 */
class SupplyRequestStatus implements TerminologyInterface
{
    const VERSION = '4.0.1';
    const SYSTEM = 'http://hl7.org/fhir/supplyrequest-status';

    const CODE_DRAFT = 'draft';
    const CODE_ACTIVE = 'active';
    const CODE_SUSPENDED = 'suspended';
    const CODE_CANCELLED = 'cancelled';
    const CODE_COMPLETED = 'completed';
    const CODE_ENTERED_IN_ERROR = 'entered-in-error';
    const CODE_UNKNOWN = 'unknown';

    public static function getCodes(): array
    {
        return [
            self::CODE_DRAFT,
            self::CODE_ACTIVE,
            self::CODE_SUSPENDED,
            self::CODE_CANCELLED,
            self::CODE_COMPLETED,
            self::CODE_ENTERED_IN_ERROR,
            self::CODE_UNKNOWN,
        ];
    }

    public static function getDisplayCode(string $code): null | string
    {
        $displays = [
            self::CODE_DRAFT => 'Draft',
            self::CODE_ACTIVE => 'Active',
            self::CODE_SUSPENDED => 'Suspended',
            self::CODE_CANCELLED => 'Cancelled',
            self::CODE_COMPLETED => 'Completed',
            self::CODE_ENTERED_IN_ERROR => 'Entered in Error',
            self::CODE_UNKNOWN => 'Unknown',
        ];

        return @$displays[$code];
    }
}
