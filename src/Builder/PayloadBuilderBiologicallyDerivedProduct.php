<?php
namespace agumil\SatuSehatSDK\Builder;

use agumil\SatuSehatSDK\DataType\CodeableConcept;
use agumil\SatuSehatSDK\DataType\Extension;
use agumil\SatuSehatSDK\DataType\Identifier;
use agumil\SatuSehatSDK\DataType\Period;
use agumil\SatuSehatSDK\DataType\Quantity;
use agumil\SatuSehatSDK\DataType\Range;
use agumil\SatuSehatSDK\DataType\Reference;
use agumil\SatuSehatSDK\Exception\SSDataTypeException;
use agumil\SatuSehatSDK\Helper\ValidatorHelper;
use agumil\SatuSehatSDK\Terminology\HL7\BiologicallyDerivedProductCategory;
use agumil\SatuSehatSDK\Terminology\HL7\BiologicallyDerivedProductStatus;
use agumil\SatuSehatSDK\Terminology\HL7\BiologicallyDerivedProductStorageScale;

/**
 * Payload builder untuk komponen darah (kantong darah) pada use case UPD.
 *
 * Profil SATUSEHAT memakai beberapa extension yang tidak ada di FHIR R4 inti:
 * volume kantong, tanggal kedaluwarsa, status detail (didistribusikan /
 * dimusnahkan), serta tanggal dan alasan pemusnahan. Semuanya disediakan
 * lewat method khusus supaya pemanggil tidak perlu menulis URL extension
 * sendiri.
 */
class PayloadBuilderBiologicallyDerivedProduct
{
    private static $resource_type = 'BiologicallyDerivedProduct';

    /**
     * URL extension mengikuti pola `https://fhir.kemkes.go.id/r4/StructureDefinition/...`.
     * Contoh Postman untuk pembuatan resource sempat menulis
     * `.../StructureDefinition/r4/...`, sedangkan contoh update memakai pola
     * yang benar; yang dipakai di sini adalah pola kanonis.
     */
    const EXT_VALUE_QUANTITY = 'https://fhir.kemkes.go.id/StructureDefinition/valueQuantity';

    const EXT_EXPIRATION_DATE = 'https://fhir.kemkes.go.id/r4/StructureDefinition/BiologicallyDerivedProduct/expirationDate';

    const EXT_STATUS_DETAIL = 'https://fhir.kemkes.go.id/r4/StructureDefinition/BiologicallyDerivedProduct/statusDetail';

    const EXT_DESTRUCTION_DATE = 'https://fhir.kemkes.go.id/r4/StructureDefinition/BiologicallyDerivedProduct/destructionDate';

    const EXT_DESTRUCTION_REASON = 'https://fhir.kemkes.go.id/r4/StructureDefinition/BiologicallyDerivedProduct/destructionReason';

    private array $identifier;

    private string $status;

    private string $product_category;

    private array $product_code;

    private array $request;

    private int $quantity;

    private array $parent;

    private array $collection;

    private array $processing;

    private array $manipulation;

    private int $division;

    private array $storage;

    private array $property;

    private array $extension;

    public function addIdentifier(Identifier $identifier)
    {
        $this->identifier[] = $identifier->toArray();

        return $this;
    }

    public function setStatus(string $status)
    {
        ValidatorHelper::in('status', $status, BiologicallyDerivedProductStatus::getCodes());

        $this->status = $status;

        return $this;
    }

    public function setProductCategory(string $productCategory)
    {
        ValidatorHelper::in('productCategory', $productCategory, BiologicallyDerivedProductCategory::getCodes());

        $this->product_category = $productCategory;

        return $this;
    }

    public function setProductCode(CodeableConcept $productCode)
    {
        $this->product_code = $productCode->toArray();

        return $this;
    }

    public function addRequest(Reference $request)
    {
        $this->request[] = $request->toArray();

        return $this;
    }

    /**
     * Jumlah unit dalam satu resource. Untuk kantong darah tunggal nilainya 1;
     * volume fisik kantong diisi lewat setValueQuantity().
     */
    public function setQuantity(int $quantity)
    {
        ValidatorHelper::positiveInt('quantity', $quantity);

        $this->quantity = $quantity;

        return $this;
    }

    public function addParent(Reference $parent)
    {
        $this->parent[] = $parent->toArray();

        return $this;
    }

    public function setCollectionSource(Reference $source)
    {
        $this->collection['source'] = $source->toArray();

        return $this;
    }

    public function setCollectionCollector(Reference $collector)
    {
        $this->collection['collector'] = $collector->toArray();

        return $this;
    }

    public function setCollectedDateTime(string $dateTime)
    {
        $this->assertCollectedNotSet('collectedDateTime');

        $this->collection['collectedDateTime'] = ValidatorHelper::dateTime($dateTime);

        return $this;
    }

    public function setCollectedPeriod(Period $period)
    {
        $this->assertCollectedNotSet('collectedPeriod');

        $this->collection['collectedPeriod'] = $period->toArray();

        return $this;
    }

    public function setCollectionQuantity(Quantity $quantity)
    {
        $this->collection['quantity'] = $quantity->toArray();

        return $this;
    }

    public function setCollectionProcedure(CodeableConcept $procedure)
    {
        $this->collection['procedure'] = $procedure->toArray();

        return $this;
    }

    public function setCollectionBodySite(CodeableConcept $bodySite)
    {
        $this->collection['bodySite'] = $bodySite->toArray();

        return $this;
    }

    public function setCollectionFastingStatus(CodeableConcept $fastingStatus)
    {
        $this->collection['fastingStatus'] = $fastingStatus->toArray();

        return $this;
    }

    /**
     * `processing.additive` pada R4 adalah Reference(Substance), bukan
     * CodeableConcept — zat aditif dirujuk sebagai resource, bukan kode.
     */
    public function addProcessing(?CodeableConcept $procedure = null, ?string $timeDateTime = null, ?string $description = null, ?Reference $additive = null)
    {
        $data = [];

        if (isset($description)) {
            $data['description'] = $description;
        }

        if (isset($procedure)) {
            $data['procedure'] = $procedure->toArray();
        }

        if (isset($additive)) {
            $data['additive'] = $additive->toArray();
        }

        if (isset($timeDateTime)) {
            $data['timeDateTime'] = ValidatorHelper::dateTime($timeDateTime);
        }

        $this->processing[] = $data;

        return $this;
    }

    /**
     * `manipulation` pada R4 hanya punya `description` dan `time[x]` —
     * tidak ada `procedure`/`additive` seperti pada `processing`.
     */
    public function setManipulation(?string $description = null, ?string $timeDateTime = null)
    {
        $data = [];

        if (isset($description)) {
            $data['description'] = $description;
        }

        if (isset($timeDateTime)) {
            $data['timeDateTime'] = ValidatorHelper::dateTime($timeDateTime);
        }

        $this->manipulation = $data;

        return $this;
    }

    public function setDivision(int $division)
    {
        ValidatorHelper::positiveInt('division', $division);

        $this->division = $division;

        return $this;
    }

    /**
     * Tipe mengikuti R4: `temperature` adalah decimal polos (satuannya
     * dinyatakan lewat `scale`, bukan Quantity), `scale` terikat value set
     * `product-storage-scale`, dan `duration` adalah Period.
     *
     * @param int|float|null $temperature suhu simpan, mis. -30 untuk plasma
     * @param string|null    $scale       farenheit|celsius|kelvin
     */
    public function addStorage(?string $description = null, int | float | null $temperature = null, ?string $scale = null, ?Period $duration = null)
    {
        $data = [];

        if (isset($description)) {
            $data['description'] = $description;
        }

        if (isset($temperature)) {
            $data['temperature'] = $temperature;
        }

        if (isset($scale)) {
            ValidatorHelper::in('scale', $scale, BiologicallyDerivedProductStorageScale::getCodes());

            $data['scale'] = $scale;
        }

        if (isset($duration)) {
            $data['duration'] = $duration->toArray();
        }

        $this->storage[] = $data;

        return $this;
    }

    /**
     * Properti tambahan komponen darah. Nilai boleh CodeableConcept, Quantity,
     * Range, atau boolean sesuai tipe `value[x]` pada FHIR R4.
     */
    public function addProperty(string $type, CodeableConcept | Quantity | Range | bool $value)
    {
        $data['type'] = $type;

        if ($value instanceof CodeableConcept) {
            $data['valueCodeableConcept'] = $value->toArray();
        } elseif ($value instanceof Quantity) {
            $data['valueQuantity'] = $value->toArray();
        } elseif ($value instanceof Range) {
            $data['valueRange'] = $value->toArray();
        } else {
            $data['valueBoolean'] = $value;
        }

        $this->property[] = $data;

        return $this;
    }

    public function setValueQuantity(Quantity $quantity)
    {
        $this->extension[] = (new Extension(self::EXT_VALUE_QUANTITY, $quantity->toArray(), 'Quantity'))->toArray();

        return $this;
    }

    public function setExpirationDate(string $dateTime)
    {
        $this->extension[] = (new Extension(self::EXT_EXPIRATION_DATE, ValidatorHelper::dateTime($dateTime), 'DateTime'))->toArray();

        return $this;
    }

    /**
     * Status detail dipakai saat kantong keluar dari stok, mis. 117074003
     * (ditransfer ke fasyankes lain) atau 117070007 (dimusnahkan).
     */
    public function setStatusDetail(CodeableConcept $statusDetail)
    {
        $this->extension[] = (new Extension(self::EXT_STATUS_DETAIL, $statusDetail->toArray(), 'CodeableConcept'))->toArray();

        return $this;
    }

    public function setDestruction(string $dateTime, ?CodeableConcept $reason = null)
    {
        $this->extension[] = (new Extension(self::EXT_DESTRUCTION_DATE, ValidatorHelper::dateTime($dateTime), 'DateTime'))->toArray();

        if (isset($reason)) {
            $this->extension[] = (new Extension(self::EXT_DESTRUCTION_REASON, $reason->toArray(), 'CodeableConcept'))->toArray();
        }

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

        if (!empty($this->status)) {
            $data['status'] = $this->status;
        }

        if (!empty($this->product_category)) {
            $data['productCategory'] = $this->product_category;
        }

        if (!empty($this->product_code)) {
            $data['productCode'] = $this->product_code;
        }

        if (!empty($this->request)) {
            $data['request'] = $this->request;
        }

        if (!empty($this->quantity)) {
            $data['quantity'] = $this->quantity;
        }

        if (!empty($this->parent)) {
            $data['parent'] = $this->parent;
        }

        if (!empty($this->collection)) {
            $data['collection'] = $this->collection;
        }

        if (!empty($this->processing)) {
            $data['processing'] = $this->processing;
        }

        if (!empty($this->manipulation)) {
            $data['manipulation'] = $this->manipulation;
        }

        if (!empty($this->division)) {
            $data['division'] = $this->division;
        }

        if (!empty($this->storage)) {
            $data['storage'] = $this->storage;
        }

        if (!empty($this->property)) {
            $data['property'] = $this->property;
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

    /**
     * `collectedDateTime` dan `collectedPeriod` adalah pilihan (choice) pada
     * FHIR: hanya satu yang boleh dikirim. Ditolak di sini supaya kesalahan
     * terlihat saat menyusun payload, bukan sebagai 400 dari server.
     */
    private function assertCollectedNotSet(string $incoming)
    {
        $current = isset($this->collection['collectedDateTime']) ? 'collectedDateTime' : (isset($this->collection['collectedPeriod']) ? 'collectedPeriod' : null);

        if ($current !== null && $current !== $incoming) {
            throw new SSDataTypeException("collectedDateTime and collectedPeriod are mutually exclusive; {$current} is already set.");
        }
    }
}
