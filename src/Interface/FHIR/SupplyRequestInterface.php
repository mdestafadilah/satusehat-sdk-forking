<?php
namespace agumil\SatuSehatSDK\Interface\FHIR;

interface SupplyRequestInterface
{
    public function getSupplyRequest(array $params = []);
    public function getSupplyRequestById(string $id);
    public function createSupplyRequest($params);
    public function updateSupplyRequest(string $id, $params);
}
