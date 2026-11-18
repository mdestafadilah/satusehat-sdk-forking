<?php require __DIR__ . '/../../../Auth/oauth2.php';

use agumil\SatuSehatSDK\Builder\PayloadBuilderBiologicallyDerivedProduct;
use agumil\SatuSehatSDK\DataType\CodeableConcept;
use agumil\SatuSehatSDK\DataType\Coding;
use agumil\SatuSehatSDK\DataType\Identifier;
use agumil\SatuSehatSDK\DataType\Quantity;
use agumil\SatuSehatSDK\DataType\Reference;
use agumil\SatuSehatSDK\SSClient;
use agumil\SatuSehatSDK\Terminology\HL7\BiologicallyDerivedProductCategory;
use agumil\SatuSehatSDK\Terminology\HL7\BiologicallyDerivedProductStatus;
use agumil\SatuSehatSDK\Terminology\KemKes\IdentifierSystem;

// init client
$ssclient = new SSClient($oauth2, ['environment' => 'staging']);
$builder = new PayloadBuilderBiologicallyDerivedProduct();

// nomor kantong darah, berlaku lintas fasyankes
$builder->addIdentifier(new Identifier(
    IdentifierSystem::bloodBagNumber(),
    'official',
    'GCO-26-A0123-WB',
    null,
    null,
    new Reference('Organization/' . $parentOrganizationId)
));

$builder->setStatus(BiologicallyDerivedProductStatus::CODE_AVAILABLE);
$builder->setProductCategory(BiologicallyDerivedProductCategory::CODE_FLUID);

// whole blood product (SNOMED CT 12289007)
$builder->setProductCode(new CodeableConcept(null, new Coding('http://snomed.info/sct', '12289007', 'Whole blood product')));

// asal kantong: pendonor dan waktu pengambilan
$builder->setCollectionSource(new Reference('Patient/100000030006'));
$builder->setCollectedDateTime('2025-06-04T08:55:00+00:00');

// waktu proses pemisahan komponen
$builder->addProcessing(null, '2025-06-04T09:15:00+00:00');

$builder->setQuantity(1);
$builder->setValueQuantity(new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL'));
$builder->setExpirationDate('2026-10-10T23:59:00+07:00');

$response = $ssclient->createBiologicallyDerivedProduct($builder->build());
var_dump($response->getContentAsObject());
