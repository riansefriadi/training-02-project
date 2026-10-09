# PRD: MVP Aplikasi Web Sales Order

Sumber: `transkrip_client.txt` dan tabel Lingkup MVP (gambar dari klien).
Legenda penanda: **[ASUMSI-n]** = dugaan yang perlu dikonfirmasi; **[OQ-n]** = pertanyaan terbuka nomor n (lihat bagian 9). Hal yang tidak ada di sumber tidak diputuskan di sini.

## 1. Latar Belakang dan Masalah

Pesanan gula dari distributor masih dicatat lewat chat dan spreadsheet. Akibatnya:

- Sales bisa menjanjikan jumlah barang tanpa melihat ketersediaan stok terbaru.
- Warehouse baru tahu pesanan setelah dokumen dikirim.
- Persetujuan supervisor tidak punya status yang jelas.
- Risiko: stok minus, pesanan ganda, diskon tidak sesuai, dan sulit menelusuri siapa mengubah atau menyetujui pesanan.

## 2. Tujuan

1. Alur pesanan jelas: draft → submitted → approved atau rejected.
2. Jumlah pesanan tidak melebihi stok tersedia; stok tidak boleh negatif.
3. Pembatasan akses berdasarkan role dan audit trail yang dapat ditelusuri.
4. Keterlacakan dari kebutuhan, desain, endpoint/layar, sampai test case.

## 3. Pengguna / Role

Role disebut di transkrip: Sales, Warehouse, Supervisor. Pada tahap desain, "Sales" disebut **Sales Admin** (di bagian lain dokumen ini tetap tertulis "Sales"). Hak akses rinci per role belum ditetapkan **[OQ-1]**; usulan ada di [design/02-rules.md](../design/02-rules.md).

Rincian desain (alur, rule, story BDD, data, API/layar, test, keterlacakan): folder [docs/design/](../design/). Tiga story di bagian 7 diperluas menjadi delapan di [design/03-user-stories.md](../design/03-user-stories.md).

Peran proyek (bukan pengguna aplikasi): System Analyst (menetapkan proses dan spesifikasi); Application Developer (membangun dan menguji MVP).

## 4. Lingkup MVP

| Termasuk | Di Luar Lingkup |
|---|---|
| Master customer dan product menggunakan data awal | Pembayaran dan rekonsiliasi bank |
| Pembuatan sales order dan item pesanan | Integrasi marketplace atau sistem eksternal |
| Pengecekan serta reservasi stok | Transfer antar-gudang dan barcode scanner |
| Submit, approve, dan reject dengan catatan | Aplikasi mobile native dan notifikasi WhatsApp |
| Daftar, pencarian, filter status, dan audit trail | Optimasi skala produksi dan deployment production |

## 5. Kebutuhan Fungsional (ringkas)

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-1 | Master customer dan product dimuat dari data awal (tanpa layar kelola master). | Lingkup MVP |
| FR-2 | Sales membuat sales order berstatus `draft` dengan satu atau lebih item. | Transkrip, Lingkup |
| FR-3 | Sistem memeriksa stok tersedia saat item ditambahkan/diubah dan menolak jumlah melebihi stok. | Tujuan 2 |
| FR-4 | Stok direservasi saat `submitted`; saat `approved` stok fisik berkurang dan reservasi dilepas; saat `rejected` reservasi dilepas tanpa mengurangi stok fisik. Stok tersedia tidak pernah negatif. | Tujuan 2, Lingkup, ABM-4 |
| FR-5 | Status pesanan: `draft`, `submitted`, `approved`, `rejected`. | Tujuan 1 |
| FR-6 | Supervisor menyetujui atau menolak pesanan `submitted` dengan catatan. | Lingkup |
| FR-7 | Daftar pesanan dengan pencarian dan filter status. | Lingkup |
| FR-8 | Audit trail: siapa, kapan, apa yang berubah, termasuk approve/reject. | Tujuan 3 |
| FR-9 | Akses dibatasi menurut role. | Tujuan 3 |

### 5.1 Aturan Bisnis Minimum (ABM)

Dari klien/pemberi tugas; bersifat keputusan, bukan asumsi.

| ID | Aturan |
|---|---|
| ABM-1 | Nomor pesanan bersifat unik dan dibuat oleh sistem. |
| ABM-2 | Pesanan minimal memiliki satu item dengan kuantitas lebih dari nol. |
| ABM-3 | Stok tersedia dihitung dari stok fisik dikurangi stok yang sedang direservasi. |
| ABM-4 | Saat pesanan `submitted`, stok direservasi. Saat `approved`, stok fisik berkurang dan reservasi dilepas. Saat `rejected`, reservasi dilepas tanpa mengurangi stok fisik. |
| ABM-5 | Hanya Supervisor yang dapat approve atau reject pesanan `submitted`. |
| ABM-6 | Setiap perubahan status menyimpan pengguna, waktu, status awal, status akhir, dan catatan. |

Dampak: draft tidak menahan stok. Dua draft dapat memuat product yang sama melebihi stok gabungan; yang mengikat adalah pengecekan dan reservasi atomik saat submit.

## 6. Kebutuhan Non-Fungsional

- Aplikasi web; Laravel dan Microsoft SQL Server (dari transkrip).
- Keterlacakan kebutuhan → desain → endpoint/layar → test case (Tujuan 4).
- Target kinerja, jumlah pengguna, dan retensi audit tidak disebut klien **[OQ-8]**.

## 7. Tiga User Story Prioritas

Prioritas mengikuti urutan alur: buat dan jaga stok (P1), putuskan (P2), telusuri (P3). Story points memakai skala Fibonacci; ini estimasi awal yang perlu divalidasi tim.

### US-1 (Prioritas 1) — Membuat sales order dengan cek stok — **8 poin**

Sebagai **Sales**, saya ingin membuat sales order dengan item pesanan dan melihat ketersediaan stok terbaru, agar saya tidak menjanjikan barang yang tidak tersedia.

Cakupan: FR-1, FR-2, FR-3, FR-4.

**Acceptance Criteria**

1. **Given** Sales login dan customer serta product tersedia dari data awal, **When** Sales membuat pesanan dan menambah item dengan jumlah ≤ stok tersedia, **Then** pesanan tersimpan berstatus `draft` dan stok tersedia tidak berubah (draft belum mereservasi, ABM-4).
2. **Given** stok tersedia suatu product adalah 10, **When** Sales menambah item dengan jumlah 11, **Then** sistem menolak, menampilkan pesan stok tidak cukup beserta stok tersedia, dan pesanan/stok tidak berubah.
3. **Given** pesanan `draft`, **When** Sales mengubah jumlah item, **Then** jumlah baru diperiksa terhadap stok tersedia dan stok tidak berubah (belum ada reservasi).
4. **Given** dua pesanan memuat product yang sama dan total jumlah melebihi stok tersedia, **When** keduanya di-submit hampir bersamaan, **Then** hanya yang muat dalam stok yang berhasil menjadi `submitted` dan stok tersedia tidak pernah negatif; yang lain ditolak dan tetap `draft` **[ASUMSI-2]**.

### US-2 (Prioritas 2) — Submit, approve, dan reject dengan catatan — **5 poin**

Sebagai **Sales** dan **Supervisor**, saya ingin pesanan melewati alur submit lalu keputusan yang berstatus jelas, agar persetujuan dapat dipertanggungjawabkan.

Cakupan: FR-5, FR-6, FR-9.

**Acceptance Criteria**

1. **Given** pesanan `draft` milik Sales dengan minimal satu item, **When** Sales men-submit, **Then** status menjadi `submitted`, stok semua item direservasi, dan pesanan tidak dapat diubah lagi oleh Sales **[ASUMSI-3]**; bila stok salah satu item tidak cukup, submit ditolak seluruhnya dan status tetap `draft`.
2. **Given** pesanan `submitted` dan Supervisor login, **When** Supervisor approve (catatan sesuai aturan **[OQ-4]**), **Then** status menjadi `approved`, catatan tersimpan, stok fisik berkurang sesuai jumlah item, dan reservasi dilepas (ABM-4).
3. **Given** pesanan `submitted` dan Supervisor login, **When** Supervisor reject dengan catatan, **Then** status menjadi `rejected`, catatan tersimpan, dan reservasi dilepas tanpa mengurangi stok fisik (ABM-4).
4. **Given** pesanan berstatus `draft`, `approved`, atau `rejected`, **When** ada upaya approve/reject, **Then** sistem menolak karena transisi status tidak valid.
5. **Given** pengguna tanpa hak approve (mis. Sales), **When** mencoba approve atau reject, **Then** sistem menolak dengan respons akses ditolak.

### US-3 (Prioritas 3) — Daftar, pencarian, filter status, dan audit trail — **5 poin**

Sebagai **Supervisor/Warehouse**, saya ingin melihat daftar pesanan, mencari, memfilter menurut status, dan menelusuri riwayat perubahan, agar tahu status pesanan dan siapa mengubah atau menyetujuinya.

Cakupan: FR-7, FR-8, FR-9.

**Acceptance Criteria**

1. **Given** terdapat pesanan dengan berbagai status, **When** pengguna berwenang membuka daftar dan memilih filter status `submitted`, **Then** hanya pesanan `submitted` yang tampil.
2. **Given** daftar pesanan, **When** pengguna mencari menurut kata kunci (bidang pencarian sesuai **[OQ-5]**), **Then** hasil hanya memuat pesanan yang cocok; jika tidak ada, tampil keadaan kosong.
3. **Given** pesanan pernah dibuat, diubah, di-submit, dan di-approve/reject, **When** pengguna berwenang membuka detail pesanan, **Then** audit trail menampilkan urutan kronologis: aksi, pengguna, waktu, dan perubahan nilai/status serta catatan.
4. **Given** catatan audit sudah tersimpan, **When** pengguna mana pun mencoba mengubah atau menghapusnya melalui aplikasi, **Then** sistem tidak menyediakan atau menolak aksi tersebut.
5. **Given** pengguna tanpa hak lihat pesanan tertentu **[OQ-1]**, **When** mengakses daftar atau detailnya, **Then** pesanan tidak ditampilkan atau akses ditolak.

Total: 18 poin.

### 7.1 Acceptance Criteria Minimum (ACM)

Dari klien/pemberi tugas; berlaku sebagai syarat lulus minimum, di atas acceptance criteria per story. Nilai angka adalah contoh dari klien.

| ID | Skenario | Given | When | Then |
|---|---|---|---|---|
| ACM-1 | Submit berhasil | Stok tersedia 100 dan draft meminta 20. | Sales Admin melakukan submit. | Status menjadi `submitted` dan reserved quantity bertambah 20. |
| ACM-2 | Stok tidak cukup | Stok tersedia 100 dan draft meminta 120. | Sales Admin melakukan submit. | Permintaan ditolak, data tidak berubah sebagian, dan pesan stok tidak cukup tampil. |
| ACM-3 | Approve | Pesanan `submitted` mereservasi 20. | Supervisor melakukan approve. | Status `approved`, stok fisik berkurang 20, reservasi dilepas, dan audit tercatat. |
| ACM-4 | Reject | Pesanan `submitted` mereservasi 20. | Supervisor melakukan reject dengan alasan. | Status `rejected`, reservasi dilepas, stok fisik tidak berkurang, dan alasan tersimpan. |
| ACM-5 | Akses ditolak | Pengguna berperan Sales Admin. | Pengguna membuka aksi approve. | Sistem mengembalikan 403 atau menolak aksi tanpa mengubah data. |

Pemetaan ke skenario BDD rinci: [design/03-user-stories.md](../design/03-user-stories.md), bagian "Acceptance Criteria Minimum".

## 8. Asumsi

| ID | Asumsi | Perlu dikonfirmasi |
|---|---|---|
| ASUMSI-1 | ~~Reservasi saat item disimpan pada `draft`.~~ **Gugur**: digantikan ABM-4 (reservasi saat `submitted`). | — |
| ASUMSI-2 | Pengecekan stok bersifat atomik terhadap permintaan bersamaan (mencegah stok minus). | — (turunan Tujuan 2) |
| ASUMSI-3 | Pesanan `submitted` terkunci dari perubahan Sales sampai ada keputusan. | OQ-3 |
| ASUMSI-4 | ~~Approve mempertahankan reservasi.~~ **Gugur**: ABM-4 menetapkan approve mengurangi stok fisik dan melepas reservasi; reject melepas reservasi tanpa mengurangi stok fisik. | — |
| ASUMSI-5 | Satu pesanan memiliki satu customer; item berupa product dengan jumlah. | OQ-6 |
| ASUMSI-6 | "Stok tersedia" = stok fisik dikurangi reservasi aktif **(ditetapkan ABM-3)**; yang masih asumsi: data stok awal disediakan bersama master. | OQ-7 |

## 9. Pertanyaan Terbuka

| ID | Pertanyaan | Dampak |
|---|---|---|
| OQ-1 | Apa hak akses tiap role (Sales, Warehouse, Supervisor, lainnya)? Apakah Sales hanya melihat pesanan sendiri? Apa yang dilakukan Warehouse di aplikasi (hanya lihat atau ada aksi)? | US-2, US-3 |
| OQ-2 | ~~Kapan stok direservasi dan dilepas?~~ **Terjawab oleh ABM-4.** Sisa: apakah item draft perlu dicek terhadap stok tersedia saat disimpan (pengecekan awal, tidak mengikat), atau hanya saat submit? Draft tidak menahan stok sehingga kedaluwarsa draft tidak lagi terkait stok. | US-1, US-2 |
| OQ-3 | Setelah `rejected`, bolehkah pesanan diedit dan diajukan ulang, atau harus dibuat baru? Bolehkah `submitted` ditarik kembali? | US-2 |
| OQ-4 | Apakah catatan wajib saat approve, saat reject, atau keduanya? Batas panjang? | US-2 |
| OQ-5 | Bidang apa yang dapat dicari (nomor pesanan, customer, product, tanggal)? | US-3 |
| OQ-6 | Transkrip menyebut risiko "diskon tidak sesuai" tetapi tidak menyebut aturan diskon. Apakah diskon ada di MVP? Jika ya, siapa yang boleh menentukan dan batasnya? Perlu juga harga, pajak, dan nomor pesanan: format dan sumbernya. | Lingkup, FR-2 |
| OQ-7 | Dari mana data awal stok, customer, dan product berasal, dan siapa menyediakannya? Apakah stok berubah di luar aplikasi selama MVP berjalan? | US-1 |
| OQ-8 | Target jumlah pengguna, volume pesanan, kinerja, dan masa simpan audit trail? | NFR |
| OQ-9 | Bagaimana mencegah "pesanan ganda" (risiko di transkrip)? Aturan deteksi duplikat belum didefinisikan. | US-1 |
| OQ-10 | Autentikasi: login lokal atau terintegrasi dengan sistem perusahaan? (Integrasi eksternal ada di luar lingkup.) | Semua |
| OQ-11 | Apakah approve menghasilkan tindak lanjut pengiriman/dokumen untuk Warehouse? Tidak ada di lingkup; konfirmasi bahwa MVP berhenti di status `approved`. | Lingkup |

## 10. Keterlacakan (awal)

| Tujuan | Kebutuhan | User Story |
|---|---|---|
| T1 Alur status jelas | FR-5, FR-6 | US-2 |
| T2 Stok tidak minus | FR-3, FR-4 | US-1 |
| T3 Akses role + audit | FR-8, FR-9 | US-2, US-3 |
| T4 Keterlacakan | Matriks ID → endpoint/layar → test case | Dibuat saat desain |

## 11. Di Luar Dokumen Ini

Desain layar, skema database, daftar endpoint, dan test case dirinci pada tahap System Analyst dan Developer setelah pertanyaan terbuka dijawab.
