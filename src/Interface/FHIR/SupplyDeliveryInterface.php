<?php
namespace agumil\SatuSehatSDK\Interface\FHIR;

interface SupplyDeliveryInterface
{
    public function getSupplyDelivery(array $params = []);
    public function getSupplyDeliveryById(string $id);
    public function createSupplyDelivery($params);
    public function updateSupplyDelivery(string $id, $params);
}
