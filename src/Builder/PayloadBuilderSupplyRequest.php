<?php
namespace agumil\SatuSehatSDK\Builder;

use agumil\SatuSehatSDK\DataType\CodeableConcept;
use agumil\SatuSehatSDK\DataType\Identifier;
use agumil\SatuSehatSDK\DataType\Period;
use agumil\SatuSehatSDK\DataType\Quantity;
use agumil\SatuSehatSDK\DataType\Reference;
use agumil\SatuSehatSDK\Exception\SSDataTypeException;
use agumil\SatuSehatSDK\Helper\ValidatorHelper;
use agumil\SatuSehatSDK\Terminology\HL7\RequestPriority;
use agumil\SatuSehatSDK\Terminology\HL7\SupplyRequestStatus;

/**
 * Permintaan distribusi produk darah dari fasyankes peminta ke UPD.
 *
 * Status divalidasi dengan `supplyrequest-status` — value set khusus
 * SupplyRequest pada FHIR R4, yang mengenal `suspended`/`cancelled` tetapi
 * tidak mengenal `on-hold`/`revoked` milik `request-status`.
 */
class PayloadBuilderSupplyRequest
{
    private static $resource_type = 'SupplyRequest';

    private array $identifier;

    private string $status;

    private array $category;

    private string $priority;

    private array $item;

    private array $quantity;

    private array $occurrence;

    private string $authored_on;

    private array $requester;

    private array $supplier;

    private array $reason_code;

    private array $reason_reference;

    private array $deliver_from;

    private array $deliver_to;

    public function addIdentifier(Identifier $identifier)
    {
        $this->identifier[] = $identifier->toArray();

        return $this;
    }

    public function setStatus(string $status)
    {
        ValidatorHelper::in('status', $status, SupplyRequestStatus::getCodes());

        $this->status = $status;

        return $this;
    }

    public function setCategory(CodeableConcept $category)
    {
        $this->category = $category->toArray();

        return $this;
    }

    public function setPriority(string $priority)
    {
        ValidatorHelper::in('priority', $priority, RequestPriority::getCodes());

        $this->priority = $priority;

        return $this;
    }

    public function setItemCodeableConcept(CodeableConcept $item)
    {
        $this->assertItemNotSet('itemCodeableConcept');

        $this->item = ['itemCodeableConcept' => $item->toArray()];

        return $this;
    }

    public function setItemReference(Reference $item)
    {
        $this->assertItemNotSet('itemReference');

        $this->item = ['itemReference' => $item->toArray()];

        return $this;
    }

    public function setQuantity(Quantity $quantity)
    {
        $this->quantity = $quantity->toArray();

        return $this;
    }

    public function setOccurrenceDateTime(string $dateTime)
    {
        $this->assertOccurrenceNotSet('occurrenceDateTime');

        $this->occurrence = ['occurrenceDateTime' => ValidatorHelper::dateTime($dateTime)];

        return $this;
    }

    public function setOccurrencePeriod(Period $period)
    {
        $this->assertOccurrenceNotSet('occurrencePeriod');

        $this->occurrence = ['occurrencePeriod' => $period->toArray()];

        return $this;
    }

    public function setAuthoredOn(string $dateTime)
    {
        $this->authored_on = ValidatorHelper::dateTime($dateTime);

        return $this;
    }

    public function setRequester(Reference $requester)
    {
        $this->requester = $requester->toArray();

        return $this;
    }

    public function addSupplier(Reference $supplier)
    {
        $this->supplier[] = $supplier->toArray();

        return $this;
    }

    public function addReasonCode(CodeableConcept $reasonCode)
    {
        $this->reason_code[] = $reasonCode->toArray();

        return $this;
    }

    public function addReasonReference(Reference $reasonReference)
    {
        $this->reason_reference[] = $reasonReference->toArray();

        return $this;
    }

    public function setDeliverFrom(Reference $deliverFrom)
    {
        $this->deliver_from = $deliverFrom->toArray();

        return $this;
    }

    public function setDeliverTo(Reference $deliverTo)
    {
        $this->deliver_to = $deliverTo->toArray();

        return $this;
    }

    public function build(): array
    {
        $data['resourceType'] = self::$resource_type;

        if (!empty($this->identifier)) {
            $data['identifier'] = $this->identifier;
        }

        if (!empty($this->status)) {
            $data['status'] = $this->status;
        }

        if (!empty($this->category)) {
            $data['category'] = $this->category;
        }

        if (!empty($this->priority)) {
            $data['priority'] = $this->priority;
        }

        if (!empty($this->item)) {
            $key = key($this->item);
            $data[$key] = $this->item[$key];
        }

        if (!empty($this->quantity)) {
            $data['quantity'] = $this->quantity;
        }

        if (!empty($this->occurrence)) {
            $key = key($this->occurrence);
            $data[$key] = $this->occurrence[$key];
        }

        if (!empty($this->authored_on)) {
            $data['authoredOn'] = $this->authored_on;
        }

        if (!empty($this->requester)) {
            $data['requester'] = $this->requester;
        }

        if (!empty($this->supplier)) {
            $data['supplier'] = $this->supplier;
        }

        if (!empty($this->reason_code)) {
            $data['reasonCode'] = $this->reason_code;
        }

        if (!empty($this->reason_reference)) {
            $data['reasonReference'] = $this->reason_reference;
        }

        if (!empty($this->deliver_from)) {
            $data['deliverFrom'] = $this->deliver_from;
        }

        if (!empty($this->deliver_to)) {
            $data['deliverTo'] = $this->deliver_to;
        }

        return $data;
    }

    public function buildAsJSON(): string
    {
        return json_encode($this->build());
    }

    private function assertItemNotSet(string $incoming)
    {
        if (!empty($this->item) && !array_key_exists($incoming, $this->item)) {
            $current = key($this->item);
            throw new SSDataTypeException("itemCodeableConcept and itemReference are mutually exclusive; {$current} is already set.");
        }
    }

    private function assertOccurrenceNotSet(string $incoming)
    {
        if (!empty($this->occurrence) && !array_key_exists($incoming, $this->occurrence)) {
            $current = key($this->occurrence);
            throw new SSDataTypeException("occurrenceDateTime and occurrencePeriod are mutually exclusive; {$current} is already set.");
        }
    }
}
