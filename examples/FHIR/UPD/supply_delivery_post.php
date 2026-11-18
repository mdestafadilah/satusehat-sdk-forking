<?php require __DIR__ . '/../../../Auth/oauth2.php';

use agumil\SatuSehatSDK\Builder\PayloadBuilderSupplyDelivery;
use agumil\SatuSehatSDK\DataType\Identifier;
use agumil\SatuSehatSDK\DataType\Quantity;
use agumil\SatuSehatSDK\DataType\Reference;
use agumil\SatuSehatSDK\SSClient;
use agumil\SatuSehatSDK\Terminology\HL7\SupplyDeliveryStatus;
use agumil\SatuSehatSDK\Terminology\KemKes\IdentifierSystem;

// init client
$ssclient = new SSClient($oauth2, ['environment' => 'staging']);
$builder = new PayloadBuilderSupplyDelivery();

$builder->addIdentifier(new Identifier(
    IdentifierSystem::supplydelivery($parentOrganizationId),
    null,
    'SD0000001'
));

// permintaan yang dipenuhi oleh pengiriman ini
$builder->addBasedOn(new Reference('SupplyRequest/sr-0000001'));
$builder->setStatus(SupplyDeliveryStatus::CODE_COMPLETED);

// kantong darah yang dikirim ditautkan lewat extension profil SATUSEHAT
$builder->setSuppliedItem(
    new Quantity('http://unitsofmeasure.org', 'mL', 250, 'mL'),
    new Reference('BiologicallyDerivedProduct/bdp-0000001', null, 'Kantong darah GCO-26-A0123-WB')
);

$builder->setOccurrenceDateTime('2025-08-27T14:00:00+00:00');
$builder->setSupplier(new Reference('Organization/' . $parentOrganizationId, null, 'PMI Kota Yogyakarta'));
$builder->addReceiver(new Reference('Practitioner/nakes-0000001', null, 'dr. Dewi'));
$builder->setReceivingOrganization(new Reference('Organization/100025610', null, 'RSUP Dr. Sardjito'));

$response = $ssclient->createSupplyDelivery($builder->build());
var_dump($response->getContentAsObject());
