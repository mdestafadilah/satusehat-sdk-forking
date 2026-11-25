<?php
namespace agumil\SatuSehatSDK\Tests\Builder;

use agumil\SatuSehatSDK\Builder\PayloadBuilderBiologicallyDerivedProduct;
use agumil\SatuSehatSDK\Builder\PayloadBuilderSupplyDelivery;
use agumil\SatuSehatSDK\Builder\PayloadBuilderSupplyRequest;
use agumil\SatuSehatSDK\DataType\CodeableConcept;
use agumil\SatuSehatSDK\DataType\Coding;
use agumil\SatuSehatSDK\DataType\Identifier;
use agumil\SatuSehatSDK\DataType\Quantity;
use agumil\SatuSehatSDK\DataType\Reference;
use agumil\SatuSehatSDK\Exception\SSDataTypeException;
use agumil\SatuSehatSDK\Terminology\HL7\BiologicallyDerivedProductCategory;
use agumil\SatuSehatSDK\Terminology\HL7\BiologicallyDerivedProductStatus;
use agumil\SatuSehatSDK\Terminology\HL7\SupplyDeliveryStatus;
use agumil\SatuSehatSDK\Terminology\HL7\SupplyRequestStatus;
use agumil\SatuSehatSDK\Terminology\KemKes\IdentifierSystem;
use PHPUnit\Framework\TestCase;

/**
 * Payload di bawah disalin dari Postman "52. Use Case - UPD" (folder 07 dan 08)
 * lalu disusun ulang memakai builder. Test ini menjaga agar keluaran builder
 * tetap sama dengan contoh resmi, karena perbedaan nama field sekecil apa pun
 * akan ditolak validator SATUSEHAT.
 */
class PayloadBuilderUpdTest extends TestCase
{
    public function testBloodComponentPayloadMatchesUpdUseCase(): void
    {
        $builder = new PayloadBuilderBiologicallyDerivedProduct();

        $builder->addIdentifier(new Identifier(
            IdentifierSystem::bloodBagNumber(),
            'official',
            'GCO-26-A0123-WB',
            null,
            null,
            new Reference('Organization/10000004')
        ));
        $builder->setStatus(BiologicallyDerivedProductStatus::CODE_AVAILABLE);
        $builder->setProductCategory(BiologicallyDerivedProductCategory::CODE_FLUID);
        $builder->setProductCode(new CodeableConcept(null, new Coding('http://snomed.info/sct', '12289007', 'Whole blood product')));
        $builder->setCollectionSource(new Reference('Patient/100000030006'));
        $builder->setCollectedDateTime('2025-06-04T08:55:00+00:00');
        $builder->addProcessing(null, '2025-06-04T09:15:00+00:00');
        $builder->setQuantity(1);
        $builder->setValueQuantity(new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL'));
        $builder->setExpirationDate('2026-10-10T23:59:00+07:00');

        $expected = [
            'resourceType' => 'BiologicallyDerivedProduct',
            'identifier' => [[
                'system' => 'http://sys-ids.kemkes.go.id/biologicallyderivedproduct/blood-bag-number',
                'use' => 'official',
                'value' => 'GCO-26-A0123-WB',
                'assigner' => ['reference' => 'Organization/10000004'],
            ]],
            'status' => 'available',
            'productCategory' => 'fluid',
            'productCode' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '12289007',
                    'display' => 'Whole blood product',
                ]],
            ],
            'quantity' => 1,
            'collection' => [
                'source' => ['reference' => 'Patient/100000030006'],
                'collectedDateTime' => '2025-06-04T08:55:00+00:00',
            ],
            'processing' => [[
                'timeDateTime' => '2025-06-04T09:15:00+00:00',
            ]],
            'extension' => [
                [
                    'url' => 'https://fhir.kemkes.go.id/StructureDefinition/valueQuantity',
                    'valueQuantity' => [
                        'system' => 'http://unitsofmeasure.org',
                        'code' => 'mL',
                        'value' => 250,
                        'unit' => 'mL',
                    ],
                ],
                [
                    'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/BiologicallyDerivedProduct/expirationDate',
                    'valueDateTime' => '2026-10-10T23:59:00+07:00',
                ],
            ],
        ];

        $this->assertEquals($expected, $builder->build());
    }

    public function testDestroyedBloodComponentCarriesDestructionExtensions(): void
    {
        $builder = new PayloadBuilderBiologicallyDerivedProduct();

        $builder->setStatus(BiologicallyDerivedProductStatus::CODE_UNAVAILABLE);
        $builder->setStatusDetail(new CodeableConcept(null, new Coding('http://snomed.info/sct', '117070007', 'Blood product unit destroyed')));
        $builder->setDestruction('2026-10-12T09:00:00+00:00', new CodeableConcept(null, new Coding('http://snomed.info/sct', '117073009', 'Blood product unit expired')));

        $payload = $builder->build();

        $this->assertCount(3, $payload['extension']);
        $this->assertSame(
            'https://fhir.kemkes.go.id/r4/StructureDefinition/BiologicallyDerivedProduct/destructionDate',
            $payload['extension'][1]['url']
        );
        $this->assertSame(
            'https://fhir.kemkes.go.id/r4/StructureDefinition/BiologicallyDerivedProduct/destructionReason',
            $payload['extension'][2]['url']
        );
    }

    public function testCollectedDateTimeAndPeriodAreMutuallyExclusive(): void
    {
        $builder = new PayloadBuilderBiologicallyDerivedProduct();
        $builder->setCollectedDateTime('2025-06-04T08:55:00+00:00');

        $this->expectException(SSDataTypeException::class);

        $builder->setCollectedPeriod(new \agumil\SatuSehatSDK\DataType\Period('2025-06-04T08:00:00+00:00', '2025-06-04T09:00:00+00:00'));
    }

    public function testUnknownProductStatusIsRejected(): void
    {
        $builder = new PayloadBuilderBiologicallyDerivedProduct();

        $this->expectException(SSDataTypeException::class);

        $builder->setStatus('in-stock');
    }

    public function testSupplyRequestPayloadMatchesUpdUseCase(): void
    {
        $builder = new PayloadBuilderSupplyRequest();

        $builder->addIdentifier(new Identifier(
            IdentifierSystem::supplyrequest('10000004'),
            null,
            'SR0000001'
        ));
        $builder->setStatus(SupplyRequestStatus::CODE_ACTIVE);
        $builder->setItemCodeableConcept(new CodeableConcept(null, new Coding('http://snomed.info/sct', '116205004', 'Blood bag')));
        $builder->setQuantity(new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL'));
        $builder->setAuthoredOn('2025-08-27T14:00:00+00:00');
        $builder->setRequester(new Reference('Organization/100025610', null, 'RSUP Dr. Sardjito'));
        $builder->addSupplier(new Reference('Organization/10000004', null, 'PMI Kota Yogyakarta'));
        $builder->addReasonCode(new CodeableConcept('Kebutuhan kantong darah untuk pasien'));

        $expected = [
            'resourceType' => 'SupplyRequest',
            'identifier' => [[
                'system' => 'http://sys-ids.kemkes.go.id/supplyrequest/10000004',
                'value' => 'SR0000001',
            ]],
            'status' => 'active',
            'itemCodeableConcept' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '116205004',
                    'display' => 'Blood bag',
                ]],
            ],
            'quantity' => [
                'system' => 'http://unitsofmeasure.org',
                'code' => 'mL',
                'value' => 250,
                'unit' => 'mL',
            ],
            'authoredOn' => '2025-08-27T14:00:00+00:00',
            'requester' => [
                'reference' => 'Organization/100025610',
                'display' => 'RSUP Dr. Sardjito',
            ],
            'supplier' => [[
                'reference' => 'Organization/10000004',
                'display' => 'PMI Kota Yogyakarta',
            ]],
            'reasonCode' => [[
                'text' => 'Kebutuhan kantong darah untuk pasien',
            ]],
        ];

        $this->assertEquals($expected, $builder->build());
    }

    public function testSupplyDeliveryPayloadMatchesUpdUseCase(): void
    {
        $builder = new PayloadBuilderSupplyDelivery();

        $builder->addIdentifier(new Identifier(
            IdentifierSystem::supplydelivery('10000004'),
            null,
            'SD0000001'
        ));
        $builder->addBasedOn(new Reference('SupplyRequest/sr-1'));
        $builder->setStatus(SupplyDeliveryStatus::CODE_COMPLETED);
        $builder->setSuppliedItem(
            new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL'),
            new Reference('BiologicallyDerivedProduct/bdp-1', null, 'Kantong darah GCO-26-A0123-WB')
        );
        $builder->setOccurrenceDateTime('2025-08-27T14:00:00+00:00');
        $builder->setSupplier(new Reference('Organization/10000004', null, 'PMI Kota Yogyakarta'));
        $builder->addReceiver(new Reference('Practitioner/nakes-1', null, 'dr. Dewi'));
        $builder->setReceivingOrganization(new Reference('Organization/100025610', null, 'RSUP Dr. Sardjito'));

        $expected = [
            'resourceType' => 'SupplyDelivery',
            'identifier' => [[
                'system' => 'http://sys-ids.kemkes.go.id/supplydelivery/10000004',
                'value' => 'SD0000001',
            ]],
            'basedOn' => [[
                'reference' => 'SupplyRequest/sr-1',
            ]],
            'status' => 'completed',
            'suppliedItem' => [
                'extension' => [[
                    'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/SupplyDelivery/suppliedBiologicallyDerivedProduct',
                    'valueReference' => [
                        'reference' => 'BiologicallyDerivedProduct/bdp-1',
                        'display' => 'Kantong darah GCO-26-A0123-WB',
                    ],
                ]],
                'quantity' => [
                    'system' => 'http://unitsofmeasure.org',
                    'code' => 'mL',
                    'value' => 250,
                    'unit' => 'mL',
                ],
            ],
            'occurrenceDateTime' => '2025-08-27T14:00:00+00:00',
            'supplier' => [
                'reference' => 'Organization/10000004',
                'display' => 'PMI Kota Yogyakarta',
            ],
            'receiver' => [[
                'reference' => 'Practitioner/nakes-1',
                'display' => 'dr. Dewi',
            ]],
            'extension' => [[
                'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/SupplyDelivery/receivingOrganization',
                'valueReference' => [
                    'reference' => 'Organization/100025610',
                    'display' => 'RSUP Dr. Sardjito',
                ],
            ]],
        ];

        $this->assertEquals($expected, $builder->build());
    }

    public function testBuildAsJsonProducesValidJson(): void
    {
        $builder = new PayloadBuilderSupplyRequest();
        $builder->setStatus(SupplyRequestStatus::CODE_ACTIVE);

        $decoded = json_decode($builder->buildAsJSON(), true);

        $this->assertSame('SupplyRequest', $decoded['resourceType']);
    }

    /**
     * Temuan N14: status divalidasi dengan `supplyrequest-status`, bukan
     * `request-status`. `suspended`/`cancelled` sah untuk SupplyRequest,
     * sedangkan `on-hold`/`revoked` tidak.
     */
    public function testSupplyRequestAcceptsSupplyRequestStatusCodes(): void
    {
        $builder = new PayloadBuilderSupplyRequest();

        $builder->setStatus(SupplyRequestStatus::CODE_SUSPENDED);
        $this->assertSame('suspended', $builder->build()['status']);

        $builder->setStatus(SupplyRequestStatus::CODE_CANCELLED);
        $this->assertSame('cancelled', $builder->build()['status']);
    }

    public function testSupplyRequestRejectsRequestStatusOnlyCodes(): void
    {
        $builder = new PayloadBuilderSupplyRequest();

        $this->expectException(SSDataTypeException::class);

        $builder->setStatus('on-hold');
    }

    /**
     * Temuan N17: `setSuppliedItem()` dulu mengganti `suppliedItem` seutuhnya,
     * menghapus `itemReference` yang sudah di-set tanpa error. Kini setiap
     * setter hanya menyentuh bagiannya, jadi urutan tidak berpengaruh.
     */
    public function testSuppliedItemSettersAreOrderIndependent(): void
    {
        $quantity = new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL');
        $item = new Reference('Device/item-1');

        $referenceFirst = new PayloadBuilderSupplyDelivery();
        $referenceFirst->setSuppliedItemReference($item);
        $referenceFirst->setSuppliedItem($quantity);

        $quantityFirst = new PayloadBuilderSupplyDelivery();
        $quantityFirst->setSuppliedItem($quantity);
        $quantityFirst->setSuppliedItemReference($item);

        $expected = [
            'itemReference' => ['reference' => 'Device/item-1'],
            'quantity' => [
                'system' => 'http://unitsofmeasure.org',
                'code' => 'mL',
                'value' => 250,
                'unit' => 'mL',
            ],
        ];

        $this->assertEquals($expected, $referenceFirst->build()['suppliedItem']);
        $this->assertEquals($expected, $quantityFirst->build()['suppliedItem']);
    }

    /**
     * Temuan N16: tipe backbone `storage`/`processing`/`manipulation` kini
     * mengikuti R4 — `temperature` decimal polos, `scale` terikat
     * `product-storage-scale`, `duration` berupa Period, `additive` berupa
     * Reference(Substance), dan `manipulation` hanya description + time[x].
     */
    public function testStorageAndProcessingFollowR4Datatypes(): void
    {
        $builder = new PayloadBuilderBiologicallyDerivedProduct();

        $builder->addStorage(
            'Simpan beku',
            -30,
            'celsius',
            new \agumil\SatuSehatSDK\DataType\Period('2025-06-04T10:00:00+00:00', '2025-06-10T10:00:00+00:00')
        );
        $builder->addProcessing(null, null, 'Penambahan antikoagulan', new Reference('Substance/cpda-1'));
        $builder->setManipulation('Pemisahan plasma', '2025-06-04T11:00:00+00:00');

        $payload = $builder->build();

        $this->assertSame([
            'description' => 'Simpan beku',
            'temperature' => -30,
            'scale' => 'celsius',
            'duration' => [
                'start' => '2025-06-04T10:00:00+00:00',
                'end' => '2025-06-10T10:00:00+00:00',
            ],
        ], $payload['storage'][0]);

        $this->assertSame([
            'description' => 'Penambahan antikoagulan',
            'additive' => ['reference' => 'Substance/cpda-1'],
        ], $payload['processing'][0]);

        $this->assertSame([
            'description' => 'Pemisahan plasma',
            'timeDateTime' => '2025-06-04T11:00:00+00:00',
        ], $payload['manipulation']);
    }

    public function testStorageScaleOutsideValueSetIsRejected(): void
    {
        $builder = new PayloadBuilderBiologicallyDerivedProduct();

        $this->expectException(SSDataTypeException::class);

        $builder->addStorage(null, 4, 'reaumur');
    }

    public function testSuppliedItemChoiceIsMutuallyExclusive(): void
    {
        $builder = new PayloadBuilderSupplyDelivery();
        $builder->setSuppliedItemReference(new Reference('Device/item-1'));

        $this->expectException(SSDataTypeException::class);

        $builder->setSuppliedItemCodeableConcept(new CodeableConcept(null, new Coding('http://snomed.info/sct', '116205004', 'Blood bag')));
    }
}
