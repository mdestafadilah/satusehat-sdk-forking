<?php require __DIR__ . '/../../../Auth/oauth2.php';

use agumil\SatuSehatSDK\Builder\PayloadBuilderSupplyRequest;
use agumil\SatuSehatSDK\DataType\CodeableConcept;
use agumil\SatuSehatSDK\DataType\Coding;
use agumil\SatuSehatSDK\DataType\Identifier;
use agumil\SatuSehatSDK\DataType\Quantity;
use agumil\SatuSehatSDK\DataType\Reference;
use agumil\SatuSehatSDK\SSClient;
use agumil\SatuSehatSDK\Terminology\HL7\RequestStatus;
use agumil\SatuSehatSDK\Terminology\KemKes\IdentifierSystem;

// init client
$ssclient = new SSClient($oauth2, ['environment' => 'staging']);
$builder = new PayloadBuilderSupplyRequest();

$builder->addIdentifier(new Identifier(
    IdentifierSystem::supplyrequest($parentOrganizationId),
    null,
    'SR0000001'
));

$builder->setStatus(RequestStatus::CODE_ACTIVE);

// blood bag (SNOMED CT 116205004)
$builder->setItemCodeableConcept(new CodeableConcept(null, new Coding('http://snomed.info/sct', '116205004', 'Blood bag')));
$builder->setQuantity(new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL'));
$builder->setAuthoredOn('2025-08-27T14:00:00+00:00');

// fasyankes peminta dan UPD sebagai pemasok
$builder->setRequester(new Reference('Organization/100025610', null, 'RSUP Dr. Sardjito'));
$builder->addSupplier(new Reference('Organization/' . $parentOrganizationId, null, 'PMI Kota Yogyakarta'));
$builder->addReasonCode(new CodeableConcept('Kebutuhan kantong darah untuk pasien'));

$response = $ssclient->createSupplyRequest($builder->build());
var_dump($response->getContentAsObject());
