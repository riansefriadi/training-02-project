# 03 — User Story dan Acceptance Criteria (BDD)

Memperluas tiga story prioritas di PRD (US-1..3) menjadi delapan story agar edge case terlihat. Direvisi mengikuti Aturan Bisnis Minimum (ABM): reservasi stok terjadi saat submit, bukan saat item disimpan. Pemetaan: PRD US-1 → US-01, US-02, US-03; PRD US-2 → US-04, US-05, US-06; PRD US-3 → US-07, US-08.
Story points: Fibonacci, estimasi awal untuk divalidasi tim (direvisi: kompleksitas reservasi berpindah ke US-04). Label **[ABM-n]** = keputusan klien; **[USULAN]** merujuk ke 01-process.md bagian 6.
Skenario diberi ID `SC-{story}-{n}`; `H` = happy path, `E` = edge case.

| Story | Judul | Aktor | Poin | Kebutuhan |
|---|---|---|---|---|
| US-01 | Membuat draft pesanan | Sales Admin | 3 | FR-1, FR-2, BR-12, BR-14, BR-15 |
| US-02 | Menambah item dengan pengecekan stok | Sales Admin | 5 | FR-3, BR-01, BR-02, BR-04 |
| US-03 | Mengubah dan menghapus item/draft | Sales Admin | 2 | FR-2, BR-05 |
| US-04 | Submit pesanan dan reservasi stok | Sales Admin | 8 | FR-4, FR-5, BR-03, BR-05, BR-06, BR-15 |
| US-05 | Approve pesanan | Supervisor | 5 | FR-6, BR-07..09, BR-15 |
| US-06 | Reject pesanan dengan catatan | Supervisor | 3 | FR-6, BR-07..10, BR-15 |
| US-07 | Daftar, pencarian, filter, dan visibilitas per role | Semua | 5 | FR-7, FR-9, BR-13 |
| US-08 | Riwayat status dan audit trail | Supervisor, Sales Admin, Warehouse | 5 | FR-8, BR-11, BR-15 |

Total: 36 poin. Jumlah skenario: 4 + 9 + 6 + 8 + 7 + 5 + 7 + 6 = 52.

---

## US-01 — Membuat draft pesanan (3)

Sebagai **Sales Admin**, saya ingin membuat pesanan draft untuk seorang customer, agar pesanan tercatat di sistem, bukan di chat.

| ID | Tipe | Skenario |
|---|---|---|
| SC-01-1 | H | **Given** Sales Admin login dan customer aktif ada, **When** ia membuat pesanan untuk customer itu, **Then** pesanan tersimpan berstatus `draft` dengan nomor unik buatan sistem [ABM-1], stok tidak berubah, dan riwayat status mencatat (status awal kosong → `draft`, pengguna, waktu) serta audit `created`. |
| SC-01-2 | E | **Given** customer nonaktif, **When** Sales Admin membuat pesanan untuk customer itu, **Then** ditolak dengan ERR-11 dan tidak ada pesanan baru. |
| SC-01-3 | E | **Given** pengguna ber-role Supervisor atau Warehouse, **When** mencoba membuat pesanan, **Then** ditolak dengan ERR-02. |
| SC-01-4 | E | **Given** pengguna belum login, **When** mencoba membuat pesanan, **Then** ditolak dengan ERR-01. |

## US-02 — Menambah item dengan pengecekan stok (5)

Sebagai **Sales Admin**, saya ingin melihat stok tersedia saat menambah item, agar saya tidak menjanjikan jumlah yang jelas melebihi stok. Draft belum mereservasi stok [ABM-4].

| ID | Tipe | Skenario |
|---|---|---|
| SC-02-1 | H | **Given** draft milik Sales Admin dan stok tersedia product A = 10 (fisik 10, reservasi 0), **When** ia menambah item A qty 4, **Then** item tersimpan, stok fisik dan reservasi A tidak berubah (tersedia tetap 10), dan audit `item_added` tercatat. |
| SC-02-2 | E | **Given** stok tersedia A = 10, **When** ia menambah item A qty 11, **Then** ditolak ERR-05 yang memuat stok tersedia 10; item tidak tersimpan. |
| SC-02-3 | E | **Given** stok tersedia A = 10, **When** ia menambah item A qty 10 (tepat sama), **Then** item tersimpan. |
| SC-02-4 | E | **Given** stok tersedia A = 0, **When** ia menambah item A qty 1, **Then** ditolak ERR-05. |
| SC-02-5 | E | **Given** qty 0, negatif, atau bukan angka, **When** item ditambahkan, **Then** ditolak ERR-04 [ABM-2]. |
| SC-02-6 | E | **Given** item A sudah ada di draft, **When** A ditambah lagi, **Then** ditolak ERR-06 **[USULAN]** U-7. |
| SC-02-7 | E | **Given** stok tersedia A = 10 dan dua draft berbeda, **When** masing-masing menambah A qty 6, **Then** keduanya tersimpan (pengecekan awal tidak mengikat, draft tidak menahan stok); konflik diselesaikan saat submit (SC-04-7). |
| SC-02-8 | E | **Given** product nonaktif, **When** item ditambahkan, **Then** ditolak ERR-11. |
| SC-02-9 | E | **Given** draft milik Sales Admin lain, **When** ia menambah item, **Then** ditolak ERR-03 **[USULAN]** U-5. |

## US-03 — Mengubah dan menghapus item atau draft (2)

Sebagai **Sales Admin**, saya ingin mengubah qty, menghapus item, atau membatalkan draft, agar isi pesanan benar sebelum diajukan.

| ID | Tipe | Skenario |
|---|---|---|
| SC-03-1 | H | **Given** item A qty 4 pada draft, **When** qty diubah menjadi 6 dan stok tersedia ≥ 6, **Then** qty menjadi 6, stok tidak berubah, dan audit `item_changed` mencatat nilai lama 4 dan baru 6. |
| SC-03-2 | H | **Given** item A qty 6, **When** qty diturunkan menjadi 2, **Then** qty menjadi 2 dan stok tidak berubah. |
| SC-03-3 | H | **Given** draft dengan item, **When** item dihapus, **Then** item hilang, stok tidak berubah, dan audit `item_removed` tercatat. |
| SC-03-4 | H | **Given** draft dengan item, **When** draft dihapus, **Then** draft dan itemnya hilang, stok tidak berubah, dan audit `draft_deleted` tercatat. |
| SC-03-5 | E | **Given** stok tersedia A = 5 dan item A qty 4, **When** qty dinaikkan menjadi 6, **Then** ditolak ERR-05; qty tetap 4. |
| SC-03-6 | E | **Given** pesanan berstatus `submitted`, `approved`, atau `rejected`, **When** item diubah atau dihapus, **Then** ditolak ERR-08. |

## US-04 — Submit pesanan dan reservasi stok (8)

Sebagai **Sales Admin**, saya ingin mengajukan draft sehingga stoknya direservasi dan Supervisor dapat memutuskan, agar stok tidak dijanjikan dua kali.

| ID | Tipe | Skenario |
|---|---|---|
| SC-04-1 | H | **Given** draft milik Sales Admin dengan item A qty 4 dan B qty 2, stok tersedia A = 10, B = 5, **When** ia submit, **Then** status `submitted`, `qty_reserved` A += 4 dan B += 2 (tersedia A = 6, B = 3, stok fisik tetap), pesanan terkunci, dan riwayat status mencatat (`draft` → `submitted`, pengguna, waktu) [ABM-4, ABM-6]. |
| SC-04-2 | E | **Given** draft tanpa item, **When** submit, **Then** ditolak ERR-09; status tetap `draft`. |
| SC-04-3 | E | **Given** pesanan sudah `submitted`, **When** submit lagi, **Then** ditolak ERR-07; reservasi tidak bertambah dua kali. |
| SC-04-4 | E | **Given** draft milik Sales Admin lain, **When** ia submit, **Then** ditolak ERR-03. |
| SC-04-5 | E | **Given** Supervisor atau Warehouse, **When** mencoba submit, **Then** ditolak ERR-02. |
| SC-04-6 | E | **Given** draft item A qty 6 yang dibuat saat tersedia 10, lalu pesanan lain mereservasi 8 (tersedia kini 2), **When** draft di-submit, **Then** ditolak ERR-05 memuat tersedia 2 dan diminta 6; status tetap `draft`. |
| SC-04-7 | E | **Given** stok tersedia A = 10 dan dua draft berbeda masing-masing item A qty 6, **When** keduanya di-submit bersamaan, **Then** tepat satu menjadi `submitted`, yang lain ditolak ERR-05 dan tetap `draft`; `qty_reserved` A = 6 dan tersedia = 4. |
| SC-04-8 | E | **Given** draft item A qty 3 (cukup) dan item B qty 9 (stok tersedia B = 5), **When** submit, **Then** seluruh submit ditolak ERR-05 yang mencantumkan B; tidak ada reservasi parsial untuk A. |

## US-05 — Approve pesanan (5)

Sebagai **Supervisor**, saya ingin menyetujui pesanan yang diajukan, agar stok fisik terpotong dan pesanan resmi dapat dilihat Warehouse.

| ID | Tipe | Skenario |
|---|---|---|
| SC-05-1 | H | **Given** pesanan `submitted` dengan item A qty 4 (fisik 10, reservasi 4), **When** Supervisor approve dengan catatan "OK", **Then** status `approved`, `qty_on_hand` A = 6, `qty_reserved` A = 0, reservasi `released`, dan riwayat status mencatat (`submitted` → `approved`, Supervisor, waktu, "OK") [ABM-4, ABM-6]. |
| SC-05-2 | H | **Given** pesanan `submitted`, **When** Supervisor approve tanpa catatan, **Then** berhasil **[USULAN]** U-3; riwayat status mencatat catatan kosong. |
| SC-05-3 | E | **Given** pesanan `draft`, `approved`, atau `rejected`, **When** approve, **Then** ditolak ERR-07; status dan stok tidak berubah. |
| SC-05-4 | E | **Given** Sales Admin atau Warehouse, **When** mencoba approve, **Then** ditolak ERR-02 [ABM-5]. |
| SC-05-5 | E | **Given** dua approve bersamaan pada pesanan yang sama, **When** keduanya diproses, **Then** stok fisik hanya berkurang sekali; yang kedua ditolak ERR-07. |
| SC-05-6 | E | **Given** catatan > 1000 karakter, **When** approve, **Then** ditolak ERR-10. |
| SC-05-7 | E | **Given** beberapa pesanan `submitted` yang total reservasinya ≤ stok fisik, **When** semuanya di-approve, **Then** `qty_on_hand` tidak pernah negatif dan `qty_reserved` kembali ke 0. |

## US-06 — Reject pesanan dengan catatan (3)

Sebagai **Supervisor**, saya ingin menolak pesanan dengan alasan, agar Sales Admin tahu penyebabnya dan stok kembali tersedia.

| ID | Tipe | Skenario |
|---|---|---|
| SC-06-1 | H | **Given** pesanan `submitted` dengan reservasi item A qty 4 (fisik 10, reservasi 4), **When** Supervisor reject dengan catatan, **Then** status `rejected`, reservasi `released`, `qty_reserved` A = 0, `qty_on_hand` A tetap 10, dan riwayat status mencatat (`submitted` → `rejected`, Supervisor, waktu, catatan) [ABM-4, ABM-6]. |
| SC-06-2 | E | **Given** pesanan `submitted`, **When** reject tanpa catatan, **Then** ditolak ERR-10; status tetap `submitted` **[USULAN]** U-3. |
| SC-06-3 | E | **Given** pesanan bukan `submitted`, **When** reject, **Then** ditolak ERR-07. |
| SC-06-4 | E | **Given** pesanan `rejected`, **When** Sales Admin mencoba mengubah atau men-submit ulang, **Then** ditolak ERR-08 atau ERR-07 **[USULAN]** U-2. |
| SC-06-5 | E | **Given** Sales Admin atau Warehouse, **When** mencoba reject, **Then** ditolak ERR-02 [ABM-5]. |

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

## US-08 — Riwayat status dan audit trail (5)

Sebagai **pengguna berwenang**, saya ingin melihat riwayat status dan perubahan pesanan, agar bisa menelusuri siapa mengubah atau menyetujui.

| ID | Tipe | Skenario |
|---|---|---|
| SC-08-1 | H | **Given** pesanan dibuat, diberi item, diubah qty, disubmit, lalu di-approve, **When** detail riwayat dibuka, **Then** riwayat status menampilkan kronologis (kosong → draft → submitted → approved) dengan pengguna, waktu, dan catatan; audit trail menampilkan perubahan item dengan nilai lama/baru. |
| SC-08-2 | H | **Given** pesanan `rejected`, **When** riwayat dibuka, **Then** baris `submitted` → `rejected` memuat catatan dan pengguna Supervisor. |
| SC-08-3 | E | **Given** riwayat status dan audit sudah tersimpan, **When** siapa pun mencoba mengubah atau menghapus lewat aplikasi, **Then** tidak tersedia atau ditolak ERR-13. |
| SC-08-4 | E | **Given** aksi gagal validasi (mis. submit ditolak ERR-05), **When** riwayat dibuka, **Then** tidak ada baris riwayat status maupun audit perubahan data untuk aksi gagal itu **[USULAN]**. |
| SC-08-5 | E | **Given** pengguna di luar visibilitas pesanan, **When** meminta riwayat/audit-nya, **Then** ERR-03. |
| SC-08-6 | E | **Given** setiap jenis perubahan status (buat, submit, approve, reject), **When** terjadi, **Then** tepat satu baris riwayat tersimpan berisi pengguna, waktu, status awal, status akhir, dan catatan [ABM-6]. |
