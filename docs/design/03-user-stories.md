# 03 — User Story dan Acceptance Criteria (BDD)

Memperluas tiga story prioritas di PRD (US-1..3) menjadi delapan story agar edge case terlihat. Pemetaan: PRD US-1 → US-01, US-02, US-03; PRD US-2 → US-04, US-05, US-06; PRD US-3 → US-07, US-08.
Story points: Fibonacci, estimasi awal untuk divalidasi tim. Label **[USULAN]** merujuk ke 01-process.md bagian 6.
Skenario diberi ID `SC-{story}-{n}`; `H` = happy path, `E` = edge case.

| Story | Judul | Aktor | Poin | Kebutuhan |
|---|---|---|---|---|
| US-01 | Membuat draft pesanan | Sales Admin | 3 | FR-1, FR-2, BR-12, BR-14 |
| US-02 | Menambah item dengan cek dan reservasi stok | Sales Admin | 8 | FR-3, FR-4, BR-01..04 |
| US-03 | Mengubah dan menghapus item/draft | Sales Admin | 3 | FR-4, BR-05, BR-09 |
| US-04 | Submit pesanan | Sales Admin | 3 | FR-5, BR-05, BR-06 |
| US-05 | Approve pesanan | Supervisor | 3 | FR-6, BR-07, BR-08, BR-09 |
| US-06 | Reject pesanan dengan catatan | Supervisor | 3 | FR-6, BR-07..10 |
| US-07 | Daftar, pencarian, filter, dan visibilitas per role | Semua | 5 | FR-7, FR-9, BR-13 |
| US-08 | Audit trail | Supervisor, Sales Admin, Warehouse | 5 | FR-8, BR-11 |

Total: 33 poin.

---

## US-01 — Membuat draft pesanan (3)

Sebagai **Sales Admin**, saya ingin membuat pesanan draft untuk seorang customer, agar pesanan tercatat di sistem, bukan di chat.

| ID | Tipe | Skenario |
|---|---|---|
| SC-01-1 | H | **Given** Sales Admin login dan customer aktif ada, **When** ia membuat pesanan untuk customer itu, **Then** pesanan tersimpan berstatus `draft`, mendapat nomor unik, dan audit `created` tercatat. |
| SC-01-2 | E | **Given** customer nonaktif, **When** Sales Admin membuat pesanan untuk customer itu, **Then** ditolak dengan ERR-11 dan tidak ada pesanan baru. |
| SC-01-3 | E | **Given** pengguna ber-role Supervisor atau Warehouse, **When** mencoba membuat pesanan, **Then** ditolak dengan ERR-02. |
| SC-01-4 | E | **Given** pengguna belum login, **When** mencoba membuat pesanan, **Then** ditolak dengan ERR-01. |

## US-02 — Menambah item dengan cek dan reservasi stok (8)

Sebagai **Sales Admin**, saya ingin melihat stok tersedia dan menambah item yang otomatis mereservasi stok, agar saya tidak menjanjikan barang yang tidak ada.

| ID | Tipe | Skenario |
|---|---|---|
| SC-02-1 | H | **Given** draft milik Sales Admin dan stok tersedia product A = 10, **When** ia menambah item A qty 4, **Then** item tersimpan, `qty_reserved` A naik 4, stok tersedia A menjadi 6, dan audit `item_added` tercatat. |
| SC-02-2 | E | **Given** stok tersedia A = 10, **When** ia menambah item A qty 11, **Then** ditolak ERR-05 yang memuat stok tersedia 10; item dan stok tidak berubah. |
| SC-02-3 | E | **Given** stok tersedia A = 10, **When** ia menambah item A qty 10 (tepat sama), **Then** berhasil dan stok tersedia A menjadi 0. |
| SC-02-4 | E | **Given** stok tersedia A = 0, **When** ia menambah item A qty 1, **Then** ditolak ERR-05; stok tidak negatif. |
| SC-02-5 | E | **Given** qty 0, negatif, atau bukan angka, **When** item ditambahkan, **Then** ditolak ERR-04. |
| SC-02-6 | E | **Given** item A sudah ada di draft, **When** A ditambah lagi, **Then** ditolak ERR-06 **[USULAN]**. |
| SC-02-7 | E | **Given** stok tersedia A = 10 dan dua draft milik pengguna berbeda, **When** keduanya bersamaan menambah A qty 6, **Then** tepat satu berhasil, satu ditolak ERR-05 (atau ERR-12), dan stok tersedia A = 4, tidak negatif. |
| SC-02-8 | E | **Given** product nonaktif, **When** item ditambahkan, **Then** ditolak ERR-11. |
| SC-02-9 | E | **Given** draft milik Sales Admin lain, **When** ia menambah item, **Then** ditolak ERR-03 **[USULAN]** U-5. |

## US-03 — Mengubah dan menghapus item atau draft (3)

Sebagai **Sales Admin**, saya ingin mengubah qty, menghapus item, atau membatalkan draft, agar reservasi stok selalu sesuai isi pesanan.

| ID | Tipe | Skenario |
|---|---|---|
| SC-03-1 | H | **Given** item A qty 4 (reservasi 4), **When** qty diubah menjadi 6 dan stok tersedia cukup, **Then** reservasi menjadi 6 dan audit `item_changed` mencatat nilai lama 4 dan baru 6. |
| SC-03-2 | H | **Given** item A qty 6, **When** qty diturunkan menjadi 2, **Then** reservasi menjadi 2 dan 4 dikembalikan ke stok tersedia. |
| SC-03-3 | H | **Given** draft dengan item, **When** item dihapus, **Then** reservasinya dilepas dan audit `item_removed` tercatat. |
| SC-03-4 | H | **Given** draft dengan item, **When** draft dihapus, **Then** semua reservasi dilepas dan audit mencatat penghapusan. |
| SC-03-5 | E | **Given** stok tersedia A = 1 dan item A qty 4, **When** qty dinaikkan menjadi 6 (butuh tambahan 2), **Then** ditolak ERR-05; qty dan reservasi tetap 4. |
| SC-03-6 | E | **Given** pesanan berstatus `submitted`, `approved`, atau `rejected`, **When** item diubah atau dihapus, **Then** ditolak ERR-08. |

## US-04 — Submit pesanan (3)

Sebagai **Sales Admin**, saya ingin mengajukan draft untuk disetujui, agar Supervisor dapat memutuskan dengan status yang jelas.

| ID | Tipe | Skenario |
|---|---|---|
| SC-04-1 | H | **Given** draft milik Sales Admin dengan ≥ 1 item, **When** ia submit, **Then** status menjadi `submitted`, `submitted_at` terisi, pesanan terkunci, dan audit `submitted` tercatat. |
| SC-04-2 | E | **Given** draft tanpa item, **When** submit, **Then** ditolak ERR-09; status tetap `draft`. |
| SC-04-3 | E | **Given** pesanan sudah `submitted`, **When** submit lagi, **Then** ditolak ERR-07 (tidak ada pesanan ganda atau transisi ganda). |
| SC-04-4 | E | **Given** draft milik Sales Admin lain, **When** ia submit, **Then** ditolak ERR-03. |
| SC-04-5 | E | **Given** Supervisor atau Warehouse, **When** mencoba submit, **Then** ditolak ERR-02. |

## US-05 — Approve pesanan (3)

Sebagai **Supervisor**, saya ingin menyetujui pesanan yang diajukan, agar pesanan resmi dan dapat dilihat Warehouse.

| ID | Tipe | Skenario |
|---|---|---|
| SC-05-1 | H | **Given** pesanan `submitted`, **When** Supervisor approve dengan catatan "OK", **Then** status `approved`, keputusan dan catatan tersimpan, reservasi tetap `active`, audit `approved` tercatat. |
| SC-05-2 | H | **Given** pesanan `submitted`, **When** Supervisor approve tanpa catatan, **Then** berhasil **[USULAN]** U-3. |
| SC-05-3 | E | **Given** pesanan `draft`, `approved`, atau `rejected`, **When** approve, **Then** ditolak ERR-07; status tidak berubah. |
| SC-05-4 | E | **Given** Sales Admin atau Warehouse, **When** mencoba approve, **Then** ditolak ERR-02. |
| SC-05-5 | E | **Given** dua approve bersamaan pada pesanan yang sama, **When** keduanya diproses, **Then** hanya satu keputusan tersimpan; yang lain ditolak ERR-07. |
| SC-05-6 | E | **Given** catatan > 1000 karakter, **When** approve, **Then** ditolak ERR-10. |

## US-06 — Reject pesanan dengan catatan (3)

Sebagai **Supervisor**, saya ingin menolak pesanan dengan alasan, agar Sales Admin tahu penyebabnya dan stok kembali tersedia.

| ID | Tipe | Skenario |
|---|---|---|
| SC-06-1 | H | **Given** pesanan `submitted` dengan reservasi, **When** Supervisor reject dengan catatan, **Then** status `rejected`, catatan tersimpan, semua reservasi `released`, stok tersedia kembali, dan audit `rejected` tercatat. |
| SC-06-2 | E | **Given** pesanan `submitted`, **When** reject tanpa catatan, **Then** ditolak ERR-10; status tetap `submitted` **[USULAN]** U-3. |
| SC-06-3 | E | **Given** pesanan bukan `submitted`, **When** reject, **Then** ditolak ERR-07. |
| SC-06-4 | E | **Given** pesanan `rejected`, **When** Sales Admin mencoba mengubah atau men-submit ulang, **Then** ditolak ERR-08 atau ERR-07 **[USULAN]** U-2. |
| SC-06-5 | E | **Given** Sales Admin atau Warehouse, **When** mencoba reject, **Then** ditolak ERR-02. |

## US-07 — Daftar, pencarian, filter, dan visibilitas per role (5)

Sebagai **pengguna berwenang**, saya ingin melihat daftar pesanan yang boleh saya lihat, mencari, dan memfilter status, agar mudah menemukan pesanan.

| ID | Tipe | Skenario |
|---|---|---|
| SC-07-1 | H | **Given** pesanan berbagai status, **When** Supervisor memfilter `submitted`, **Then** hanya pesanan `submitted` tampil. |
| SC-07-2 | H | **Given** daftar pesanan, **When** pengguna mencari nomor pesanan atau nama customer **[OQ-5, bidang usulan]**, **Then** hanya yang cocok tampil. |
| SC-07-3 | E | **Given** kata kunci tanpa hasil, **When** mencari, **Then** tampil keadaan kosong tanpa error. |
| SC-07-4 | E | **Given** Sales Admin A dan B punya pesanan masing-masing, **When** A membuka daftar, **Then** hanya pesanan A tampil **[USULAN]** U-5. |
| SC-07-5 | E | **Given** pesanan `draft` dan `submitted` ada, **When** Warehouse membuka daftar, **Then** hanya `approved` tampil **[USULAN]** U-1. |
| SC-07-6 | E | **Given** Warehouse membuka detail pesanan `draft` lewat URL langsung, **When** diminta, **Then** ERR-03 (404), bukan data pesanan. |
| SC-07-7 | E | **Given** filter status tidak dikenal, **When** diminta, **Then** ditolak 422 dengan pesan nilai filter tidak valid. |

## US-08 — Audit trail (5)

Sebagai **pengguna berwenang**, saya ingin melihat riwayat perubahan pesanan, agar bisa menelusuri siapa mengubah atau menyetujui.

| ID | Tipe | Skenario |
|---|---|---|
| SC-08-1 | H | **Given** pesanan dibuat, diberi item, diubah qty, disubmit, lalu di-approve, **When** detail audit dibuka, **Then** tampil kronologis: aksi, pengguna, waktu, nilai lama/baru, dan catatan keputusan. |
| SC-08-2 | H | **Given** pesanan `rejected`, **When** audit dibuka, **Then** entri reject memuat catatan dan pengguna Supervisor. |
| SC-08-3 | E | **Given** audit sudah tersimpan, **When** siapa pun mencoba mengubah atau menghapus lewat aplikasi, **Then** tidak tersedia atau ditolak ERR-13. |
| SC-08-4 | E | **Given** aksi gagal validasi (mis. ERR-05), **When** audit dibuka, **Then** tidak ada entri perubahan data untuk aksi gagal itu **[USULAN]**. |
| SC-08-5 | E | **Given** pengguna di luar visibilitas pesanan, **When** meminta audit-nya, **Then** ERR-03. |
