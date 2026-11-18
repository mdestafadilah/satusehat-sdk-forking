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

$id = 'b6a1c2d3-0000-4000-8000-000000000001';

// Status kantong berubah menjadi unavailable setelah keluar dari stok.
// Pakai setStatusDetail() untuk distribusi (117074003) dan setDestruction()
// untuk pemusnahan (117070007).
$builder = new PayloadBuilderBiologicallyDerivedProduct();

$builder->addIdentifier(new Identifier(
    IdentifierSystem::bloodBagNumber(),
    'official',
    'GCO-26-A0123-WB',
    null,
    null,
    new Reference('Organization/' . $parentOrganizationId)
));

$builder->setStatus(BiologicallyDerivedProductStatus::CODE_UNAVAILABLE);
$builder->setProductCategory(BiologicallyDerivedProductCategory::CODE_FLUID);
$builder->setProductCode(new CodeableConcept(null, new Coding('http://snomed.info/sct', '12289007', 'Whole blood product')));

$builder->setCollectionSource(new Reference('Patient/100000030006'));
$builder->setCollectedDateTime('2025-06-04T08:55:00+00:00');
$builder->addProcessing(null, '2025-06-04T09:15:00+00:00');
$builder->setQuantity(1);
$builder->setValueQuantity(new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL'));
$builder->setExpirationDate('2026-10-10T23:59:00+07:00');

// contoh pemusnahan karena kedaluwarsa
$builder->setStatusDetail(new CodeableConcept(null, new Coding('http://snomed.info/sct', '117070007', 'Blood product unit destroyed')));
$builder->setDestruction(
    '2026-10-12T09:00:00+00:00',
    new CodeableConcept(null, new Coding('http://snomed.info/sct', '117073009', 'Blood product unit expired'))
);

$response = $ssclient->updateBiologicallyDerivedProduct($id, $builder->build());
var_dump($response->getContentAsObject());
