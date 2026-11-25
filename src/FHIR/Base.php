<?php
namespace agumil\SatuSehatSDK\FHIR;

use agumil\SatuSehatSDK\Auth\Oauth2;
use agumil\SatuSehatSDK\Config\Endpoint;
use agumil\SatuSehatSDK\Exception\SSDataTypeException;
use agumil\SatuSehatSDK\Exception\SSEnvException;
use agumil\SatuSehatSDK\Helper\EnvHelper;
use agumil\SatuSehatSDK\Helper\StrHelper;

class Base
{
    protected Oauth2 $oauth2;

    protected string $base_url;

    protected array $config;

    protected function __construct(Oauth2 $oauth2, array $config = [])
    {
        if (EnvHelper::isDevelopment() || @$config['environment'] === 'development') {
            $this->base_url = Endpoint::DEV_FHIR;
        } elseif (EnvHelper::isStaging() || @$config['environment'] === 'staging') {
            $this->base_url = Endpoint::STG_FHIR;
        } elseif (EnvHelper::isProduction() || @$config['environment'] === 'production') {
            $this->base_url = Endpoint::PROD_FHIR;
        } else {
            $env = EnvHelper::getEnvironment();
            throw new SSEnvException("FHIR Client - Environment '{$env}' OR Parameter 'environment' must be provided. Valid environment value is must be 'development', 'staging' or 'production'");
        }

        $this->oauth2 = $oauth2;
        $this->base_url = StrHelper::appendSlash($this->base_url);
        $this->config = $config;
    }

    /**
     * Menormalkan payload `update*` sebelum `id` disisipkan.
     *
     * `create*` menerima objek builder karena `HttpRequest` memanggil
     * `build()` sendiri, tetapi `update*` harus menyisipkan `id` lebih dulu —
     * dan operasi array pada objek builder fatal sebagai `\Error` mentah yang
     * tidak tertangkap `catch (\Exception)` di aplikasi. Builder dinormalkan
     * menjadi array di sini, dan payload bentuk lain (string JSON, objek
     * tanpa `build()`) ditolak sebagai exception SDK alih-alih fatal error.
     *
     * `id` hasil validasi path selalu menimpa `id` bawaan payload supaya body
     * dan URL tidak pernah menunjuk resource yang berbeda.
     *
     * @param array|object $params payload update: array FHIR atau payload builder
     */
    protected function payloadWithId($params, string $id): array
    {
        if (is_object($params) && method_exists($params, 'build')) {
            $params = $params->build();
        }

        if (!is_array($params)) {
            throw new SSDataTypeException('params must be an array or a payload builder providing build().');
        }

        $params['id'] = $id;

        return $params;
    }
}
