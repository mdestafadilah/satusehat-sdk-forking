# SATUSEHAT SDK untuk PHP

<p align="center">
  <b>API wrapper tidak resmi untuk integrasi dengan platform SATUSEHAT (Kementerian Kesehatan RI).</b><br>
  FHIR R4 · Consent · KFA · KYC · OAuth2
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/php-%3E%3D8.0-777bb4">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-blue">
  <img alt="Tests" src="https://img.shields.io/badge/tests-43%20passed-brightgreen">
</p>

<p align="center">
  <a href="#tentang-proyek">Tentang Proyek</a> •
  <a href="#sumber-kode">Sumber Kode</a> •
  <a href="#apa-yang-dibawa-pr-13">PR #13</a> •
  <a href="#catatan">Catatan</a> •
  <a href="#fitur-utama">Fitur</a> •
  <a href="#instalasi">Instalasi</a> •
  <a href="#mulai-cepat">Mulai Cepat</a> •
  <a href="#konfigurasi">Konfigurasi</a> •
  <a href="#partial-update">Partial Update</a> •
  <a href="#paging">Paging</a> •
  <a href="#kyc">KYC</a> •
  <a href="#logging">Logging</a> •
  <a href="#token-caching">Token Caching</a> •
  <a href="#pengujian">Pengujian</a> •
  <a href="#lisensi">Lisensi</a>
</p>

## Tentang Proyek

`mdestafadilah/satusehat-sdk` adalah pustaka PHP (tanpa framework) untuk berbicara dengan platform **SATUSEHAT** milik Kementerian Kesehatan RI. Paket ini adalah fork dari `agumil/satusehat-sdk`. Pustaka ini membungkus lima kelompok API yang dipakai faskes sehari-hari:

| Modul | Client | Base path | Kegunaan |
| --- | --- | --- | --- |
| FHIR R4 | `SSClient` | `/fhir-r4/v1` | Resource klinis: `Patient`, `Encounter`, `Observation`, `Condition`, `Medication`, dan 24 resource lain (29 kelas resource di `src/FHIR`). |
| Consent | `SSClient` | `/consent/v1` | Persetujuan pasien atas pertukaran data. |
| KFA | `SSClientKFA` | `/kfa`, `/kfa-v2`, `/kfa-v3` | Katalog produk & alkes, harga JKN, dan template. |
| KYC | `SSClientKYC` | `/kyc/v1` | Verifikasi NIK untuk memperoleh IHS Number. |
| OAuth2 | `Oauth2` | `/oauth2/v1` | Pengambilan dan penyegaran access token. |

Semua modul berbagi satu token dan satu mekanisme transport, sehingga retry, timeout, logging, dan penyamaran data pasien berlaku seragam di seluruh permukaan API.

Repo ini adalah **fork** dari proyek asal. Isinya bukan sekadar salinan: seluruh perubahan pada [PR #13](https://github.com/agumil/satusehat-sdk/pull/13) sudah masuk, ditambah perbaikan dokumentasi ini. Rincian asal-usul kode ada di [Sumber Kode](#sumber-kode).

## Sumber Kode

| Peran | Lokasi |
| --- | --- |
| Proyek asal (upstream) | [`agumil/satusehat-sdk`](https://github.com/agumil/satusehat-sdk) |
| Pull request yang menjadi dasar fork ini | [`agumil/satusehat-sdk#13` — "Bunch of Update"](https://github.com/agumil/satusehat-sdk/pull/13) |
| Branch asal PR | `hallobayi/satusehat-sdk@main` |
| Repo fork ini | [`mdestafadilah/satusehat-sdk-forking`](https://github.com/mdestafadilah/satusehat-sdk-forking) |
| Analisis keamanan kode ini | [`ANALISIS-KEAMANAN.md`](./ANALISIS-KEAMANAN.md) |

PR #13 berstatus **terbuka (belum di-merge)** saat README ini ditulis: 89 berkas berubah, +5.551 / −281 baris, dikerjakan oleh `mdestafadilah`. Versi yang berisi seluruh perbaikan di bawah dirilis di Packagist dengan nama **[`mdestafadilah/satusehat-sdk`](https://packagist.org/packages/mdestafadilah/satusehat-sdk)** — lihat [Instalasi](#instalasi).

## Apa yang Dibawa PR #13

PR ini mengubah SDK dari "pembungkus HTTP" menjadi pustaka yang layak dipakai di fasilitas kesehatan. Ringkasannya:

| Area | Perubahan | Berkas kunci |
| --- | --- | --- |
| **Ketahanan transport** | Retry otomatis untuk `429`/`5xx` dengan dukungan `Retry-After` dan exponential backoff; hanya method idempoten yang diulang sehingga `POST` tidak pernah terkirim dua kali. Semua kegagalan transport dikembalikan sebagai `Response`, tidak bocor sebagai exception Guzzle. | `src/Request/HttpRequest.php` |
| **OAuth2** | Endpoint token punya `timeout`/`connect_timeout` sendiri (15s/5s); kegagalan jaringan menjadi `SSOauth2Exception`; `client_secret` disaring dari pesan error; refresh otomatis sekali saat menerima `401`. | `src/Auth/Oauth2.php` |
| **Token store** | `TokenStoreInterface` dengan dua implementasi: `InMemoryTokenStore` (default) dan `FileTokenStore` (berbagi token antar worker PHP-FPM). File ditulis `0600` secara atomik, diverifikasi `fileperms()`, disegel HMAC memakai `client_secret`, dan refresh-nya dikunci `flock`. | `src/Auth/FileTokenStore.php`, `src/Auth/InMemoryTokenStore.php` |
| **Privasi (PHI)** | `PhiRedactor` bekerja *default-deny*: seluruh nilai diganti `[REDACTED]`, hanya nama kunci, `resourceType`, `status`, dan parameter paging yang bertahan. ID resource di path URL juga disamarkan. | `src/Helper/PhiRedactor.php` |
| **Modul KYC** | Modul baru: `generateUrl()`, `generateChallengeCode()`, `requestPerNik()`. `X-Debug-Mode` ditolak di production (`SSEnvException` dilempar sebelum request dibuat); NIK divalidasi 16 digit sebelum dikirim. | `src/KYC/`, `src/SSClientKYC.php` |
| **Paging** | `BundlePager` menelusuri `link.next`/`_page_token` tanpa perlu menyusun URL sendiri, dengan pengaman halaman berulang dan batas jumlah halaman. `BundleHelper` untuk membaca Bundle yang sudah ter-decode. | `src/Helper/BundlePager.php`, `src/Helper/BundleHelper.php` |
| **FHIR & KFA** | Resource baru (`ImagingStudy`, `Medication`, `Slot`, `Patient::patch`), validasi ID sebelum interpolasi URL, serta perapian base KFA dan endpoint `Price`. | `src/FHIR/`, `src/KFA/` |
| **Pengujian** | 43 test / 78 assertion, tanpa akses jaringan (Guzzle di-mock, transport KYC/FHIR diganti recorder). | `tests/` |
| **CI** | GitHub Actions: matriks PHP 8.0–8.4, `composer update --prefer-lowest`, dan `composer audit`. | `.github/workflows/ci.yml` |
| **Dependensi** | Guzzle 7.16 + psr7 2.13 + promises 2.6 (Guzzle 6 yang sudah EOL ditinggalkan), `vlucas/phpdotenv` 5.6, `psr/log` 1–3. | `composer.json` |
| **Dokumentasi** | README ini dan `ANALISIS-KEAMANAN.md` (audit keamanan dengan bukti eksekusi). | `README.md`, `ANALISIS-KEAMANAN.md` |

## Catatan

Paket ini masih dalam pengembangan. Dokumentasi terbatas dan beberapa komponen belum lengkap. Uji dulu di environment **development** sebelum menyentuh produksi.

## Fitur Utama

- **Payload Builder** — susun payload senyata mungkin dengan builder; tidak suka? Boleh diabaikan dan kirim array biasa.
- **Sederhana** — dependensi seminimal mungkin; kelas resource mengikuti standar HL7 FHIR.
- **Fleksibel** — tidak terikat framework tertentu; payload bisa dikirim dengan gaya apa pun sesuai proyek Anda.
- **Tangguh secara default** — retry otomatis untuk `429`/`5xx` dengan `Retry-After` dan exponential backoff; hanya method idempoten yang diulang.
- **Aman secara default** — ID resource divalidasi sebelum masuk URL, dan PHI disamarkan sebelum menyentuh log.
- **Helper praktis** — `BundlePager` untuk menelusuri halaman FHIR, `FileTokenStore` agar worker PHP-FPM berbagi satu access token.

## Kebutuhan Sistem

| Komponen | Versi |
| --- | --- |
| PHP | `>= 8.0` (diuji 8.0 – 8.4) |
| `guzzlehttp/guzzle` | `^7.16` |
| `guzzlehttp/psr7` | `^2.13` |
| `guzzlehttp/promises` | `^2.6` |
| `vlucas/phpdotenv` | `^5.6` (wajib, dipakai `EnvHelper`) |
| `psr/log` | `^1.1 \|\| ^2.0 \|\| ^3.0` (opsional saat dipakai) |

Guzzle 6 tidak lagi didukung: sudah EOL dan punya advisory tanpa perbaikan. Guzzle 8 juga belum didukung karena memerlukan `psr7` 3 dan `promises` 3 — migrasi tersendiri.

## Instalasi

```bash
composer require mdestafadilah/satusehat-sdk
```

Bila hanya butuh versi upstream apa adanya:

```bash
composer require agumil/satusehat-sdk
```

Untuk mengikuti branch `main` tanpa menunggu rilis (mis. ingin ikut perubahan yang belum di-tag):

```bash
composer config repositories.satusehat-forking vcs https://github.com/mdestafadilah/satusehat-sdk-forking
composer require mdestafadilah/satusehat-sdk:dev-main
```

Atau langsung di `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/mdestafadilah/satusehat-sdk-forking" }
  ],
  "require": {
    "mdestafadilah/satusehat-sdk": "dev-main"
  }
}
```

Simpan kredensial di `.env`:

```bash
SATUSEHAT_ENV=development|staging|production
SATUSEHAT_ORGANIZATION_ID=organization_id
SATUSEHAT_CLIENT_ID=client_id
SATUSEHAT_CLIENT_SECRET=client_secret
```

`SATUSEHAT_ENV` menentukan host untuk seluruh modul (FHIR, Consent, KFA, KYC, dan OAuth2): `development` → `api-satusehat-dev.dto.kemkes.go.id`, `staging` → `api-satusehat-stg.dto.kemkes.go.id`, `production` → `api-satusehat.kemkes.go.id`. Nilai `.env` didahulukan; parameter `'environment'` pada config hanya dipakai bila `.env` tidak diisi.

> **Catatan**
> Logging bersifat opsional. SDK hanya bergantung pada `psr/log` (antarmuka saja) dan tidak menulis apa pun kecuali Anda memberikan instance logger. Logger PSR-3 apa pun bisa dipakai — `composer require monolog/monolog` bila belum ada.

## Mulai Cepat

Dengan PHPDOTENV:

```php
use agumil\SatuSehatSDK\Auth\Oauth2;
use agumil\SatuSehatSDK\SSClient;

$ssclient = new SSClient(new Oauth2());
$response = $ssclient->getPractitioner([
    'identifier' => 'https://fhir.kemkes.go.id/id/nakes-his-number|10009880728',
]);
```

Tanpa PHPDOTENV:

```php
use agumil\SatuSehatSDK\Auth\Oauth2;
use agumil\SatuSehatSDK\SSClient;

$config1['environment'] = 'development';
$config1['client_id'] = 'your_client_id';
$config1['client_secret'] = 'your_client_secret';

$config2['environment'] = 'development';

$ssclient = new SSClient(new Oauth2($config1), $config2);
$response = $ssclient->getPractitioner([
    'identifier' => 'https://fhir.kemkes.go.id/id/nakes-his-number|10009880728',
]);
```

Host ditentukan oleh `environment`, bukan oleh URL yang Anda masukkan. Tidak ada kunci `base_url`: bila URL bisa diatur pemanggil, satu payload yang salah dapat mengarahkan Bearer token ke host lain (SSRF).

Contoh lain yang siap dijalankan ada di direktori [`examples/`](./examples) (`bootstrap.php` memuat autoloader dan `.env`).

## Konfigurasi

Argumen kedua `SSClient` diteruskan ke setiap request. Semua kunci opsional dan nilai default-nya aman dipakai apa adanya.

```php
use agumil\SatuSehatSDK\Auth\Oauth2;
use agumil\SatuSehatSDK\SSClient;

$ssclient = new SSClient(new Oauth2(), [
    'timeout' => 30,
    'logger' => $logger,
    'retry_attempts' => 3,
    'retry_base_delay_ms' => 200,
    'retry_max_delay_ms' => 5000,
    'retry_methods' => ['GET', 'PUT', 'DELETE'],
]);
```

| Kunci | Tipe | Default | Keterangan |
| --- | --- | --- | --- |
| `timeout` | `int` | `60` | Timeout request dalam detik. |
| `logger` | `Psr\Log\LoggerInterface` | `null` | Logger PSR-3 opsional. Tanpa ini, tidak ada yang dicatat. |
| `retry_attempts` | `int` | `3` | Total percobaan termasuk percobaan pertama. Isi `1` untuk mematikan retry. |
| `retry_base_delay_ms` | `int` | `200` | Jeda dasar untuk exponential backoff. |
| `retry_max_delay_ms` | `int` | `5000` | Batas atas jeda hasil perhitungan backoff. |
| `retry_methods` | `string[]` | `['GET', 'PUT', 'DELETE']` | Method HTTP yang boleh diulang. |

### Perilaku Retry

Request hanya diulang bila **dua** syarat terpenuhi:

1. Status respons `429`, `500`, `502`, `503`, atau `504`.
2. Method HTTP terdaftar di `retry_methods`.

`4xx` lain (`400`, `401`, `403`, `404`) tidak pernah diulang karena pengulangannya menghasilkan jawaban yang sama. `POST` dikecualikan secara default: bila request pertama sebenarnya berhasil tetapi responsnya hilang, mengulang akan membuat resource tercatat dua kali. Tambahkan `'POST'` ke `retry_methods` hanya bila endpoint Anda memang aman diduplikasi.

Bila server mengirim header `Retry-After`, nilainya dihormati (dibatasi 60 detik). Jika tidak, SDK menunggu `retry_base_delay_ms * 2^(attempt - 1)` dengan jitter, tidak melebihi `retry_max_delay_ms`. Setelah `retry_attempts` habis, respons terakhir dikembalikan apa adanya — tidak diubah menjadi exception, sehingga `$response->getHttpCode()` dan `$response->getErrorMessages()` tetap bisa dipakai.

## Partial Update

`createPatient()` selalu mengirim seluruh resource. Untuk mengubah satu field tanpa mengirim ulang semuanya, gunakan `updatePatient()` yang mengirim dokumen JSON Patch ([RFC 6902](https://datatracker.ietf.org/doc/html/rfc6902)):

```php
$response = $ssclient->updatePatient('P02478375538', [
    [
        'op' => 'replace',
        'path' => '/language',
        'value' => 'id',
    ],
]);

var_dump($response->getContentAsObject());
```

SATUSEHAT saat ini hanya menerima operasi `replace`. Beberapa operasi boleh dikirim dalam satu array, dan setiap `path` relatif terhadap akar resource (misalnya `/name/0/text`). Contoh lengkap: [`examples/FHIR/Patient/patch.php`](./examples/FHIR/Patient/patch.php).

## Paging

Pencarian FHIR mengembalikan `Bundle` yang `link.next`-nya berisi `_page_token` buram. Anda tidak bisa menghitung halaman berikutnya sendiri — parameternya harus dikirim ulang dari link tersebut. `BundlePager` mengerjakannya untuk Anda:

```php
use agumil\SatuSehatSDK\Helper\BundleHelper;
use agumil\SatuSehatSDK\Helper\BundlePager;

$pager = new BundlePager(
    fn (array $params) => $ssclient->getPatient($params),
    ['identifier' => 'https://fhir.kemkes.go.id/id/nik|9271060312000001', '_count' => 100]
);

while (($response = $pager->next()) !== null) {
    foreach (BundleHelper::entries($response->getContentAsArray()) as $entry) {
        echo $entry['resource']['id'] . PHP_EOL;
    }
}

echo 'Halaman terambil: ' . $pager->page() . PHP_EOL;
```

`next()` mengembalikan `null` saat tidak ada halaman berikutnya. Ia juga berhenti ketika request gagal, atau ketika server mengembalikan link yang sama dua kali (pengaman terhadap loop tanpa akhir). Argumen ketiga yang opsional membatasi jumlah halaman, default `1000`.

`BundleHelper` berguna sendiri saat Anda sudah punya Bundle ter-decode:

```php
BundleHelper::isBundle($content);        // bool
BundleHelper::entries($bundle);          // list entri, atau []
BundleHelper::total($bundle);            // int|null
BundleHelper::nextUrl($bundle);          // string|null
BundleHelper::nextParams($bundle);       // array|null berisi query param
```

## KYC

KYC (verifikasi NIK) punya base URL sendiri (`/kyc/v1`) dengan Bearer token yang sama, jadi ia dipakai lewat client terpisah: `SSClientKYC`.

Alurnya tiga langkah, dan SDK hanya mengerjakan dua di antaranya — enkripsi dan dekripsi tetap di sisi aplikasi karena kuncinya dipegang faskes:

1. **Enkripsi** pesan permintaan memakai kunci publik dari Kemkes (di luar SDK).
2. `generateUrl()` menukar pesan terenkripsi itu dengan sesi KYC. Responsnya masih ciphertext; dekripsikan untuk mengambil `url_token`.
3. `generateChallengeCode()` atau `requestPerNik()` meminta challenge code memakai `url_token` tersebut.

```php
use agumil\SatuSehatSDK\Auth\Oauth2;
use agumil\SatuSehatSDK\SSClientKYC;

$kyc = new SSClientKYC(new Oauth2(), ['environment' => 'development']);

$url_response = $kyc->generateUrl($encrypted_message); // text/plain, masih ciphertext
$url_token = /* hasil dekripsi di sisi aplikasi */;

$response = $kyc->requestPerNik('3171022809990001', 'Budi Santoso', $url_token);

if ($response->getHttpCode() === 200) {
    $data = $response->getContentAsObject()->data;

    echo $data->ihs_number;        // IHS Number pasien pemilik NIK
    echo $data->challenge_code;    // 6 digit
    echo $data->expired_timestamp; // batas waktu challenge code
}
```

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| `generateUrl(string $encryptedMessage)` | `POST /kyc/v1/generate-url` | Body dikirim apa adanya sebagai `text/plain`. |
| `generateChallengeCode(array $params, string $urlToken, bool $debugMode = false)` | `POST /kyc/v1/challenge-code` | `$params` berisi `['metadata' => [...], 'data' => [...]]`. |
| `requestPerNik(string $nik, string $name, string $urlToken, bool $debugMode = false)` | `POST /kyc/v1/challenge-code` | Pintasan dengan `metadata.method = request_per_nik`. |

Beberapa hal yang perlu diketahui:

- `$urlToken` dikirim sebagai header `X-Frame-Token`. SDK tidak menyimpannya, jadi ambil dari respons `generate-url()` lalu teruskan sendiri.
- `$debugMode = true` menambahkan `X-Debug-Mode: 1` supaya challenge code dikembalikan langsung tanpa widget KYC. Pakai hanya di development/staging: mode ini melewati verifikasi yang seharusnya dilakukan pasien, jadi SDK menolaknya sendiri (`SSEnvException`) bila environment-nya `production` — request tidak dikirim sama sekali.
- NIK diperiksa 16 digit sebelum dikirim, karena respons memuat `ihs_number` milik NIK tersebut — salah ketik berarti challenge code untuk orang lain.
- Lingkungan mengikuti `SATUSEHAT_ENV` (`.env` didahulukan) atau `'environment'` pada config. Nilai yang sah: `development`, `staging`, `production` — sama seperti modul lain.

Contoh: [`examples/KYC/generate_url.php`](./examples/KYC/generate_url.php) dan [`examples/KYC/request_per_nik.php`](./examples/KYC/request_per_nik.php).

## Logging

Berikan logger PSR-3 apa pun dan setiap pertukaran HTTP akan dicatat. Data pasien disamarkan lebih dulu, sehingga identifier, nama, tanggal lahir, alamat, dan kontak tidak pernah sampai ke berkas log Anda.

```php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$logger = new Logger('satusehat');
$logger->pushHandler(new StreamHandler(__DIR__ . '/satusehat.log', Logger::DEBUG));

$ssclient = new SSClient(new Oauth2(), ['logger' => $logger]);
```

Yang dicatat:

| Level | Kapan |
| --- | --- |
| `debug` | Setiap pertukaran selesai: method, URL tersamarkan, status, durasi. |
| `warning` | Kegagalan yang akan diulang, termasuk jeda yang dipakai. |
| `error` | Request gagal permanen, atau seluruh retry sudah habis. |

Query string dan body JSON dilewatkan ke `PhiRedactor`, yang bekerja secara **default-deny**: setiap nilai diganti `[REDACTED]` dan hanya nama kunci, sejumlah kecil field struktural (`resourceType`, `status`), serta parameter paging (`_count`, `_offset`, ...) yang bertahan. Menyamarkan daftar kunci "sensitif" tidak cukup untuk FHIR, karena identitas juga berada di tempat yang nama kuncinya tampak lugu — `subject.display`, `text.div`, dan `note.text` semuanya pernah membawa nama dan NIK pasien pada versi sebelumnya. ID resource di path URL (`/Patient/100000030009`) disamarkan karena alasan yang sama. Pertahankan level logger di `DEBUG` hanya di mesin development — payload yang sudah disamarkan pun tetap membocorkan volume request dan jenis resource.

## Token Caching

Secara default access token hidup di memori, per instance `Oauth2`. Di bawah PHP-FPM itu berarti satu request token per worker, dan setiap worker terus menanggung latensi tersebut sampai token kedaluwarsa.

`FileTokenStore` membagi satu token antar proses:

```php
use agumil\SatuSehatSDK\Auth\FileTokenStore;
use agumil\SatuSehatSDK\Auth\Oauth2;

$oauth2 = new Oauth2([
    'token_store' => new FileTokenStore(__DIR__ . '/storage/satusehat-token.json'),
]);
```

Berkas ditulis dengan permission `0600` memakai pola temp-file-plus-rename yang atomik, sehingga pembaca tidak pernah melihat token setengah tertulis. Mode tersebut kemudian **diverifikasi** dengan `fileperms()`, karena `chmod()` melaporkan sukses bahkan pada filesystem yang tidak bisa menerapkan permission POSIX (FAT/vfat, sebagian network share); dalam kasus itu penulisan gagal dengan suara keras alih-alih meninggalkan kredensial yang bisa dibaca. Penyegaran berjalan di bawah `flock` eksklusif: ketika sepuluh worker mendapati token kedaluwarsa pada saat bersamaan, tepat satu yang memanggil SATUSEHAT dan sembilan lainnya memakai hasilnya.

Token yang disimpan ditandatangani HMAC dengan kunci `client_secret`, yang disuntikkan `Oauth2` secara otomatis. Berkas yang ditulis pihak lain — misalnya token milik organisasi berbeda — gagal verifikasi dan diabaikan, sehingga data klinis tidak bisa dibelokkan ke organisasi yang salah hanya dengan menaruh berkas di disk. Berkas dari versi lama (tanpa tanda tangan) dianggap tidak ada dan diganti pada penyegaran berikutnya. Perlu dicatat: dua aplikasi dengan `client_secret` berbeda tidak bisa berbagi satu berkas token — masing-masing akan menolak token yang lain dan menyegarkan pada setiap panggilan, dan itulah sinyal yang memang diinginkan bahwa keduanya tidak seharusnya berbagi.

> **Catatan Windows**
> Windows tidak menerapkan bit permission POSIX, jadi `chmod()` di sana hanya mengubah flag read-only dan `fileperms()` selalu melaporkan `0666`. SDK tidak berpura-pura sebaliknya: pemeriksaan mode dilewati di Windows, dan kerahasiaan bergantung pada ACL direktori yang Anda tunjuk. Di Windows, lindungi direktori itu dengan NTFS ACL. Pemeriksaan HMAC tetap berlaku di semua platform. Host PHP-FPM produksi tidak terpengaruh.

Arahkan path ke direktori yang bisa ditulis user PHP dan berada di luar web root. Berkasnya berisi kredensial hidup — perlakukan seperti secret lain dan jangan pernah di-commit.

## Pengujian

```bash
composer install
composer test
```

Suite berjalan di PHPUnit dan tidak memerlukan akses jaringan: handler Guzzle di-mock, dan transport KYC/FHIR diganti recorder. Hasil pada repo ini (PHP 8.4, PHPUnit 11.5):

```
Tests: 43, Assertions: 78, Skipped: 2.
```

Dua test permission dilewati di Windows karena perilaku yang diuji (bit mode POSIX) tidak ada di sana; `PosixFileTokenStore` di `tests/Support` menutup jalur verifikasi tersebut di Windows.

CI berjalan di setiap push ke `main` dan setiap pull request, dengan **3 job (7 run)** sesuai [`.github/workflows/ci.yml`](./.github/workflows/ci.yml):

| Job | Yang dibuktikan |
| --- | --- |
| `test` (matriks PHP 8.0 – 8.4) | Suite lulus di setiap versi yang diklaim didukung, dan setiap berkas lolos `php -l`. |
| `lowest` | `composer update --prefer-lowest` tetap berjalan — batas versi di `composer.json` nyata, bukan harapan. |
| `audit` | `composer audit` tidak menemukan advisory pada dependensi yang terkunci. |

PHP 8.0 dan 8.1 memakai PHPUnit versi sendiri (9.6 dan 10.5) karena PHPUnit 11 yang terkunci membutuhkan PHP 8.2; PHP 8.0 menjalankan test yang sama lewat `composer test:compat`, yang melewati berkas konfigurasi yang tidak bisa dibaca PHPUnit 9.6.

## Kredit

Perangkat lunak ini memakai paket open source berikut:

- [Composer](https://getcomposer.org/)
- [Guzzle](https://github.com/guzzle/guzzle)
- [PSR-3 Log](https://github.com/php-fig/log)
- [PHP Dotenv](https://github.com/vlucas/phpdotenv)

## Lisensi

MIT License

Copyright (c) 2024 Agung Gumilang

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
