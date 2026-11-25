# Analisis Keamanan & Kesesuaian API — `mdestafadilah/satusehat-sdk` (v4)

**Tanggal:** 26 November 2026
**Commit yang dianalisis:** `dac25af` — *feat(upd): tambah resource dan payload builder untuk use case UPD*
**Metode:** pembacaan kode, eksekusi test suite lokal (PHP 8.4.5), `composer audit`, scan fungsi berbahaya/secret, plus review silang builder UPD terhadap spesifikasi FHIR R4. Setiap klaim di bawah merujuk file dan baris yang bisa diperiksa langsung.

> **Riwayat:** v1 (`5353959`) dan v2 (`eafaddc`) sudah kedaluwarsa. v3 (`abf9295`) memverifikasi perbaikan 4 temuan P0 dan meninggalkan 5 temuan terbuka. Catatan: riwayat git repo ini telah di-squash sehingga commit `abf9295` tidak lagi ada di history — baseline v3 diverifikasi ulang dengan membaca kode saat ini, bukan lewat diff.

---

## 1. Ringkasan Eksekutif

**Skor kesiapan produksi: 8/10** (v3: 8,5/10 — turun karena kode UPD baru membawa cacat kesesuaian FHIR R4, meski fondasi keamanan tetap utuh).

Fondasi keamanan yang dibangun sejak v2 **masih utuh seluruhnya**: timeout endpoint token, redaksi PHI default-deny, penolakan `X-Debug-Mode` di produksi, dan FileTokenStore dengan HMAC + verifikasi permission. Higiene repo juga bersih: tidak ada `eval`/`exec`/`unserialize`/include dinamis, tidak ada kredensial ter-commit, `.env` di-ignore, CI memakai `permissions: contents: read` tanpa `pull_request_target`.

**Hasil eksekusi di commit ini:**

- Test suite: **50 test, 87 assertion, hijau** (2 di-skip: test permission POSIX, memang tidak jalan di Windows).
- `composer audit`: **0 advisory**.
- Dependensi langsung: Guzzle 7.16.1, psr7 2.13.2 — major update tersedia (Guzzle 8, PHPUnit 13) tapi tidak mendesak.

Yang berubah sejak v3 hanyalah satu commit kode: tiga resource UPD (BiologicallyDerivedProduct, SupplyRequest, SupplyDelivery) beserta builder, terminology, test, dan contohnya. Commit itu **mengulangi bug lama N10** pada tiga method `update*` barunya dan membawa **empat temuan MEDIUM baru** seputar kesesuaian FHIR R4:

| # | Temuan | Tingkat |
|---|---|---|
| **N10** | `update*` fatal (`\Error` mentah) bila diberi builder — kini 29 resource, termasuk 3 resource UPD baru | ✅ **DIPERBAIKI** pasca-review (lihat §3) |
| **N14** | `SupplyRequest.status` divalidasi terhadap value set yang salah (`request-status`, seharusnya `supplyrequest-status`) | ✅ **DIPERBAIKI** pasca-review (lihat §3) |
| **N15** | Builder BDP memancarkan elemen yang tidak ada di R4 (`collection.quantity/procedure/bodySite/fastingStatus`, `division`, `property`) | MEDIUM |
| **N16** | Tipe data backbone BDP salah vs R4 (`processing.additive`, `storage.temperature`, `storage.duration`, `manipulation`) | ✅ **DIPERBAIKI** pasca-review (lihat §3) |
| **N17** | `setSuppliedItem()` di SupplyDelivery menimpa `itemReference` yang sudah di-set — kehilangan data diam-diam, tergantung urutan | ✅ **DIPERBAIKI** pasca-review (lihat §3) |

Temuan terbuka v3 (N11, N12, N6, N9, F3 dst.) **belum satu pun diperbaiki** — rincian di §4.

---

## 2. Verifikasi Baseline v3 → v4

Semua diperiksa ulang pada kode saat ini.

| Item v3 | Status di `dac25af` | Bukti |
|---|---|---|
| Timeout + `connect_timeout` endpoint token, `SSOauth2Exception`, scrub `client_secret` | ✅ utuh | `src/Auth/Oauth2.php:26-28, 275-313, 321-328` |
| `PhiRedactor` default-deny (nilai, path id, query param) | ✅ utuh | `src/Helper/PhiRedactor.php` — `SAFE_KEYS` tetap hanya `resourcetype`+`status` |
| `X-Debug-Mode` ditolak di production sebelum request dibuat | ✅ utuh | `src/KYC/Kyc.php:61-65` |
| `FileTokenStore`: tulis atomik + `umask(0077)`, verifikasi `fileperms()`, HMAC `client_secret`, refresh di bawah `flock` | ✅ utuh | `src/Auth/FileTokenStore.php` |
| Validasi `resourceId` sebelum interpolasi URL | ✅ utuh, **juga diterapkan di 3 resource UPD baru** | `src/Helper/ValidatorHelper.php:123-130`; `src/FHIR/{SupplyRequest,SupplyDelivery,BiologicallyDerivedProduct}.php` |
| Retry idempoten-only, `Retry-After` dibatasi 60 dtk/percobaan, PATCH tidak di-retry | ✅ utuh | `src/Request/HttpRequest.php:21-44, 169-192` |
| 43 test → kini **50 test / 87 assertion**, semuanya hijau | ✅ | `vendor/bin/phpunit` di PHP 8.4.5 |
| `composer audit` bersih | ✅ | 0 advisory |

**Higiene tambahan yang diperiksa di v4 (semuanya bersih):**

- Tidak ada `eval`, `exec`, `shell_exec`, `system`, `unserialize`, `extract`, atau include/require dinamis di `src/` dan `examples/`.
- Tidak ada secret ter-commit; `.env.example` hanya placeholder; `.gitignore` memuat `.env`.
- Workflow CI: `permissions: contents: read`, concurrency group, tanpa secret, tanpa `pull_request_target`.

---

## 3. Temuan Baru (review commit UPD `dac25af`)

### N10 (meluas) — `update*` fatal bila diberi builder, kini 29 resource (MEDIUM → ✅ DIPERBAIKI)

> **Status: diperbaiki langsung setelah review v4.** Normalisasi kini terpusat di `FHIR\Base::payloadWithId()`: objek builder di-`build()` lebih dulu, payload non-array dilempar sebagai `SSDataTypeException` (bukan `\Error`), dan `id` hasil validasi path selalu menimpa `id` bawaan payload. Ke-29 method `update*` memanggil helper ini. Diuji di `tests/FHIR/BasePayloadWithIdTest.php` (5 test: array, builder, override id, string ditolak, objek tanpa `build()` ditolak). Suite penuh: 55 test / 94 assertion hijau. Deskripsi asli temuan dipertahankan di bawah sebagai arsip.

Tiga method `update*` baru mengulangi pola lama `$params['id'] = $id;` pada parameter tanpa tipe:

- `src/FHIR/BiologicallyDerivedProduct.php:45`
- `src/FHIR/SupplyRequest.php:45`
- `src/FHIR/SupplyDelivery.php:45`

Skenario: `$ss->updateSupplyRequest($id, $builder)` (objek builder, bukan `$builder->build()`) fatal dengan
`Error: Cannot use object of type PayloadBuilderSupplyRequest as array` — `\Error` mentah, bukan exception SDK, sehingga `catch (\Exception)` di aplikasi tidak menangkapnya. Payload berupa string JSON juga fatal (`Cannot access offset of type string on string`). Jalur `create*` menerima builder dengan baik (`HttpRequest::initializeOptions()` mendeteksi objek ber-`build()`), jadi inkonsistensinya persis seperti didokumentasikan di v3 §3 — hanya cakupannya bertambah.

Catatan positif: `examples/FHIR/UPD/*.php` semuanya memanggil `->build()` lebih dulu, jadi jalur terdokumentasi aman. Perbaikan tetap satu helper normalisasi untuk 29 pemanggil (lihat v3 §3).

### N14 — Value set `SupplyRequest.status` salah (MEDIUM → ✅ DIPERBAIKI)

> **Status: diperbaiki pasca-review v4.** Terminology baru `HL7\SupplyRequestStatus` (system `http://hl7.org/fhir/supplyrequest-status`) kini dipakai `PayloadBuilderSupplyRequest::setStatus()`; contoh `supply_request_post.php` dan test disesuaikan. Diuji: `suspended`/`cancelled` diterima, `on-hold` ditolak. Deskripsi asli dipertahankan di bawah sebagai arsip.

`src/Builder/PayloadBuilderSupplyRequest.php:61` memvalidasi `setStatus()` terhadap `Terminology\HL7\RequestStatus` (`request-status`: draft|active|on-hold|revoked|completed|entered-in-error|unknown). FHIR R4 mengikat `SupplyRequest.status` (required binding) ke **`supplyrequest-status`**: draft|active|**suspended**|**cancelled**|completed|entered-in-error|unknown.

Akibat ganda:

1. `setStatus('suspended')` / `setStatus('cancelled')` — status yang sah — dilempar `SSDataTypeException` oleh builder.
2. `setStatus('on-hold')` / `setStatus('revoked')` — status yang tidak sah — lolos builder dan baru ditolak server sebagai 400.

Docblock kelasnya (baris 17-18) yang mengklaim sesuai R4 juga keliru. Perbaikan: buat `Terminology\HL7\SupplyRequestStatus` dan pakai itu.

### N15 — Builder BDP memancarkan elemen non-R4 (MEDIUM)

`src/Builder/PayloadBuilderBiologicallyDerivedProduct.php`:

- Baris 165-191: `collection.quantity`, `collection.procedure`, `collection.bodySite`, `collection.fastingStatus` — ini field milik **Specimen.collection**, bukan BiologicallyDerivedProduct.collection (R4 hanya punya `collector`, `source`, `collected[x]`).
- Baris 243-250: `division` — hanya ada di R5.
- Baris 281-298: `property` — hanya ada di R5.

Tidak satu pun method ini dipakai test atau contoh, jadi kegagalannya (400 dari validator R4, atau elemen dibuang diam-diam) baru muncul di integrasi produksi. Perlu dikonfirmasi ke profil UPD resmi Kemkes sebelum dihapus — profil SATUSEHAT kadang menyimpang dari R4 murni, tetapi kalaupun begitu, penyimpangannya harus didokumentasikan, bukan dibiarkan implisit.

### N16 — Tipe data backbone BDP salah vs R4 (MEDIUM → ✅ DIPERBAIKI)

> **Status: diperbaiki pasca-review v4.** `processing.additive` kini Reference (Substance); `setManipulation()` hanya menerima `description` + `timeDateTime`; `addStorage()` menerima `temperature` sebagai `int|float` polos, `scale` divalidasi terhadap terminology baru `HL7\BiologicallyDerivedProductStorageScale` (`product-storage-scale`: farenheit|celsius|kelvin), dan `duration` berupa Period. Tidak ada pemanggil lama yang memakai parameter yang berubah (test & contoh hanya memakai `addProcessing(null, <waktu>)`), jadi tidak ada jalur terdokumentasi yang pecah. Diuji: bentuk keluaran storage/processing/manipulation, dan `scale` di luar value set ditolak. Deskripsi asli dipertahankan di bawah sebagai arsip. Catatan: N16 berbeda dari N15 — N15 (setter untuk elemen yang tidak ada di R4) tetap terbuka menunggu konfirmasi profil UPD resmi.

Masih di builder yang sama:

- Baris 205-207: `processing.additive` di R4 adalah **Reference(Substance)**, builder menerima CodeableConcept (memancarkan `{coding:[...]}` padahal yang diminta `{reference:"..."}`).
- Baris 226-232: `manipulation` di R4 hanya punya `description` + `time[x]`; builder ikut memancarkan `procedure`/`additive`.
- Baris 260-270: `storage.temperature` di R4 adalah **decimal**, builder menerima Quantity; `storage.duration` adalah **Period**, builder menerima Duration; `storage.scale` string bebas padahal R4 mengikatnya ke `farenheit|celsius|kelvin`.

Skenario konkret: `addStorage(null, new Quantity(..., 4, 'C'))` menghasilkan `storage[0].temperature` berupa objek → 400 dari validator FHIR.

### N17 — Kehilangan data diam-diam pada `suppliedItem` SupplyDelivery (MEDIUM → ✅ DIPERBAIKI)

> **Status: diperbaiki pasca-review v4.** Semua setter `suppliedItem` kini merge per-bagian (urutan pemanggilan tidak berpengaruh), choice `itemReference`/`itemCodeableConcept` dijaga mutual-exclusion ala `assertOccurrenceNotSet`, dan setter `setSuppliedItemCodeableConcept()` yang hilang ditambahkan. Properti `supplied_item` diberi nilai awal `[]` agar eksplisit (catatan koreksi: tulis per-kunci pada typed array property yang belum terinisialisasi ternyata sah di PHP — diverifikasi empiris — jadi ini kerapian, bukan perbaikan bug laten seperti sempat ditulis). Diuji order-independence dua arah + konflik choice. Deskripsi asli dipertahankan di bawah sebagai arsip.

`src/Builder/PayloadBuilderSupplyDelivery.php:101-121`: `setSuppliedItem()` mengganti `$this->supplied_item` seutuhnya (menghapus `itemReference` yang sudah di-set), sedangkan `setSuppliedItemReference()` me-merge. Jadi:

```php
$b->setSuppliedItemReference($ref);
$b->setSuppliedItem($qty);      // itemReference hilang tanpa error
```

sementara urutan sebaliknya mempertahankan keduanya. `suppliedItem.item[x]` adalah choice field, tetapi tidak ada guard mutual-exclusion di sini (builder lain punya `assert*NotSet`), dan `setSuppliedItemCodeableConcept` tidak ada sama sekali.

### Temuan LOW

| # | Temuan | Lokasi |
|---|---|---|
| N18 | `addProcessing()`/`addStorage()` dengan semua argumen null meng-append `[]`, sehingga `build()` memancarkan `"processing":[[]]` — elemen kosong yang ditolak FHIR. (`setManipulation()` all-null aman karena di-drop `!empty` di :383.) | `PayloadBuilderBiologicallyDerivedProduct.php:193-216, 252-275` |
| N19 | `build()` SupplyRequest tidak menegakkan elemen wajib R4 (`item[x]` 1..1, `quantity` 1..1); payload berisi status saja "berhasil" dibangun dan gagal di server. Konsisten dengan konvensi builder SDK, tetapi test di `PayloadBuilderUpdTest.php:255-263` justru merestui resource invalid itu. | `PayloadBuilderSupplyRequest.php:176-238` |
| N20 | Cakupan test jalur gagal tipis: mutual-exclusion `item[x]`/`occurrence[x]` SupplyRequest & SupplyDelivery, priority invalid, penolakan `resourceId`, dan fatal N10 semuanya belum di-test. Bug N17 akan tertangkap oleh satu test dua-setter. | `tests/Builder/PayloadBuilderUpdTest.php` |
| N21 | `buildAsJSON()` bisa TypeError bila `json_encode()` mengembalikan false (mis. byte non-UTF-8 di description). Konsisten dengan builder lama — perbaiki SDK-wide atau tidak sama sekali. | BDP:406, SupplyRequest:241, SupplyDelivery:232 |
| N22 (verifikasi) | `EXT_VALUE_QUANTITY` (`.../StructureDefinition/valueQuantity`, tanpa `/r4/` dan tanpa segmen resource) sengaja berbeda bentuk dari empat URL extension saudaranya karena contoh Postman tidak konsisten (komentar :31-34). Bila URL ini yang salah, volume kantong darah diabaikan server secara diam-diam. Konfirmasi ke profil UPD resmi; **jangan dinormalkan buta** — golden test di `PayloadBuilderUpdTest.php:77` mengunci nilai sekarang. | `PayloadBuilderBiologicallyDerivedProduct.php:36` |

### Yang tampak mencurigakan tetapi aman — jangan "diperbaiki"

| Dugaan | Hasil verifikasi |
|---|---|
| `!empty($this->identifier)` pada typed property belum terinisialisasi | **Aman.** `isset()`/`empty()` pada typed property uninitialized mengembalikan false/true tanpa throw; `build()` pada builder segar tidak error. |
| `key($this->item)` / `key($this->occurrence)` di `build()` | **Aman.** Array selalu baru di-assign dengan satu kunci; pointer internal tidak pernah digeser. |
| `assert*NotSet` membolehkan set ulang kunci choice yang SAMA (overwrite) | **Disengaja.** Semantik replace, konsisten di ketiga builder; konflik lintas-kunci tetap dilempar `SSDataTypeException`. |
| Display `"Delivered"` untuk kode `completed` di `SupplyDeliveryStatus` | **Bukan typo.** Itu display resmi HL7. Seluruh daftar kode terminology baru cocok dengan R4. |
| `IdentifierSystem` baru menggabungkan `organization_id` tanpa validasi | **Bukan injeksi.** Hasilnya masuk nilai JSON (`identifier.system`), tidak pernah masuk URL request. |
| Builder mengakumulasi state antar `build()` | **Konvensi SDK.** `build()` read-only/idempoten; pemanggilan berulang menghasilkan output identik. |
| String field (description, display) tidak disanitasi | **Aman.** Semuanya lewat `json_encode` — tidak ada vektor injeksi payload. |

---

## 4. Yang Masih Terbuka (warisan v3, diverifikasi ulang — belum ada yang diperbaiki)

| # | Celah | Status v4 |
|---|---|---|
| **N11** | `HttpRequest` tanpa `connect_timeout` — hanya `Oauth2` yang punya | Masih ada (`src/Request/HttpRequest.php:314-320` hanya mengirim `timeout`) |
| **N12** | Retry tanpa anggaran waktu total; `Retry-After: 60` × 3 percobaan ≈ 120 dtk per request | Masih ada |
| **N6** | `EnvHelper::__construct()` kode mati menunjuk `.env` di `src/Helper` | Masih ada (`src/Helper/EnvHelper.php:13-22`) |
| **N9** | `"minimum-stability": "dev"` di composer.json; belum ada PHPStan/Psalm di CI | Masih ada (`composer.json:44`) |
| **N13** | `HttpRequest` membuat `Client` baru tiap request — tanpa connection reuse | Masih ada (performa, bukan keamanan) |
| **F3** | 22 resource resmi belum ada (Task, DocumentReference, Coverage/Claim, dll.) — minus 3 yang kini hadir lewat UPD | Berkurang jadi 19 |
| **F7** | Tanpa `If-Match`/`meta.versionId` — rawan lost update | Masih ada |
| **F10** | Kombinasi parameter pencarian `Patient` tidak divalidasi | Masih ada |
| **F14** | `Terminology/` hanya konstanta lokal, tanpa `$validate-code`/`$lookup` | Masih ada |

---

## 5. Prioritas Berikutnya

**P1 — sebelum alur UPD dipakai produksi**

1. ~~**N10** — normalisasi builder di `update*`~~ ✅ selesai (`Base::payloadWithId()`, 29 pemanggil, +5 test).
2. ~~**N14** — value set `SupplyRequestStatus` yang benar~~ ✅ selesai (terminology baru + 2 test).
3. ~~**N17** — guard choice-field `suppliedItem` di SupplyDelivery~~ ✅ selesai (merge per-bagian + guard choice + setter baru, +2 test).
4. ~~**N16** — tipe data backbone BDP~~ ✅ selesai (additive→Reference, temperature→decimal, duration→Period, scale→value set baru, manipulation dirampingkan; +2 test).
5. **N15/N22** — dilaporkan ke Kemkes untuk konfirmasi profil UPD resmi; selaraskan atau dokumentasikan penyimpangan setelah ada jawaban.

**P2 — ketahanan & higiene (warisan v3)**

5. **N12** — anggaran total retry.
6. **N11** — `connect_timeout` di `HttpRequest`.
7. **N9** — hapus `minimum-stability: dev`, tambahkan PHPStan level 5 ke CI.
8. **N18–N21**, **N6**, **F7**.

---

## 6. Lampiran — Cara Reproduksi

```bash
composer install
vendor/bin/phpunit    # 50 test, 87 assertion, 2 skipped (POSIX di Windows)
composer audit        # 0 advisory
```

```
### N10 di resource UPD baru
$ss->updateSupplyRequest('id-1', $builder);
→ Error: Cannot use object of type PayloadBuilderSupplyRequest as array

### N14
(new PayloadBuilderSupplyRequest)->setStatus('suspended');
→ SSDataTypeException (padahal sah untuk SupplyRequest)
(new PayloadBuilderSupplyRequest)->setStatus('revoked');
→ lolos builder (padahal tidak sah) → 400 dari server

### N17
$b->setSuppliedItemReference($ref); $b->setSuppliedItem($qty);
→ build()['suppliedItem'] tanpa itemReference, tanpa error

### N18
$b->addProcessing(); $b->addStorage();
→ build() memuat "processing":[[]] dan "storage":[[]]
```

**Batas kejujuran analisis ini:** test permission POSIX tetap di-skip di mesin Windows ini (di CI Linux berjalan sungguhan). Pemetaan N14–N16 bersandar pada definisi FHIR R4 upstream (hl7.org/fhir/R4); profil turunan SATUSEHAT untuk UPD belum bisa dibandingkan langsung karena koleksi Postman resminya tidak dapat diunduh tanpa API key — karena itu N15/N16/N22 diberi catatan "konfirmasi profil resmi" alih-alih divonis mutlak.
