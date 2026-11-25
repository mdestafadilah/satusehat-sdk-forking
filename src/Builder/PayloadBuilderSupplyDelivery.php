<?php
namespace agumil\SatuSehatSDK\Builder;

use agumil\SatuSehatSDK\DataType\CodeableConcept;
use agumil\SatuSehatSDK\DataType\Extension;
use agumil\SatuSehatSDK\DataType\Identifier;
use agumil\SatuSehatSDK\DataType\Period;
use agumil\SatuSehatSDK\DataType\Quantity;
use agumil\SatuSehatSDK\DataType\Reference;
use agumil\SatuSehatSDK\Exception\SSDataTypeException;
use agumil\SatuSehatSDK\Helper\ValidatorHelper;
use agumil\SatuSehatSDK\Terminology\HL7\SupplyDeliveryStatus;

/**
 * Pengiriman produk darah dari UPD ke fasyankes penerima.
 *
 * Profil SATUSEHAT menautkan kantong darah lewat extension
 * `suppliedBiologicallyDerivedProduct`, bukan lewat `suppliedItem.itemReference`,
 * dan mencatat fasyankes penerima lewat extension `receivingOrganization`.
 */
class PayloadBuilderSupplyDelivery
{
    private static $resource_type = 'SupplyDelivery';

    const EXT_SUPPLIED_BIOLOGICALLY_DERIVED_PRODUCT = 'https://fhir.kemkes.go.id/r4/StructureDefinition/SupplyDelivery/suppliedBiologicallyDerivedProduct';

    const EXT_RECEIVING_ORGANIZATION = 'https://fhir.kemkes.go.id/r4/StructureDefinition/SupplyDelivery/receivingOrganization';

    private array $identifier;

    private array $based_on;

    private array $part_of;

    private string $status;

    private array $patient;

    private array $type;

    private array $supplied_item = [];

    private array $occurrence;

    private array $supplier;

    private array $destination;

    private array $receiver;

    private array $extension;

    public function addIdentifier(Identifier $identifier)
    {
        $this->identifier[] = $identifier->toArray();

        return $this;
    }

    public function addBasedOn(Reference $basedOn)
    {
        $this->based_on[] = $basedOn->toArray();

        return $this;
    }

    public function setPartOf(Reference $partOf)
    {
        $this->part_of = $partOf->toArray();

        return $this;
    }

    public function setStatus(string $status)
    {
        ValidatorHelper::in('status', $status, SupplyDeliveryStatus::getCodes());

        $this->status = $status;

        return $this;
    }

    public function setPatient(Reference $patient)
    {
        $this->patient = $patient->toArray();

        return $this;
    }

    public function setType(CodeableConcept $type)
    {
        $this->type = $type->toArray();

        return $this;
    }

    /**
     * Semua setter `suppliedItem` menambah/mengganti bagiannya masing-masing
     * tanpa menghapus bagian lain, sehingga urutan pemanggilan tidak
     * berpengaruh. Versi lama mengganti `suppliedItem` seutuhnya di sini,
     * yang menghapus `itemReference` yang sudah di-set tanpa error.
     *
     * @param Quantity      $quantity      volume yang dikirim (mis. 250 mL)
     * @param Reference|null $product      referensi ke BiologicallyDerivedProduct (kantong darah)
     */
    public function setSuppliedItem(Quantity $quantity, ?Reference $product = null)
    {
        if (isset($product)) {
            $this->supplied_item['extension'] = [
                (new Extension(self::EXT_SUPPLIED_BIOLOGICALLY_DERIVED_PRODUCT, $product->toArray(), 'Reference'))->toArray(),
            ];
        }

        $this->supplied_item['quantity'] = $quantity->toArray();

        return $this;
    }

    public function setSuppliedItemReference(Reference $item)
    {
        $this->assertSuppliedItemChoiceNotSet('itemReference');

        $this->supplied_item['itemReference'] = $item->toArray();

        return $this;
    }

    public function setSuppliedItemCodeableConcept(CodeableConcept $item)
    {
        $this->assertSuppliedItemChoiceNotSet('itemCodeableConcept');

        $this->supplied_item['itemCodeableConcept'] = $item->toArray();

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

    public function setSupplier(Reference $supplier)
    {
        $this->supplier = $supplier->toArray();

        return $this;
    }

    public function setDestination(Reference $destination)
    {
        $this->destination = $destination->toArray();

        return $this;
    }

    public function addReceiver(Reference $receiver)
    {
        $this->receiver[] = $receiver->toArray();

        return $this;
    }

    public function setReceivingOrganization(Reference $organization)
    {
        $this->extension[] = (new Extension(self::EXT_RECEIVING_ORGANIZATION, $organization->toArray(), 'Reference'))->toArray();

        return $this;
    }

    public function addExtension(Extension $extension)
    {
        $this->extension[] = $extension->toArray();

        return $this;
    }

    public function build(): array
    {
        $data['resourceType'] = self::$resource_type;

        if (!empty($this->identifier)) {
            $data['identifier'] = $this->identifier;
        }

        if (!empty($this->based_on)) {
            $data['basedOn'] = $this->based_on;
        }

        if (!empty($this->part_of)) {
            $data['partOf'] = $this->part_of;
        }

        if (!empty($this->status)) {
            $data['status'] = $this->status;
        }

        if (!empty($this->patient)) {
            $data['patient'] = $this->patient;
        }

        if (!empty($this->type)) {
            $data['type'] = $this->type;
        }

        if (!empty($this->supplied_item)) {
            $data['suppliedItem'] = $this->supplied_item;
        }

        if (!empty($this->occurrence)) {
            $key = key($this->occurrence);
            $data[$key] = $this->occurrence[$key];
        }

        if (!empty($this->supplier)) {
            $data['supplier'] = $this->supplier;
        }

        if (!empty($this->destination)) {
            $data['destination'] = $this->destination;
        }

        if (!empty($this->receiver)) {
            $data['receiver'] = $this->receiver;
        }

        if (!empty($this->extension)) {
            $data['extension'] = $this->extension;
        }

        return $data;
    }

    public function buildAsJSON(): string
    {
        return json_encode($this->build());
    }

    private function assertOccurrenceNotSet(string $incoming)
    {
        if (!empty($this->occurrence) && !array_key_exists($incoming, $this->occurrence)) {
            $current = key($this->occurrence);
            throw new SSDataTypeException("occurrenceDateTime and occurrencePeriod are mutually exclusive; {$current} is already set.");
        }
    }

    /**
     * `suppliedItem.item[x]` adalah choice field: hanya satu dari
     * `itemReference`/`itemCodeableConcept` yang boleh ada. Set ulang kunci
     * yang sama tetap diizinkan (semantik replace, konsisten dengan choice
     * field lain di builder ini).
     */
    private function assertSuppliedItemChoiceNotSet(string $incoming)
    {
        foreach (['itemReference', 'itemCodeableConcept'] as $existing) {
            if ($existing !== $incoming && isset($this->supplied_item[$existing])) {
                throw new SSDataTypeException("suppliedItem.itemReference and suppliedItem.itemCodeableConcept are mutually exclusive; {$existing} is already set.");
            }
        }
    }
}
