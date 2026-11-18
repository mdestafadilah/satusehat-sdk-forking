<?php
namespace agumil\SatuSehatSDK\Interface\FHIR;

interface BiologicallyDerivedProductInterface
{
    public function getBiologicallyDerivedProduct(array $params = []);
    public function getBiologicallyDerivedProductById(string $id);
    public function createBiologicallyDerivedProduct($params);
    public function updateBiologicallyDerivedProduct(string $id, $params);
}
