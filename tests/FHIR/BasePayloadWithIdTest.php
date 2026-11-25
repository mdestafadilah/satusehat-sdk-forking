<?php
namespace agumil\SatuSehatSDK\Tests\FHIR;

use agumil\SatuSehatSDK\Builder\PayloadBuilderOrganization;
use agumil\SatuSehatSDK\Exception\SSDataTypeException;
use agumil\SatuSehatSDK\FHIR\Base;
use agumil\SatuSehatSDK\Tests\Support\EnvIsolation;
use agumil\SatuSehatSDK\Tests\Support\FakeOauth2;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Temuan N10: `update*` dulu melakukan `$params['id'] = $id;` langsung pada
 * parameter tanpa tipe, sehingga objek builder — yang diterima `create*` —
 * fatal sebagai `\Error` mentah. Normalisasinya kini terpusat di
 * `Base::payloadWithId()` dan diuji di sini lewat subclass yang membuka
 * method tersebut.
 */
class BasePayloadWithIdTest extends TestCase
{
    use EnvIsolation;

    protected function setUp(): void
    {
        $this->isolateEnvironment();
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment();
    }

    private function base(): ExposedFhirBase
    {
        $oauth2 = new FakeOauth2([
            'environment' => 'development',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
        ]);

        return new ExposedFhirBase($oauth2);
    }

    public function testArrayPayloadGetsIdWithoutLosingKeys(): void
    {
        $result = $this->base()->normalize(['name' => 'RS Contoh'], 'org-1');

        $this->assertSame(['name' => 'RS Contoh', 'id' => 'org-1'], $result);
    }

    public function testBuilderIsBuiltAndGetsId(): void
    {
        $builder = new PayloadBuilderOrganization();
        $builder->setName('RS Contoh');

        $result = $this->base()->normalize($builder, 'org-1');

        $this->assertSame('Organization', $result['resourceType']);
        $this->assertSame('RS Contoh', $result['name']);
        $this->assertSame('org-1', $result['id']);
    }

    public function testPathIdOverridesPayloadId(): void
    {
        // Body dan URL tidak boleh menunjuk resource berbeda; id hasil
        // validasi path yang menang.
        $result = $this->base()->normalize(['id' => 'lama'], 'baru');

        $this->assertSame('baru', $result['id']);
    }

    public function testStringPayloadThrowsSdkExceptionInsteadOfFatal(): void
    {
        $this->expectException(SSDataTypeException::class);

        $this->base()->normalize('{"name":"RS Contoh"}', 'org-1');
    }

    public function testObjectWithoutBuildThrowsSdkException(): void
    {
        $this->expectException(SSDataTypeException::class);

        $this->base()->normalize(new stdClass(), 'org-1');
    }
}

class ExposedFhirBase extends Base
{
    public function __construct(FakeOauth2 $oauth2)
    {
        parent::__construct($oauth2, ['environment' => 'development']);
    }

    public function normalize($params, string $id): array
    {
        return $this->payloadWithId($params, $id);
    }
}
